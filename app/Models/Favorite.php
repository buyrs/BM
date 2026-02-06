<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favorable_type',
        'favorable_id',
    ];

    /**
     * Get the user who created the favorite.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the favorable model (Property, Mission, etc.).
     */
    public function favorable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get favorites for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to get favorites of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('favorable_type', $type);
    }
}
