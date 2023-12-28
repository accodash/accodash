<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingType extends Model
{
    use HasFactory;

    /**
     * Get the buildings of that type.
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }
}
