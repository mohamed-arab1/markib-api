<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'trip.vessel', 'booking']);

        if ($request->filled('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($reviews);
    }

    public function approve(Review $review): JsonResponse
    {
        $review->update(['is_approved' => true]);

        return response()->json([
            'message' => 'تم اعتماد التقييم',
            'review' => $review->fresh(),
        ]);
    }

    public function reject(Review $review): JsonResponse
    {
        $review->update(['is_approved' => false]);

        return response()->json([
            'message' => 'تم رفض التقييم',
            'review' => $review->fresh(),
        ]);
    }

    public function toggleFeatured(Review $review): JsonResponse
    {
        $review->update(['is_featured' => !$review->is_featured]);

        return response()->json([
            'message' => $review->is_featured ? 'تم تمييز التقييم' : 'تم إلغاء تمييز التقييم',
            'review' => $review->fresh(),
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['message' => 'تم حذف التقييم']);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Review::count(),
            'approved' => Review::where('is_approved', true)->count(),
            'pending' => Review::where('is_approved', false)->count(),
            'average_rating' => round(Review::where('is_approved', true)->avg('rating'), 1),
            'by_rating' => Review::where('is_approved', true)
                ->selectRaw('rating, count(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating'),
        ];

        return response()->json($stats);
    }
}
