<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingImage extends Model
{
    use HasFactory;

    /**
     * Get the image's building.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
