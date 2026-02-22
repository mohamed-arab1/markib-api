<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vessel extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_type_id',
        'name',
        'name_ar',
        'capacity',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vesselType(): BelongsTo
    {
        return $this->belongsTo(VesselType::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VesselImage::class)->orderBy('sort_order');
    }

    public function coverImage()
    {
        return $this->hasOne(VesselImage::class)->where('type', 'cover');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(VesselImage::class)->where('type', 'gallery')->orderBy('sort_order');
    }
}
