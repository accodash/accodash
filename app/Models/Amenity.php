<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasFactory;

    /**
     * Get the buildings that offer the amenity.
     */
    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class);
    }
}
