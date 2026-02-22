<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trip_id',
        'offer_id',
        'passengers_count',
        'booked_for_name',
        'booking_notes',
        'discount_amount',
        'status',
        'cancellation_reason',
        'refund_method',
        'cancelled_at',
        'booking_reference',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function offer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getSubtotalAmountAttribute(): float
    {
        return (float) $this->trip->price * $this->passengers_count;
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->subtotal_amount - (float) ($this->discount_amount ?? 0);
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'NB' . strtoupper(substr(uniqid(), -8));
        } while (self::where('booking_reference', $ref)->exists());

        return $ref;
    }
}
