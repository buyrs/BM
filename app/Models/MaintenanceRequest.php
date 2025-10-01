<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_id',
        'checklist_id',
        'checklist_item_id',
        'reported_by',
        'assigned_to',
        'priority',
        'description',
        'status',
        'estimated_cost',
        'completed_at',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'completed_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'assigned_to' => $user->id,
        ]);
    }

    public function reject(string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'notes' => $reason,
        ]);
    }

    public function startWork(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete(string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, User $user)
    {
        return $query->where('assigned_to', $user->id);
    }
}
