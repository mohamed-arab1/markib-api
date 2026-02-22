<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketReplyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['user', 'assignedTo', 'latestMessage']);

        // فريق الدعم يرى فقط التذاكر المعينة له أو غير المعينة (ليأخذها)
        if (auth()->user()->role === 'support') {
            $query->where(function ($q) {
                $q->where('assigned_to', auth()->id())->orWhereNull('assigned_to');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // ترتيب حسب الأولوية (عاجل أولاً) - يعمل على SQLite و MySQL
        $priorityOrder = "CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END";
        $tickets = $query->orderByRaw($priorityOrder)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($tickets);
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        // فريق الدعم يمكنه فتح التذكرة فقط إذا كانت معينة له أو غير معينة
        if (auth()->user()->role === 'support') {
            if ($ticket->assigned_to !== null && $ticket->assigned_to !== auth()->id()) {
                return response()->json(['message' => 'غير مصرح - التذكرة معينة لموظف آخر'], 403);
            }
        }

        $ticket->load(['user', 'assignedTo', 'messages.user']);

        // Mark messages as read (رسائل المحادثة تُعتبر مقروءة عند فتح التذكرة)
        $ticket->messages()
            ->where('user_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // استجابة موحدة: تذكرة الدعم + المحادثة (الرسائل)
        return response()->json($ticket);
    }

    public function assign(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        // فريق الدعم يمكنه تعيين التذكرة لنفسه فقط (أخذ التذكرة)
        if (auth()->user()->role === 'support') {
            if ((int) $validated['assigned_to'] !== auth()->id()) {
                return response()->json(['message' => 'غير مصرح - يمكنك أخذ التذكرة لنفسك فقط'], 403);
            }
        }

        $ticket->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'in_progress',
        ]);

        return response()->json([
            'message' => 'تم تعيين التذكرة',
            'ticket' => $ticket->fresh(['assignedTo']),
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket): JsonResponse
    {
        if (auth()->user()->role === 'support') {
            if ($ticket->assigned_to !== null && $ticket->assigned_to !== auth()->id()) {
                return response()->json(['message' => 'غير مصرح - التذكرة معينة لموظف آخر'], 403);
            }
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,waiting_customer,resolved,closed'],
        ]);

        $data = ['status' => $validated['status']];

        if ($validated['status'] === 'resolved') {
            $data['resolved_at'] = now();
        } elseif ($validated['status'] === 'closed') {
            $data['closed_at'] = now();
        }

        $ticket->update($data);

        return response()->json([
            'message' => 'تم تحديث حالة التذكرة',
            'ticket' => $ticket->fresh(),
        ]);
    }

    public function sendMessage(Request $request, SupportTicket $ticket): JsonResponse
    {
        // فريق الدعم يرد فقط على التذاكر المعينة له أو غير المعينة (ويتم تعيينها له تلقائياً عند أول رد)
        if (auth()->user()->role === 'support') {
            if ($ticket->assigned_to !== null && $ticket->assigned_to !== auth()->id()) {
                return response()->json(['message' => 'غير مصرح - التذكرة معينة لموظف آخر'], 403);
            }
            // إذا كانت التذكرة غير معينة، تعيينها لفريق الدعم الحالي عند أول رد
            if ($ticket->assigned_to === null) {
                $ticket->update(['assigned_to' => auth()->id(), 'status' => 'in_progress']);
            }
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $message = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        // Update ticket status if it was waiting for customer
        if ($ticket->status === 'waiting_customer') {
            $ticket->update(['status' => 'in_progress']);
        }

        $ticket->user?->notify(new SupportTicketReplyNotification($ticket, $message, true));

        return response()->json([
            'message' => 'تم إرسال الرسالة في المحادثة',
            'chat_message' => $message->load('user'),
        ]);
    }

    public function stats(): JsonResponse
    {
        $scope = SupportTicket::query();
        if (auth()->user()->role === 'support') {
            $scope->where(function ($q) {
                $q->where('assigned_to', auth()->id())->orWhereNull('assigned_to');
            });
        }

        $stats = [
            'total' => (clone $scope)->count(),
            'open' => (clone $scope)->where('status', 'open')->count(),
            'in_progress' => (clone $scope)->where('status', 'in_progress')->count(),
            'waiting_customer' => (clone $scope)->where('status', 'waiting_customer')->count(),
            'resolved' => (clone $scope)->where('status', 'resolved')->count(),
            'closed' => (clone $scope)->where('status', 'closed')->count(),
            'urgent' => (clone $scope)->where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
            'label' => 'تذاكر الدعم والمحادثات',
        ];

        return response()->json($stats);
    }
}
