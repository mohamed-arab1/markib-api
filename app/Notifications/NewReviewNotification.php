<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $user = $this->review->user;
        $trip = $this->review->trip;

        return [
            'type' => 'new_review',
            'message' => 'تقييم جديد من ' . ($user?->name ?? 'عميل') . ' - ' . ($trip?->vessel?->name_ar ?? 'رحلة'),
            'review_id' => $this->review->id,
            'trip_id' => $this->review->trip_id,
            'user_name' => $user?->name,
            'rating' => $this->review->rating,
            'comment_preview' => \Str::limit($this->review->comment ?? '', 60),
        ];
    }
}
