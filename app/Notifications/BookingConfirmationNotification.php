<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $trip = $this->booking->trip;

        $startTime = $trip->start_time;
        $formattedTime = $startTime instanceof \Carbon\Carbon
            ? $startTime->format('H:i')
            : (is_string($startTime) ? substr($startTime, 0, 5) : '');

        $message = 'تم تأكيد حجزك للرحلة في ' . $trip->date->format('Y-m-d');
        if ($this->booking->booked_for_name) {
            $message .= ' - الحجز باسم: ' . $this->booking->booked_for_name;
        }
        return [
            'type' => 'booking_confirmation',
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'booked_for_name' => $this->booking->booked_for_name,
            'trip_date' => $trip->date->toDateString(),
            'trip_time' => $formattedTime,
            'message' => $message,
        ];
    }
}
