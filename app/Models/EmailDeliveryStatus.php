<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailDeliveryStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'to_email',
        'subject',
        'status',
        'provider',
        'attempts',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_message',
        'provider_message_id',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Scope for failed emails
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending emails
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['sending', 'queued', 'retrying']);
    }

    /**
     * Scope for successfully sent emails
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Check if email can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && 
               $this->attempts < config('mail.max_retries', 3);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Mark as sent
     */
    public function markAsSent(string $providerMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }
}