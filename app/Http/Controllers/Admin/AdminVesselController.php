<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Models\VesselImage;
use App\Models\VesselType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminVesselController extends Controller
{
    public function vesselTypes(): JsonResponse
    {
        $types = VesselType::withCount('vessels')->get();
        return response()->json($types);
    }

    public function storeVesselType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $type = VesselType::create($validated);
        return response()->json(['message' => 'تم الإنشاء', 'vessel_type' => $type], 201);
    }

    public function updateVesselType(Request $request, VesselType $vesselType): JsonResponse
    {
        $vesselType->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['message' => 'تم التحديث', 'vessel_type' => $vesselType]);
    }

    public function vessels(Request $request): JsonResponse
    {
        $query = Vessel::with(['vesselType', 'images', 'coverImage']);
        if ($request->filled('type_id')) {
            $query->where('vessel_type_id', $request->type_id);
        }
        $vessels = $query->paginate(20);
        return response()->json($vessels);
    }

    public function storeVessel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vessel_type_id' => ['required', 'exists:vessel_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'image' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $vessel = Vessel::create($validated);

        return response()->json(['message' => 'تم الإنشاء', 'vessel' => $vessel->load('vesselType')], 201);
    }

    public function updateVessel(Request $request, Vessel $vessel): JsonResponse
    {
        $vessel->update($request->validate([
            'vessel_type_id' => ['sometimes', 'exists:vessel_types,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'image' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]));

        return response()->json(['message' => 'تم التحديث', 'vessel' => $vessel->fresh('vesselType')]);
    }

    public function uploadCover(Request $request, Vessel $vessel): JsonResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        // Delete old cover if exists
        $oldCover = $vessel->coverImage;
        if ($oldCover) {
            Storage::disk('public')->delete($oldCover->path);
            $oldCover->delete();
        }

        $file = $request->file('cover');
        $path = $file->store('vessels/covers', 'public');
        $url = Storage::url($path);

        $image = VesselImage::create([
            'vessel_id' => $vessel->id,
            'path' => $path,
            'url' => $url,
            'alt_text' => $vessel->name_ar ?? 'صورة المركب',
            'type' => 'cover',
            'is_primary' => true,
        ]);

        return response()->json([
            'message' => 'تم رفع صورة الغلاف',
            'image' => $image,
        ]);
    }

    public function uploadGallery(Request $request, Vessel $vessel): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $uploadedImages = [];
        $sortOrder = $vessel->galleryImages()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $file) {
            $sortOrder++;
            $path = $file->store('vessels/gallery', 'public');
            $url = Storage::url($path);

            $image = VesselImage::create([
                'vessel_id' => $vessel->id,
                'path' => $path,
                'url' => $url,
                'alt_text' => $vessel->name_ar ?? 'صورة المركب',
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

    public function deleteImage(Vessel $vessel, VesselImage $vesselImage): JsonResponse
    {
        if ($vesselImage->vessel_id !== $vessel->id) {
            return response()->json(['message' => 'الصورة لا تنتمي لهذا المركب'], 422);
        }

        Storage::disk('public')->delete($vesselImage->path);
        $vesselImage->delete();

        return response()->json(['message' => 'تم حذف الصورة']);
    }

    public function getImages(Vessel $vessel): JsonResponse
    {
        return response()->json([
            'cover' => $vessel->coverImage,
            'gallery' => $vessel->galleryImages,
        ]);
    }
}
