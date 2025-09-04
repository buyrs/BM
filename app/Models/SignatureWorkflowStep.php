<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignatureWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_template_id',
        'signature_party_id',
        'order',
        'is_required',
        'timeout_hours',
        'validation_rules',
        'notification_settings'
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'notification_settings' => 'array',
        'is_required' => 'boolean'
    ];

    /**
     * Get the contract template for this workflow step
     */
    public function contractTemplate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    /**
     * Get the signature party for this workflow step
     */
    public function signatureParty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SignatureParty::class);
    }

    /**
     * Get the signature invitations for this workflow step
     */
    public function signatureInvitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SignatureInvitation::class, 'signature_party_id', 'signature_party_id')
            ->whereHas('bailMobiliteSignature', function ($query) {
                $query->where('contract_template_id', $this->contract_template_id);
            });
    }

    /**
     * Check if this step is currently active in a signature workflow
     */
    public function isActiveInWorkflow(BailMobiliteSignature $signature): bool
    {
        $currentStep = $signature->getCurrentWorkflowStep();
        return $currentStep && $currentStep->id === $this->id;
    }

    /**
     * Get next workflow step
     */
    public function getNextStep(): ?self
    {
        return self::where('contract_template_id', $this->contract_template_id)
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();
    }

    /**
     * Get previous workflow step
     */
    public function getPreviousStep(): ?self
    {
        return self::where('contract_template_id', $this->contract_template_id)
            ->where('order', '<', $this->order)
            ->orderByDesc('order')
            ->first();
    }

    /**
     * Check if this is the first step in the workflow
     */
    public function isFirstStep(): bool
    {
        return $this->order === self::where('contract_template_id', $this->contract_template_id)
            ->min('order');
    }

    /**
     * Check if this is the last step in the workflow
     */
    public function isLastStep(): bool
    {
        return $this->order === self::where('contract_template_id', $this->contract_template_id)
            ->max('order');
    }

    /**
     * Get timeout datetime for this step
     */
    public function getTimeoutAt(): ?\Carbon\Carbon
    {
        if (!$this->timeout_hours) {
            return null;
        }

        return now()->addHours($this->timeout_hours);
    }

    /**
     * Check if a signature invitation has expired for this step
     */
    public function hasInvitationExpired(SignatureInvitation $invitation): bool
    {
        if (!$this->timeout_hours || !$invitation->sent_at) {
            return false;
        }

        return $invitation->sent_at->addHours($this->timeout_hours)->isPast();
    }

    /**
     * Validate signature data against this step's validation rules
     */
    public function validateSignatureData(array $signatureData): array
    {
        $errors = [];
        $rules = $this->validation_rules ?? [];

        // Basic validation rules
        if (in_array('signature_required', $rules) && empty($signatureData['signature'])) {
            $errors[] = 'Signature is required';
        }

        if (in_array('timestamp_required', $rules) && empty($signatureData['timestamp'])) {
            $errors[] = 'Timestamp is required';
        }

        if (in_array('ip_address_required', $rules) && empty($signatureData['ip_address'])) {
            $errors[] = 'IP address is required';
        }

        // Custom validation based on signature party role
        switch ($this->signatureParty->role) {
            case 'landlord':
                if (in_array('landlord_license_required', $rules) && empty($signatureData['license_number'])) {
                    $errors[] = 'Landlord license number is required';
                }
                break;
            
            case 'notary':
                if (in_array('notary_seal_required', $rules) && empty($signatureData['seal_data'])) {
                    $errors[] = 'Notary seal is required';
                }
                break;
        }

        return $errors;
    }
}