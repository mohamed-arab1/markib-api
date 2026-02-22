<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripImage;
use App\Models\Vessel;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminTripController extends Controller
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Trip::with(['vessel.vesselType', 'location.images', 'images', 'coverImage']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $trips = $query->orderBy('date')->orderBy('start_time')->paginate(20);

        $trips->getCollection()->transform(function (Trip $trip) {
            $data = $trip->toArray();
            $offers = $this->offerService->getApplicableOffers($trip);
            $data['applicable_offers'] = $offers->map(fn ($o) => [
                'id' => $o->id,
                'name_ar' => $o->name_ar,
                'discount_type' => $o->discount_type,
                'discount_value' => (float) $o->discount_value,
                'promo_code' => $o->promo_code,
            ])->values();
            $best = $this->offerService->getBestOfferForBooking($trip, 1);
            $unitPrice = (float) $trip->price;
            $data['original_price'] = $unitPrice;
            $data['price_after_discount'] = $best ? $unitPrice - $best['discount_amount'] : $unitPrice;
            $data['discount_amount_per_seat'] = $best ? $best['discount_amount'] : 0;
            $data['is_full'] = $trip->available_seats <= 0;
            return $data;
        });

        return response()->json($trips);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vessel_id' => ['required', 'exists:vessels,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:480'],
            'price' => ['required', 'numeric', 'min:0'],
            'available_seats' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $vessel = Vessel::findOrFail($validated['vessel_id']);
        if ($validated['available_seats'] > $vessel->capacity) {
            return response()->json(['message' => 'عدد المقاعد أكبر من سعة المركب'], 422);
        }

        $validated['total_seats'] = $validated['available_seats'];
        $trip = Trip::create($validated);

        return response()->json([
            'message' => 'تم إنشاء الرحلة',
            'trip' => $trip->load(['vessel.vesselType', 'location.images']),
        ], 201);
    }

    public function update(Request $request, Trip $trip): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
            'date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'duration_minutes' => ['sometimes', 'integer', 'min:30', 'max:480'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'available_seats' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:scheduled,completed,cancelled'],
        ]);

        $trip->update($validated);

        return response()->json([
            'message' => 'تم تحديث الرحلة',
            'trip' => $trip->fresh(['vessel.vesselType', 'location.images']),
        ]);
    }

    public function destroy(Trip $trip): JsonResponse
    {
        if ($trip->bookings()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف رحلة تحتوي على حجوزات'], 422);
        }

        // Delete associated images
        foreach ($trip->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $trip->delete();
        return response()->json(['message' => 'تم حذف الرحلة']);
    }

    public function uploadCover(Request $request, Trip $trip): JsonResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        // Delete old cover if exists
        $oldCover = $trip->coverImage;
        if ($oldCover) {
            Storage::disk('public')->delete($oldCover->path);
            $oldCover->delete();
        }

        $file = $request->file('cover');
        $path = $file->store('trips/covers', 'public');
        $url = Storage::url($path);

        $image = TripImage::create([
            'trip_id' => $trip->id,
            'path' => $path,
            'url' => $url,
            'alt_text' => $trip->vessel?->name_ar ?? 'صورة الرحلة',
            'type' => 'cover',
            'is_primary' => true,
        ]);

        return response()->json([
            'message' => 'تم رفع صورة الغلاف',
            'image' => $image,
        ]);
    }

    public function uploadGallery(Request $request, Trip $trip): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $uploadedImages = [];
        $sortOrder = $trip->galleryImages()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $file) {
            $sortOrder++;
            $path = $file->store('trips/gallery', 'public');
            $url = Storage::url($path);

            $image = TripImage::create([
                'trip_id' => $trip->id,
                'path' => $path,
                'url' => $url,
                'alt_text' => $trip->vessel?->name_ar ?? 'صورة الرحلة',
                'type' => 'gallery',
                'sort_order' => $sortOrder,
            ]);

            $uploadedImages[] = $image;
        }

        return response()->json([
            'message' => 'تم رفع الصور بنجاح',
            'images' => $uploadedImages,
        ]);
    }

    public function deleteImage(Trip $trip, TripImage $tripImage): JsonResponse
    {
        if ($tripImage->trip_id !== $trip->id) {
            return response()->json(['message' => 'الصورة لا تنتمي لهذه الرحلة'], 422);
        }

        Storage::disk('public')->delete($tripImage->path);
        $tripImage->delete();

        return response()->json(['message' => 'تم حذف الصورة']);
    }

    public function getImages(Trip $trip): JsonResponse
    {
        return response()->json([
            'cover' => $trip->coverImage,
            'gallery' => $trip->galleryImages,
        ]);
    }
}
