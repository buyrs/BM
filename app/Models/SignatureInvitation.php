<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignatureInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bail_mobilite_signature_id',
        'signature_party_id',
        'token',
        'status',
        'sent_at',
        'expires_at',
        'completed_at',
        'delivery_metadata',
        'signature_data'
    ];

    protected $casts = [
        'delivery_metadata' => 'array',
        'signature_data' => 'array',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Status values for signature invitations
     */
    public const STATUSES = [
        'pending',
        'sent',
        'delivered',
        'opened',
        'completed',
        'expired',
        'cancelled',
        'failed'
    ];

    /**
     * Get the bail mobilite signature for this invitation
     */
    public function bailMobiliteSignature(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BailMobiliteSignature::class);
    }

    /**
     * Get the signature party for this invitation
     */
    public function signatureParty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SignatureParty::class);
    }

    /**
     * Get the workflow step for this invitation
     */
    public function workflowStep(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SignatureWorkflowStep::class, 'signature_party_id', 'signature_party_id')
            ->where('contract_template_id', function ($query) {
                $query->select('contract_template_id')
                    ->from('bail_mobilite_signatures')
                    ->whereColumn('id', 'signature_invitations.bail_mobilite_signature_id');
            });
    }

    /**
     * Check if the invitation is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the invitation has been sent
     */
    public function isSent(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'opened']);
    }

    /**
     * Check if the invitation has been completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the invitation has expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if the invitation can be resent
     */
    public function canBeResent(): bool
    {
        return !$this->isCompleted() && !$this->isExpired();
    }

    /**
     * Get the signature URL for this invitation
     */
    public function getSignatureUrl(): string
    {
        return route('signatures.invitation', [
            'token' => $this->token,
            'invitation' => $this->id
        ]);
    }

    /**
     * Get the tracking URL for this invitation
     */
    public function getTrackingUrl(): string
    {
        return route('signatures.track', [
            'token' => $this->token
        ]);
    }

    /**
     * Mark the invitation as sent
     */
    public function markAsSent(array $deliveryMetadata = []): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'delivery_metadata' => array_merge($this->delivery_metadata ?? [], $deliveryMetadata)
        ]);
    }

    /**
     * Mark the invitation as delivered
     */
    public function markAsDelivered(array $deliveryMetadata = []): bool
    {
        return $this->update([
            'status' => 'delivered',
            'delivery_metadata' => array_merge($this->delivery_metadata ?? [], $deliveryMetadata)
        ]);
    }

    /**
     * Mark the invitation as opened
     */
    public function markAsOpened(array $viewMetadata = []): bool
    {
        return $this->update([
            'status' => 'opened',
            'delivery_metadata' => array_merge($this->delivery_metadata ?? [], [
                'opened_at' => now()->toISOString(),
                'opened_metadata' => $viewMetadata
            ])
        ]);
    }

    /**
     * Mark the invitation as completed with signature data
     */
    public function markAsCompleted(array $signatureData): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'signature_data' => $signatureData
        ]);
    }

    /**
     * Mark the invitation as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => 'expired'
        ]);
    }

    /**
     * Get the time taken to complete the invitation
     */
    public function getCompletionTime(): ?string
    {
        if (!$this->sent_at || !$this->completed_at) {
            return null;
        }

        return $this->sent_at->diffForHumans($this->completed_at, true);
    }

    /**
     * Check if the signature data is valid for this invitation
     */
    public function validateSignatureData(array $signatureData): array
    {
        $errors = [];
        $workflowStep = $this->workflowStep;

        if ($workflowStep) {
            $errors = $workflowStep->validateSignatureData($signatureData);
        }

        // Additional validation for invitation-specific rules
        if ($this->isExpired()) {
            $errors[] = 'Signature invitation has expired';
        }

        if ($this->isCompleted()) {
            $errors[] = 'Signature invitation has already been completed';
        }

        return $errors;
    }

    /**
     * Generate a new secure token for the invitation
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Find an invitation by token
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereNotIn('status', ['expired', 'cancelled', 'completed'])
            ->first();
    }
}