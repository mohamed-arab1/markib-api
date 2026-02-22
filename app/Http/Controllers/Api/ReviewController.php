<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\NewReviewNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * الحصول على التقييمات لرحلة معينة
     */
    public function index(Request $request, Trip $trip): JsonResponse
    {
        $reviews = Review::with(['user', 'replies.user'])
            ->where('trip_id', $trip->id)
            ->where('is_approved', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($reviews);
    }

    /**
     * إنشاء تقييم جديد
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'booking_id' => [
                'required',
                Rule::exists('bookings', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // التحقق من أن الحجز للرحلة المحددة
        $booking = Booking::findOrFail($validated['booking_id']);
        if ($booking->trip_id != $validated['trip_id']) {
            return response()->json(['message' => 'الحجز لا ينتمي لهذه الرحلة'], 422);
        }

        // التحقق من عدم وجود تقييم سابق لهذا الحجز
        $existingReview = Review::where('booking_id', $validated['booking_id'])->first();
        if ($existingReview) {
            return response()->json(['message' => 'تم التقييم مسبقاً لهذا الحجز'], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'trip_id' => $validated['trip_id'],
            'booking_id' => $validated['booking_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_approved' => true, // يمكن تغييرها لتحتاج موافقة
        ]);

        $review->load(['user', 'replies.user']);

        Notification::send(User::staff()->get(), new NewReviewNotification($review));

        return response()->json([
            'message' => 'تم إضافة التقييم بنجاح',
            'review' => $review,
        ], 201);
    }

    /**
     * إضافة رد من الدعم الفني على تقييم
     */
    public function reply(Request $request, Review $review): JsonResponse
    {
        $user = Auth::user();

        // التحقق من أن المستخدم لديه صلاحية الدعم الفني
        if (!in_array($user->role, ['admin', 'support'])) {
            return response()->json(['message' => 'غير مصرح - تحتاج صلاحية الدعم الفني'], 403);
        }

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);

        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'reply' => $validated['reply'],
        ]);

        $reply->load('user');

        return response()->json([
            'message' => 'تم إضافة الرد بنجاح',
            'reply' => $reply,
        ], 201);
    }
}
