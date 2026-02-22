<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Trip::with(['vessel.vesselType', 'vessel.coverImage', 'vessel.images', 'location.images', 'coverImage', 'images'])
            ->where('status', 'scheduled')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time');

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('vessel_type')) {
            $query->whereHas('vessel.vesselType', fn ($q) => $q->where('id', $request->vessel_type));
        }

        // نوع الرحلة: sunset = رحلات غروب (16:00 - 19:59), dinner_night = رحلات عشاء ليليه (20:00+)
        if ($request->filled('type')) {
            if ($request->type === 'sunset') {
                $query->whereTime('start_time', '>=', '16:00')->whereTime('start_time', '<', '20:00');
            } elseif ($request->type === 'dinner_night') {
                $query->whereTime('start_time', '>=', '20:00');
            }
        }

        $trips = $query->paginate($request->get('per_page', 15));

        $trips->getCollection()->transform(function (Trip $trip) {
            return $this->appendOfferToTrip($trip);
        });

        return response()->json($trips);
    }

    public function show(Trip $trip): JsonResponse
    {
        if ($trip->status !== 'scheduled') {
            return response()->json(['message' => 'الرحلة غير متاحة'], 404);
        }

        $trip->load([
            'vessel.vesselType',
            'vessel.coverImage',
            'vessel.images',
            'location.images',
            'coverImage',
            'galleryImages',
            'reviews.user',
            'reviews.replies.user'
        ]);

        // إضافة متوسط التقييمات
        $approvedReviews = $trip->reviews()->where('is_approved', true)->get();
        $trip->setAttribute('average_rating', $approvedReviews->avg('rating') ?? 0);
        $trip->setAttribute('reviews_count', $approvedReviews->count());

        return response()->json($this->appendOfferToTrip($trip));
    }

    private function appendOfferToTrip(Trip $trip): array
    {
        $data = $trip->toArray();
        $offers = $this->offerService->getApplicableOffers($trip);
        $data['applicable_offers'] = $offers->map(fn ($o) => [
            'id' => $o->id,
            'name_ar' => $o->name_ar,
            'name_en' => $o->name_en,
            'description_ar' => $o->description_ar,
            'discount_type' => $o->discount_type,
            'discount_value' => (float) $o->discount_value,
            'promo_code' => $o->promo_code,
        ])->values();

        $data['is_full'] = $trip->available_seats <= 0;
        $best = $this->offerService->getBestOfferForBooking($trip, 1);
        $unitPrice = (float) $trip->price;
        $data['original_price'] = $unitPrice;
        if ($best) {
            $data['discount_amount_per_seat'] = $best['discount_amount'];
            $data['price_after_discount'] = $unitPrice - ($best['discount_amount'] / 1);
        } else {
            $data['discount_amount_per_seat'] = 0;
            $data['price_after_discount'] = $unitPrice;
        }

        return $data;
    }
}
