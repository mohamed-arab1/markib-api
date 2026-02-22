<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
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

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
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
