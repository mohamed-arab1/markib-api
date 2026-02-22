<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trip = $this->booking->trip;

        $msg = (new MailMessage)
            ->subject('تأكيد حجز رحلة نيلية - ' . $this->booking->booking_reference)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تم تأكيد حجزك بنجاح.')
            ->line('رقم الحجز: ' . $this->booking->booking_reference);
        if ($this->booking->booked_for_name) {
            $msg->line('الحجز باسم: ' . $this->booking->booked_for_name);
        }
        return $msg
            ->line('التاريخ: ' . $trip->date->format('Y-m-d'))
            ->line('الوقت: ' . $trip->start_time->format('H:i'))
            ->line('نوع المركب: ' . $trip->vessel->name_ar)
            ->line('عدد المسافرين: ' . $this->booking->passengers_count)
            ->line('المبلغ: ' . $this->booking->payment->amount . ' جنيه مصري');
    }

    public function toArray(object $notifiable): array
    {
        $trip = $this->booking->trip;

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
            'trip_time' => $trip->start_time->format('H:i'),
            'message' => $message,
        ];
    }
}
