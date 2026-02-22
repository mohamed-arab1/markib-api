<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Offer::with('trips:id');

        if ($request->boolean('active_only')) {
            $query->active();
        } elseif ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $offers = $query->orderByDesc('priority')->orderByDesc('created_at')->paginate(20);

        return response()->json($offers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'applicable_days' => ['nullable', 'array'],
            'applicable_days.*' => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'applies_to' => ['required', 'in:all_trips,selected_trips'],
            'min_booking_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_passengers' => ['nullable', 'integer', 'min:1'],
            'promo_code' => ['nullable', 'string', 'max:50', 'unique:offers,promo_code'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'trip_ids' => ['nullable', 'array'],
            'trip_ids.*' => ['exists:trips,id'],
        ]);

        if (($validated['discount_type'] ?? '') === 'percentage' && ($validated['discount_value'] ?? 0) > 100) {
            return response()->json(['message' => 'نسبة الخصم لا يمكن أن تتجاوز 100%'], 422);
        }

        $tripIds = $validated['trip_ids'] ?? [];
        unset($validated['trip_ids']);
        $validated['priority'] = $validated['priority'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $offer = Offer::create($validated);

        if ($offer->applies_to === 'selected_trips' && ! empty($tripIds)) {
            $offer->trips()->sync($tripIds);
        }

        return response()->json([
            'message' => 'تم إنشاء العرض بنجاح',
            'offer' => $offer->load('trips:id'),
        ], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        $offer->load('trips:id,date,start_time');
        return response()->json($offer);
    }

    public function update(Request $request, Offer $offer): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'discount_type' => ['sometimes', 'in:percentage,fixed'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'applicable_days' => ['nullable', 'array'],
            'applicable_days.*' => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'applies_to' => ['sometimes', 'in:all_trips,selected_trips'],
            'min_booking_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'promo_code' => ['nullable', 'string', 'max:50', 'unique:offers,promo_code,' . $offer->id],
            'priority' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'trip_ids' => ['nullable', 'array'],
            'trip_ids.*' => ['exists:trips,id'],
        ]);

        if (isset($validated['discount_type']) && $validated['discount_type'] === 'percentage' && ($validated['discount_value'] ?? 0) > 100) {
            return response()->json(['message' => 'نسبة الخصم لا يمكن أن تتجاوز 100%'], 422);
        }

        $tripIds = $validated['trip_ids'] ?? null;
        unset($validated['trip_ids']);

        $offer->update($validated);

        if ($tripIds !== null) {
            $offer->trips()->sync($offer->applies_to === 'selected_trips' ? $tripIds : []);
        }

        return response()->json([
            'message' => 'تم تحديث العرض',
            'offer' => $offer->fresh()->load('trips:id'),
        ]);
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $offer->delete();
        return response()->json(['message' => 'تم حذف العرض']);
    }

    public function syncTrips(Request $request, Offer $offer): JsonResponse
    {
        $validated = $request->validate([
            'trip_ids' => ['required', 'array'],
            'trip_ids.*' => ['exists:trips,id'],
        ]);

        if ($offer->applies_to !== 'selected_trips') {
            return response()->json(['message' => 'العرض مضبوط على كل الرحلات. غيّر "ينطبق على" إلى رحلات محددة أولاً.'], 422);
        }

        $offer->trips()->sync($validated['trip_ids']);
        return response()->json([
            'message' => 'تم ربط الرحلات بالعرض',
            'offer' => $offer->fresh()->load('trips:id'),
        ]);
    }
}
