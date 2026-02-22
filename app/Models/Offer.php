<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'applicable_days',
        'applies_to',
        'min_booking_amount',
        'max_discount_amount',
        'min_passengers',
        'promo_code',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_value' => 'decimal:2',
        'min_booking_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'applicable_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class, 'offer_trip');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * تحقق إذا العرض ينطبق على يوم معين (من الأسبوع)
     */
    public function appliesToDay(string $dayOfWeek): bool
    {
        if ($this->applicable_days === null || empty($this->applicable_days)) {
            return true;
        }
        return in_array(strtolower($dayOfWeek), array_map('strtolower', $this->applicable_days), true);
    }

    /**
     * تحقق إذا العرض ينطبق على رحلة معينة
     */
    public function appliesToTrip(Trip $trip): bool
    {
        if ($this->applies_to === 'all_trips') {
            return true;
        }
        return $this->trips()->where('trips.id', $trip->id)->exists();
    }

    /**
     * حساب مبلغ الخصم لسعر معين (قبل الضرب بعدد الركاب)
     */
    public function calculateDiscount(float $unitPrice, int $quantity = 1): float
    {
        // التحقق من الحد الأدنى لعدد الأشخاص
        if ($this->min_passengers !== null && $quantity < (int) $this->min_passengers) {
            return 0;
        }

        $subtotal = $unitPrice * $quantity;

        if ($this->min_booking_amount !== null && (float) $this->min_booking_amount > $subtotal) {
            return 0;
        }

        $discount = 0;
        if ($this->discount_type === 'percentage') {
            $discount = $subtotal * ((float) $this->discount_value / 100);
            if ($this->max_discount_amount !== null) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
        } else {
            $discount = (float) $this->discount_value * $quantity;
        }

        return round($discount, 2);
    }
}
