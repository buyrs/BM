<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'channels',
        'mission_id',
        'checklist_id',
        'priority',
        'requires_action',
        'action_taken_at',
        'action_taken_by',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'read_at' => 'datetime',
        'action_taken_at' => 'datetime',
        'requires_action' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function actionTakenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_taken_by');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markActionTaken(User $user): void
    {
        $this->update([
            'action_taken_at' => now(),
            'action_taken_by' => $user->id,
        ]);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isActionTaken(): bool
    {
        return !is_null($this->action_taken_at);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRequiringAction($query)
    {
        return $query->where('requires_action', true)
                    ->whereNull('action_taken_at');
    }
}
