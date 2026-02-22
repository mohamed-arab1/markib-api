<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\BookingCancelledNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['user', 'trip.vessel.vesselType', 'trip.location', 'payment', 'offer']);

        if ($request->filled('status')) {
            $statuses = explode(',', $request->status);
            if (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by period (based on trip date for today, created_at for others)
        if ($request->filled('period')) {
            $period = $request->period;
            
            switch ($period) {
                case 'today':
                    // الحجوزات التي تم إنشاؤها اليوم
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        }

        if ($request->filled('date_from')) {
            $query->whereHas('trip', fn ($q) => $q->whereDate('date', '>=', $request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->whereHas('trip', fn ($q) => $q->whereDate('date', '<=', $request->date_to));
        }

        $bookings = $query->latest()->paginate(50);

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['user', 'trip.vessel.vesselType', 'payment', 'offer']);

        return response()->json($booking);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز'], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('reason', 'إلغاء من الإدارة'),
            'refund_method' => $request->input('refund_method'),
            'cancelled_at' => now(),
        ]);

        $booking->trip->increment('available_seats', $booking->passengers_count);
        $booking->payment?->update(['status' => 'refunded']);

        $booking->user?->notify(new BookingCancelledNotification($booking, false));

        return response()->json(['message' => 'تم إلغاء الحجز']);
    }
}
