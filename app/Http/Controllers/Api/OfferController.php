<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function index(): JsonResponse
    {
        $offers = Offer::active()
            ->orderByPriority()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'name_ar' => $offer->name_ar,
                    'name_en' => $offer->name_en,
                    'description_ar' => $offer->description_ar,
                    'description_en' => $offer->description_en,
                    'discount_type' => $offer->discount_type,
                    'discount_value' => (float) $offer->discount_value,
                    'start_date' => $offer->start_date->format('Y-m-d'),
                    'end_date' => $offer->end_date->format('Y-m-d'),
                    'applicable_days' => $offer->applicable_days,
                    'applies_to' => $offer->applies_to,
                    'min_passengers' => $offer->min_passengers,
                    'min_booking_amount' => $offer->min_booking_amount ? (float) $offer->min_booking_amount : null,
                    'max_discount_amount' => $offer->max_discount_amount ? (float) $offer->max_discount_amount : null,
                    'promo_code' => $offer->promo_code,
                ];
            });

        return response()->json($offers);
    }
}
