<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LocationImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $appends = ['url'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getUrlAttribute(): string
    {
        return '/storage/' . $this->path;
    }
}
