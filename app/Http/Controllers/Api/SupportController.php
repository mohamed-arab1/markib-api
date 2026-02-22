<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketReplyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class SupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // تذاكر الدعم والمحادثات الخاصة بالعميل فقط
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->with(['assignedTo', 'latestMessage'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['sometimes', 'in:booking,payment,technical,complaint,suggestion,other'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'other',
            'priority' => $validated['priority'] ?? 'medium',
        ]);

        // Create initial message from description
        ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['description'],
        ]);

        Notification::send(User::staff()->get(), new NewSupportTicketNotification($ticket));

        return response()->json([
            'message' => 'تم إنشاء تذكرة الدعم بنجاح - يمكنك متابعة المحادثة من هنا',
            'ticket' => $ticket->load('messages.user'),
        ], 201);
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        if ($ticket->user_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $ticket->load(['assignedTo', 'messages.user']);

        // اعتبار رسائل فريق الدعم مقروءة عند فتح التذكرة (المحادثة)
        $ticket->messages()
            ->where('user_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json($ticket);
    }

    public function sendMessage(Request $request, SupportTicket $ticket): JsonResponse
    {
        if ($ticket->user_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if (in_array($ticket->status, ['resolved', 'closed'])) {
            return response()->json(['message' => 'لا يمكن الرد على تذكرة مغلقة'], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $message = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        // Update status if waiting for customer
        if ($ticket->status === 'waiting_customer') {
            $ticket->update(['status' => 'open']);
        }

        Notification::send(User::staff()->get(), new SupportTicketReplyNotification($ticket->fresh(), $message, false));

        return response()->json([
            'message' => 'تم إرسال الرسالة في المحادثة',
            'chat_message' => $message->load('user'),
        ]);
    }

    public function close(SupportTicket $ticket): JsonResponse
    {
        if ($ticket->user_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json(['message' => 'تم إغلاق التذكرة']);
    }

    public function unreadCount(): JsonResponse
    {
        $count = ChatMessage::whereHas('ticket', function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->where('user_id', '!=', auth()->id())
        ->where('is_read', false)
        ->count();

        return response()->json(['unread_count' => $count]);
    }
}
