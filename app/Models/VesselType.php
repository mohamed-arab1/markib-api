<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VesselType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_ar', 'capacity', 'description'];

    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class);
    }
}
