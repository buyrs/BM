<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'content',
        'admin_signature',
        'admin_signed_at',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'admin_signed_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user who created this contract template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all signatures that used this contract template.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(BailMobiliteSignature::class);
    }

    /**
     * Scope to get only active contract templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only entry contract templates.
     */
    public function scopeEntry($query)
    {
        return $query->where('type', 'entry');
    }

    /**
     * Scope to get only exit contract templates.
     */
    public function scopeExit($query)
    {
        return $query->where('type', 'exit');
    }

    /**
     * Check if the contract template is signed by admin.
     */
    public function isSignedByAdmin(): bool
    {
        return !empty($this->admin_signature) && !is_null($this->admin_signed_at);
    }

    /**
     * Check if the contract template is ready for use.
     */
    public function isReadyForUse(): bool
    {
        return $this->is_active && $this->isSignedByAdmin();
    }
}