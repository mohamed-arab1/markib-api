<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Trip;
use Carbon\Carbon;

class OfferService
{
    /**
     * العروض النشطة التي تنطبق على رحلة في تاريخ معين (ويوم أسبوع محدد).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Offer>
     */
    public function getApplicableOffers(Trip $trip, ?Carbon $date = null): \Illuminate\Database\Eloquent\Collection
    {
        $date = $date ?? $trip->date;
        $dayOfWeek = strtolower($date->locale('en')->dayName);

        return Offer::active()
            ->orderByPriority()
            ->get()
            ->filter(function (Offer $offer) use ($trip, $dayOfWeek) {
                return $offer->appliesToDay($dayOfWeek) && $offer->appliesToTrip($trip);
            })
            ->values();
    }

    /**
     * أفضل عرض واحد يُطبّق (الأول حسب الأولوية) مع مبلغ الخصم.
     */
    public function getBestOfferForBooking(Trip $trip, int $passengersCount, ?string $promoCode = null): ?array
    {
        $offers = $this->getApplicableOffers($trip);

        if ($promoCode !== null && $promoCode !== '') {
            $byCode = $offers->firstWhere('promo_code', $promoCode);
            if ($byCode) {
                $discount = $byCode->calculateDiscount((float) $trip->price, $passengersCount);
                return [
                    'offer' => $byCode,
                    'discount_amount' => $discount,
                ];
            }
            return null;
        }

        $best = $offers->first();
        if (!$best) {
            return null;
        }

        $discount = $best->calculateDiscount((float) $trip->price, $passengersCount);
        return [
            'offer' => $best,
            'discount_amount' => $discount,
        ];
    }
}
