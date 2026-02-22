<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminLocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::with('images')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($locations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'string'],
            'longitude' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $location = Location::create($validated);

        return response()->json(['message' => 'تم إنشاء الموقع', 'location' => $location->load('images')], 201);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $location->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'string'],
            'longitude' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]));

        return response()->json(['message' => 'تم التحديث', 'location' => $location->fresh('images')]);
    }

    public function destroy(Location $location): JsonResponse
    {
        foreach ($location->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        $location->delete();
        return response()->json(['message' => 'تم الحذف']);
    }

    public function uploadImages(Request $request, Location $location): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $uploaded = [];
        $sortOrder = $location->images()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $file) {
            $path = $file->store('locations/' . $location->id, 'public');
            $sortOrder++;
            $image = $location->images()->create([
                'path' => $path,
                'alt_text' => $location->name_ar,
                'sort_order' => $sortOrder,
                'is_primary' => $location->images()->count() === 0,
            ]);
            $uploaded[] = $image;
        }

        return response()->json([
            'message' => 'تم رفع الصور',
            'images' => $location->fresh('images')->images,
        ], 201);
    }

    public function deleteImage(Location $location, LocationImage $location_image): JsonResponse
    {
        if ($location_image->location_id !== $location->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
        Storage::disk('public')->delete($location_image->path);
        $wasPrimary = $location_image->is_primary;
        $location_image->delete();

        if ($wasPrimary && $location->images()->exists()) {
            $location->images()->first()->update(['is_primary' => true]);
        }

        return response()->json(['message' => 'تم حذف الصورة']);
    }

    public function setPrimaryImage(Location $location, LocationImage $location_image): JsonResponse
    {
        if ($location_image->location_id !== $location->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
        $location->images()->update(['is_primary' => false]);
        $location_image->update(['is_primary' => true]);
        return response()->json(['message' => 'تم تعيين الصورة الرئيسية']);
    }
}
