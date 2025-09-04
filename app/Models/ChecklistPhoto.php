<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChecklistPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_item_id',
        'photo_path',
        'filename',
        'original_name',
        'file_size',
        'mime_type',
        'uploaded_by'
    ];

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}