<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(LocationImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(LocationImage::class)->where('is_primary', true);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
