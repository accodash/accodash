<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory;

    /**
     * Get the city that the building is placed in.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the type of the building.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }

    /**
     * Get the type of the building.
     */
    public function images(): HasMany
    {
        return $this->hasMany(BuildingImage::class);
    }
}