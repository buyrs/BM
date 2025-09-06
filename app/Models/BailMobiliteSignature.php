<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BailMobiliteSignature extends Model
{
    use HasFactory, HasEncryptedAttributes;

    protected $fillable = [
        'bail_mobilite_id',
        'signature_type',
        'contract_template_id',
        'tenant_signature',
        'tenant_signed_at',
        'contract_pdf_path',
        'signature_metadata',
        'tenant_signature_encryption_metadata',
        'signature_metadata_encryption_metadata',
        'additional_signatures',
        'signature_status',
        'signature_workflow_history',
        'workflow_started_at',
        'workflow_completed_at'
    ];

    protected $casts = [
        'tenant_signed_at' => 'datetime',
        'signature_metadata' => 'array',
        'tenant_signature_encryption_metadata' => 'array',
        'signature_metadata_encryption_metadata' => 'array',
        'additional_signatures' => 'array',
        'signature_workflow_history' => 'array',
        'workflow_started_at' => 'datetime',
        'workflow_completed_at' => 'datetime'
    ];

    /**
     * The attributes that should be encrypted
     */
    protected $encrypted = [
        'tenant_signature',
        'signature_metadata'
    ];

    /**
     * The attributes that should be searchable while encrypted
     */
    protected $searchableEncrypted = [];

    /**
     * Get the bail mobilité that owns this signature.
     */
    public function bailMobilite(): BelongsTo
    {
        return $this->belongsTo(BailMobilite::class);
    }

    /**
     * Get the contract template used for this signature.
     */
    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    /**
     * Get the signature invitations for this signature.
     */
    public function signatureInvitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SignatureInvitation::class);
    }

    /**
     * Get the workflow steps for this signature's contract template.
     */
    public function workflowSteps(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            SignatureWorkflowStep::class,
            ContractTemplate::class,
            'id', // Foreign key on ContractTemplate table
            'contract_template_id', // Foreign key on SignatureWorkflowStep table
            'contract_template_id', // Local key on BailMobiliteSignature table
            'id' // Local key on ContractTemplate table
        );
    }

    /**
     * Check if the signature is complete (both admin and tenant signed).
     */
    public function isComplete(): bool
    {
        // For multi-party signatures, check if all required parties have signed
        if ($this->contractTemplate->requires_multi_party) {
            return $this->isMultiPartyComplete();
        }

        // For single-party signatures (original behavior)
        return !empty($this->tenant_signature) && 
               !is_null($this->tenant_signed_at) &&
               !empty($this->contractTemplate->admin_signature) &&
               !is_null($this->contractTemplate->admin_signed_at);
    }

    /**
     * Check if multi-party signature workflow is complete.
     */
    public function isMultiPartyComplete(): bool
    {
        if (!$this->contractTemplate->requires_multi_party) {
            return false;
        }

        // Check if all required workflow steps are completed
        $requiredSteps = $this->workflowSteps()
            ->where('is_required', true)
            ->get();

        foreach ($requiredSteps as $step) {
            $invitation = $this->signatureInvitations()
                ->where('signature_party_id', $step->signature_party_id)
                ->where('status', 'completed')
                ->first();

            if (!$invitation) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the current workflow step for this signature.
     */
    public function getCurrentWorkflowStep(): ?SignatureWorkflowStep
    {
        if (!$this->contractTemplate->requires_multi_party) {
            return null;
        }

        // Find the first incomplete step in order
        $steps = $this->workflowSteps()->orderBy('order')->get();

        foreach ($steps as $step) {
            $invitation = $this->signatureInvitations()
                ->where('signature_party_id', $step->signature_party_id)
                ->where('status', 'completed')
                ->first();

            if (!$invitation) {
                return $step;
            }
        }

        return null; // All steps completed
    }

    /**
     * Get the completion percentage for multi-party signatures.
     */
    public function getCompletionPercentage(): int
    {
        if (!$this->contractTemplate->requires_multi_party) {
            return $this->isComplete() ? 100 : 0;
        }

        $totalSteps = $this->workflowSteps()->count();
        $completedSteps = $this->signatureInvitations()
            ->where('status', 'completed')
            ->count();

        if ($totalSteps === 0) {
            return 0;
        }

        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Start the multi-party signature workflow.
     */
    public function startWorkflow(): bool
    {
        if (!$this->contractTemplate->requires_multi_party) {
            return false;
        }

        return $this->update([
            'signature_status' => 'in_progress',
            'workflow_started_at' => now(),
            'signature_workflow_history' => array_merge($this->signature_workflow_history ?? [], [
                'workflow_started' => now()->toISOString()
            ])
        ]);
    }

    /**
     * Complete the multi-party signature workflow.
     */
    public function completeWorkflow(): bool
    {
        if (!$this->contractTemplate->requires_multi_party) {
            return false;
        }

        return $this->update([
            'signature_status' => 'completed',
            'workflow_completed_at' => now(),
            'signature_workflow_history' => array_merge($this->signature_workflow_history ?? [], [
                'workflow_completed' => now()->toISOString()
            ])
        ]);
    }

    /**
     * Get all completed signatures for this workflow.
     */
    public function getCompletedSignatures(): array
    {
        $signatures = [];

        // Tenant signature
        if ($this->isTenantSigned()) {
            $signatures[] = [
                'party' => 'tenant',
                'name' => $this->bailMobilite->tenant_name,
                'signed_at' => $this->tenant_signed_at,
                'signature_type' => 'electronic'
            ];
        }

        // Admin signature from template
        if ($this->isAdminSigned()) {
            $signatures[] = [
                'party' => 'admin',
                'name' => $this->contractTemplate->creator->name ?? 'Administrator',
                'signed_at' => $this->contractTemplate->admin_signed_at,
                'signature_type' => 'electronic'
            ];
        }

        // Additional parties from invitations
        $completedInvitations = $this->signatureInvitations()
            ->where('status', 'completed')
            ->with('signatureParty')
            ->get();

        foreach ($completedInvitations as $invitation) {
            $signatures[] = [
                'party' => $invitation->signatureParty->role,
                'name' => $invitation->signatureParty->name,
                'signed_at' => $invitation->completed_at,
                'signature_type' => $invitation->signatureParty->signature_method,
                'invitation_id' => $invitation->id
            ];
        }

        return $signatures;
    }

    /**
     * Check if the tenant has signed.
     */
    public function isTenantSigned(): bool
    {
        return !empty($this->tenant_signature) && !is_null($this->tenant_signed_at);
    }

    /**
     * Check if the admin has signed the contract template.
     */
    public function isAdminSigned(): bool
    {
        return $this->contractTemplate && $this->contractTemplate->isSignedByAdmin();
    }

    /**
     * Check if the contract PDF has been generated.
     */
    public function hasPdfGenerated(): bool
    {
        return !empty($this->contract_pdf_path);
    }

    /**
     * Get the signature validation status.
     */
    public function getValidationStatus(): array
    {
        return [
            'tenant_signed' => $this->isTenantSigned(),
            'admin_signed' => $this->isAdminSigned(),
            'pdf_generated' => $this->hasPdfGenerated(),
            'complete' => $this->isComplete()
        ];
    }

    /**
     * Scope to get signatures by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('signature_type', $type);
    }

    /**
     * Scope to get entry signatures.
     */
    public function scopeEntry($query)
    {
        return $query->where('signature_type', 'entry');
    }

    /**
     * Scope to get exit signatures.
     */
    public function scopeExit($query)
    {
        return $query->where('signature_type', 'exit');
    }

    /**
     * Scope to get complete signatures.
     */
    public function scopeComplete($query)
    {
        return $query->whereNotNull('tenant_signature')
                    ->whereNotNull('tenant_signed_at')
                    ->whereHas('contractTemplate', function ($q) {
                        $q->whereNotNull('admin_signature')
                          ->whereNotNull('admin_signed_at');
                    });
    }

    /**
     * Scope to get signatures with generated PDFs.
     */
    public function scopeWithPdf($query)
    {
        return $query->whereNotNull('contract_pdf_path');
    }
}