<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmationNotification;
use App\Notifications\NewBookingNotification;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class BookingController extends Controller
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['trip.vessel.vesselType', 'payment', 'offer'])
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'passengers_count' => ['required', 'integer', 'min:1', 'max:20'],
            'payment_method' => ['required', 'in:cash,card,bank_transfer,online'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'booked_for_name' => ['nullable', 'string', 'max:255'],
            'booking_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $trip = Trip::findOrFail($validated['trip_id']);

        if ($trip->status !== 'scheduled') {
            return response()->json(['message' => 'الرحلة غير متاحة للحجز'], 422);
        }

        if ($trip->available_seats < $validated['passengers_count']) {
            return response()->json([
                'message' => 'عدد المقاعد المطلوبة غير متوفر. المتاح: ' . $trip->available_seats,
            ], 422);
        }

        $promoCode = $validated['promo_code'] ?? null;
        if ($promoCode !== null) {
            $promoCode = trim($promoCode) === '' ? null : $promoCode;
        }

        $bestOffer = $this->offerService->getBestOfferForBooking($trip, $validated['passengers_count'], $promoCode);
        if ($promoCode !== null && $bestOffer === null) {
            return response()->json(['message' => 'كود الخصم غير صالح أو منتهي'], 422);
        }

        $subtotal = (float) $trip->price * $validated['passengers_count'];
        $discountAmount = $bestOffer ? $bestOffer['discount_amount'] : 0;
        $finalAmount = $subtotal - $discountAmount;

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'trip_id' => $trip->id,
            'offer_id' => $bestOffer['offer']->id ?? null,
            'passengers_count' => $validated['passengers_count'],
            'booked_for_name' => $validated['booked_for_name'] ?? null,
            'booking_notes' => $validated['booking_notes'] ?? null,
            'discount_amount' => $discountAmount,
            'status' => 'confirmed',
            'booking_reference' => Booking::generateReference(),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'amount' => $finalAmount,
            'method' => $validated['payment_method'],
            'status' => 'completed',
            'transaction_id' => 'TXN-' . uniqid(),
        ]);

        $trip->decrement('available_seats', $validated['passengers_count']);

        $request->user()->notify(new BookingConfirmationNotification($booking));
        Notification::send(User::staff()->get(), new NewBookingNotification($booking));

        return response()->json([
            'message' => 'تم الحجز بنجاح',
            'booking' => $booking->load(['trip.vessel.vesselType', 'payment', 'offer']),
        ], 201);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'لا يمكن تعديل هذا الحجز'], 422);
        }

        $validated = $request->validate([
            'passengers_count' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        if (isset($validated['passengers_count'])) {
            $diff = $validated['passengers_count'] - $booking->passengers_count;
            $trip = $booking->trip;

            if ($diff > 0 && $trip->available_seats < $diff) {
                return response()->json(['message' => 'عدد المقاعد الإضافية غير متوفر'], 422);
            }

            $booking->update(['passengers_count' => $validated['passengers_count']]);
            $trip->increment('available_seats', -$diff);

            $subtotal = (float) $trip->price * $validated['passengers_count'];
            $discount = (float) ($booking->discount_amount ?? 0);
            if ($booking->offer_id && $booking->offer) {
                $discount = $booking->offer->calculateDiscount((float) $trip->price, $validated['passengers_count']);
            }
            $booking->payment->update([
                'amount' => $subtotal - $discount,
            ]);
            $booking->update(['discount_amount' => $discount]);
        }

        return response()->json([
            'message' => 'تم تحديث الحجز',
            'booking' => $booking->fresh(['trip.vessel.vesselType', 'payment']),
        ]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز'], 422);
        }

        $hasPaid = $booking->payment && $booking->payment->status === 'completed';

        $rules = [
            'reason' => ['required', 'string', 'max:500'],
        ];
        if ($hasPaid) {
            $rules['refund_method'] = ['required', 'string', 'in:mail,bank_transfer,wallet'];
        } else {
            $rules['refund_method'] = ['nullable', 'string', 'in:mail,bank_transfer,wallet'];
        }

        $validated = $request->validate($rules);

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['reason'],
            'refund_method' => $validated['refund_method'] ?? null,
            'cancelled_at' => now(),
        ]);

        $booking->trip->increment('available_seats', $booking->passengers_count);
        $booking->payment?->update(['status' => 'refunded']);

        $booking->user?->notify(new BookingCancelledNotification($booking, false));
        Notification::send(User::staff()->get(), new BookingCancelledNotification($booking->fresh(), true));

        return response()->json([
            'message' => 'تم إلغاء الحجز بنجاح',
        ]);
    }
}
