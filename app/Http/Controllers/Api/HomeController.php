<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Trip;
use App\Models\Vessel;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    public function index(): JsonResponse
    {
        $heroSlides = $this->getHeroSlides();
        $popularVessels = $this->getPopularVessels();
        $sunsetTrips = $this->getTripsByType('sunset', 6);
        $dinnerNightTrips = $this->getTripsByType('dinner_night', 6);

        return response()->json([
            'hero_slides' => $heroSlides,
            'popular_vessels' => $popularVessels,
            'sunset_trips' => $sunsetTrips,
            'dinner_night_trips' => $dinnerNightTrips,
        ]);
    }

    /**
     * رحلات غروب (16:00 - 19:59) أو رحلات عشاء ليليه (20:00+)
     */
    private function getTripsByType(string $type, int $limit = 6): array
    {
        $query = Trip::with(['vessel.vesselType', 'vessel.coverImage', 'vessel.images', 'location', 'coverImage', 'images'])
            ->where('status', 'scheduled')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit);

        if ($type === 'sunset') {
            $query->whereTime('start_time', '>=', '16:00')->whereTime('start_time', '<', '20:00');
        } elseif ($type === 'dinner_night') {
            $query->whereTime('start_time', '>=', '20:00');
        } else {
            return [];
        }

        $trips = $query->get();

        return $trips->map(function (Trip $trip) {
            $best = $this->offerService->getBestOfferForBooking($trip, 1);
            $unitPrice = (float) $trip->price;
            $priceAfterDiscount = $best ? $unitPrice - ($best['discount_amount'] / 1) : $unitPrice;

            $data = $trip->toArray();
            $data['original_price'] = $unitPrice;
            $data['price_after_discount'] = $priceAfterDiscount;
            $data['is_full'] = $trip->available_seats <= 0;

            return $data;
        })->toArray();
    }

    private function getHeroSlides(): array
    {
        $slides = [];

        // صور مراكب (غلاف المركب + صور المعرض)
        $vessels = Vessel::where('is_active', true)
            ->with(['coverImage', 'galleryImages', 'vesselType'])
            ->get();

        foreach ($vessels as $vessel) {
            $images = [];
            $cover = $vessel->coverImage;
            if ($cover && ($cover->url || $cover->path)) {
                $images[] = ['url' => $this->normalizeUrl($cover->url, $cover->path), 'title' => $vessel->name_ar];
            }
            foreach ($vessel->galleryImages as $img) {
                $u = $img->url ?? ($img->path ? '/storage/' . $img->path : null);
                if ($u) {
                    $images[] = ['url' => $this->normalizeUrl($u, $img->path), 'title' => $vessel->name_ar];
                }
            }
            $slides = array_merge($slides, $images);
        }

        // صور المواقع
        $locations = Location::where('is_active', true)
            ->with('images')
            ->get();

        foreach ($locations as $loc) {
            foreach ($loc->images ?? [] as $img) {
                $u = $img->url ?? ($img->path ? '/storage/' . $img->path : null);
                if ($u) {
                    $slides[] = ['url' => $this->normalizeUrl($u, $img->path ?? null), 'title' => $loc->name_ar];
                }
            }
        }

        return array_slice(array_values($slides), 0, 10);
    }

    private function normalizeUrl(?string $url, ?string $path): string
    {
        if ($url && (str_starts_with($url, 'http') || str_starts_with($url, '/'))) {
            return $url;
        }
        if ($path) {
            return str_starts_with($path, '/') ? $path : '/storage/' . $path;
        }
        return $url ?? '';
    }

    private function getPopularVessels(int $limit = 6): array
    {
        $vesselIds = DB::table('vessels')
            ->join('trips', 'trips.vessel_id', '=', 'vessels.id')
            ->join('bookings', 'bookings.trip_id', '=', 'trips.id')
            ->where('vessels.is_active', true)
            ->where('bookings.status', '!=', 'cancelled')
            ->groupBy('vessels.id')
            ->orderByRaw('COUNT(bookings.id) DESC')
            ->limit($limit)
            ->pluck('vessels.id');

        if ($vesselIds->isEmpty()) {
            $vessels = Vessel::where('is_active', true)
                ->with(['vesselType', 'coverImage', 'images'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        } else {
            $order = $vesselIds->flip()->toArray();
            $vessels = Vessel::whereIn('id', $vesselIds)
                ->with(['vesselType', 'coverImage', 'images'])
                ->get()
                ->sortBy(fn ($v) => $order[$v->id] ?? 999);
        }

        return $vessels->map(function ($v) {
            $img = $v->coverImage ?? $v->images->first();
            $url = null;
            if ($img) {
                $url = $img->url ?? ($img->path ? '/storage/' . $img->path : null);
            }
            $nextTrip = $v->trips()
                ->where('status', 'scheduled')
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->orderBy('start_time')
                ->first();
            return [
                'id' => $v->id,
                'name_ar' => $v->name_ar,
                'name' => $v->name,
                'vessel_type' => $v->vesselType?->name_ar,
                'image_url' => $url,
                'next_trip_id' => $nextTrip?->id,
            ];
        })->values()->toArray();
    }
}
