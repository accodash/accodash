<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BuildingList extends Model
{
    use HasFactory;

    /**
     * Get the list creator.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the buildings that are present on the list.
     */
    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
