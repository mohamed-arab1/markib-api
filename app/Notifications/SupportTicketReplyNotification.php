<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public ChatMessage $message,
        public bool $replyByStaff
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $sender = $this->message->user;
        $senderName = $sender?->name ?? 'نظام';

        if ($this->replyByStaff) {
            return [
                'type' => 'support_ticket_reply',
                'message' => 'رد جديد من الدعم على تذكرتك: ' . $this->ticket->subject,
                'ticket_id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'reply_preview' => \Str::limit($this->message->message, 80),
            ];
        }

        return [
            'type' => 'support_ticket_customer_reply',
            'message' => 'رد جديد من العميل في تذكرة: ' . $this->ticket->subject . ' (' . $this->ticket->ticket_number . ')',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'user_name' => $senderName,
            'reply_preview' => \Str::limit($this->message->message, 80),
        ];
    }
}
