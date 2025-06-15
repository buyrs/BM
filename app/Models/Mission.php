<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'scheduled_at',
        'address',
        'tenant_name',
        'tenant_phone',
        'tenant_email',
        'notes',
        'agent_id',
        'status'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function scopeUnassigned($query)
    {
        return $query->where('status', 'unassigned');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}