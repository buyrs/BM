<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_name',
        'owner_address', 
        'property_address',
        'property_type',
        'description',
    ];

    protected $casts = [
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
}
