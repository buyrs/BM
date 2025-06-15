<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'category',
        'item_name',
        'condition',
        'comment'
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ChecklistPhoto::class);
    }
}