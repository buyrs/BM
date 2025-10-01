<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'amenity_id',
        'state',
        'comment',
        'photo_path',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }
}
