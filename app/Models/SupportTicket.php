<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'ticket_number',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'ticket_id')->latestOfMany();
    }

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TK' . strtoupper(substr(uniqid(), -8));
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'booking' => 'حجوزات',
            'payment' => 'مدفوعات',
            'technical' => 'مشكلة تقنية',
            'complaint' => 'شكوى',
            'suggestion' => 'اقتراح',
            default => 'أخرى',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'urgent' => 'عاجلة',
            default => 'متوسطة',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'مفتوحة',
            'in_progress' => 'قيد المعالجة',
            'waiting_customer' => 'بانتظار العميل',
            'resolved' => 'تم الحل',
            'closed' => 'مغلقة',
            default => 'مفتوحة',
        };
    }
}
