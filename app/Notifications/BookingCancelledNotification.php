<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public bool $forStaff = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $trip = $this->booking->trip;
        $user = $this->booking->user;

        if ($this->forStaff) {
            return [
                'type' => 'booking_cancelled_staff',
                'message' => 'تم إلغاء حجز من قبل ' . ($user?->name ?? 'عميل') . ' - رقم الحجز: ' . $this->booking->booking_reference,
                'booking_id' => $this->booking->id,
                'booking_reference' => $this->booking->booking_reference,
                'user_name' => $user?->name,
                'trip_date' => $trip?->date?->toDateString(),
                'cancellation_reason' => $this->booking->cancellation_reason,
            ];
        }

        return [
            'type' => 'booking_cancelled',
            'message' => 'تم إلغاء حجزك بنجاح. رقم الحجز: ' . $this->booking->booking_reference,
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'trip_date' => $trip?->date?->toDateString(),
        ];
    }
}
