<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QAReview extends Model
{
    protected $table = 'qa_reviews';

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'reviewer_id',
        'status',
        'score',
        'verification_data',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'verification_data' => 'array',
        'score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Possible statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FLAGGED = 'flagged';

    /**
     * Get the reviewable entity (Mission, ChecklistItem, etc).
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Scope: pending reviews.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: needs attention (pending or flagged).
     */
    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_FLAGGED]);
    }

    /**
     * Scope: by score threshold.
     */
    public function scopeLowScore($query, int $threshold = 70)
    {
        return $query->where('score', '<', $threshold);
    }

    /**
     * Mark as approved.
     */
    public function approve(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewer_id' => $reviewerId,
            'notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function reject(int $reviewerId, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewer_id' => $reviewerId,
            'notes' => $reason,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Flag for further investigation.
     */
    public function flag(int $reviewerId, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_FLAGGED,
            'reviewer_id' => $reviewerId,
            'notes' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}
