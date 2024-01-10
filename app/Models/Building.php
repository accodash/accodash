<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperBuilding
 */
class Building extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'address',
        'main_image_url',
        'type_id',
        'city_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pending' => 'boolean',
    ];

    /**
     * Get the city that the building is located in.
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
     * Get the building's images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(BuildingImage::class, "building_images");
    }

    /**
     * Get the amenities that the building offer.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, "buildings_amenities");
    }

    /**
     * Get the building's reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the building's accommodations.
     */
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }

    /**
     * Get the original building (if the building has an edit suggestion.)
     */
    public function originalBuilding(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the building's creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
