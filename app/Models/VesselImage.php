<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_id',
        'path',
        'url',
        'alt_text',
        'type',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function scopeCover($query)
    {
        return $query->where('type', 'cover');
    }

    public function scopeGallery($query)
    {
        return $query->where('type', 'gallery');
    }
}
