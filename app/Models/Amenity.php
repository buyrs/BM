<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amenity extends Model
{
    protected $fillable = [
        'name',
        'amenity_type_id',
        'property_id',
    ];

    public function amenityType(): BelongsTo
    {
        return $this->belongsTo(AmenityType::class);
    }
}
