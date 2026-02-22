<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_id',
        'location_id',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'price',
        'total_seats',
        'available_seats',
        'notes',
        'status',
    ];

    protected $appends = ['reserved_seats', 'remaining_seats'];

    public function getReservedSeatsAttribute(): int
    {
        $total = (int) ($this->attributes['total_seats'] ?? 0);
        $remaining = (int) $this->available_seats;
        return max(0, $total - $remaining);
    }

    public function getRemainingSeatsAttribute(): int
    {
        return (int) $this->available_seats;
    }

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TripImage::class)->orderBy('sort_order');
    }

    public function coverImage()
    {
        return $this->hasOne(TripImage::class)->where('type', 'cover');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(TripImage::class)->where('type', 'gallery')->orderBy('sort_order');
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_trip');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'scheduled')
            ->where('date', '>=', now()->toDateString())
            ->where('available_seats', '>', 0);
    }
}
