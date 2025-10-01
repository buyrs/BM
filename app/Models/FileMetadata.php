<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class FileMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'size',
        'mime_type',
        'file_hash',
        'metadata',
        'property_id',
        'mission_id',
        'checklist_id',
        'uploaded_by',
        'storage_disk',
        'is_public',
        'last_accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_public' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the property that owns the file.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the mission that owns the file.
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * Get the checklist that owns the file.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->storage_disk)->url($this->path);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the file exists in storage.
     */
    public function exists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->path);
    }

    /**
     * Delete the file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->storage_disk)->delete($this->path);
        }
        return true;
    }

    /**
     * Update last accessed timestamp.
     */
    public function markAsAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }
}
