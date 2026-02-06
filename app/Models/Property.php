<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'owner_name',
        'owner_address', 
        'property_address',
        'property_type',
        'description',
    ];

    protected $casts = [
        'internal_code' => 'string',
        'owner_name' => 'string',
        'owner_address' => 'string',
        'property_address' => 'string',
        'property_type' => 'string',
        'description' => 'string',
    ];

    /**
     * Get missions for this property
     */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'property_address', 'property_address');
    }

    /**
     * Get all favorites for this property.
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }

    /**
     * Check if the property is favorited by a user.
     */
    public function isFavoritedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->id)->exists();
    }
}

