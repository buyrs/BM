<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    // Notification types constants
    const TYPE_EXIT_REMINDER = 'exit_reminder';
    const TYPE_CHECKLIST_VALIDATION = 'checklist_validation';
    const TYPE_INCIDENT_ALERT = 'incident_alert';
    const TYPE_MISSION_ASSIGNED = 'mission_assigned';

    protected $fillable = [
        'type',
        'recipient_id',
        'bail_mobilite_id',
        'scheduled_at',
        'sent_at',
        'status',
        'data'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'data' => 'array'
    ];

    /**
     * Get the user who should receive this notification.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the bail mobilité associated with this notification.
     */
    public function bailMobilite(): BelongsTo
    {
        return $this->belongsTo(BailMobilite::class);
    }

    /**
     * Scope to get pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get cancelled notifications.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get notifications by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get notifications for a specific recipient.
     */
    public function scopeForRecipient($query, int $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Scope to get notifications scheduled for sending.
     */
    public function scopeScheduledForSending($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to get exit reminder notifications.
     */
    public function scopeExitReminders($query)
    {
        return $query->where('type', self::TYPE_EXIT_REMINDER);
    }

    /**
     * Scope to get checklist validation notifications.
     */
    public function scopeChecklistValidations($query)
    {
        return $query->where('type', self::TYPE_CHECKLIST_VALIDATION);
    }

    /**
     * Scope to get incident alert notifications.
     */
    public function scopeIncidentAlerts($query)
    {
        return $query->where('type', self::TYPE_INCIDENT_ALERT);
    }

    /**
     * Scope to get mission assigned notifications.
     */
    public function scopeMissionAssigned($query)
    {
        return $query->where('type', self::TYPE_MISSION_ASSIGNED);
    }

    /**
     * Mark the notification as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Cancel the notification.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if the notification is ready to be sent.
     */
    public function isReadyToSend(): bool
    {
        return $this->status === 'pending' && 
               $this->scheduled_at <= now();
    }

    /**
     * Check if the notification has been sent.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent' && !is_null($this->sent_at);
    }

    /**
     * Check if the notification has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the notification message based on type and data.
     */
    public function getMessage(): string
    {
        switch ($this->type) {
            case self::TYPE_EXIT_REMINDER:
                return "Bail Mobilité se termine dans 10 jours - {$this->bailMobilite->tenant_name}";
            case self::TYPE_CHECKLIST_VALIDATION:
                return "Checklist à valider pour {$this->bailMobilite->tenant_name}";
            case self::TYPE_INCIDENT_ALERT:
                return "Incident détecté pour {$this->bailMobilite->tenant_name}";
            case self::TYPE_MISSION_ASSIGNED:
                return "Nouvelle mission assignée";
            default:
                return "Notification";
        }
    }
}