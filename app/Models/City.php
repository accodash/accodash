<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    /**
     * Get the country that the city is placed in.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
