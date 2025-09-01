<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BailMobiliteSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'bail_mobilite_id',
        'signature_type',
        'contract_template_id',
        'tenant_signature',
        'tenant_signed_at',
        'contract_pdf_path'
    ];

    protected $casts = [
        'tenant_signed_at' => 'datetime'
    ];

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
     * Check if the signature is complete (both admin and tenant signed).
     */
    public function isComplete(): bool
    {
        return !empty($this->tenant_signature) && 
               !is_null($this->tenant_signed_at) &&
               !empty($this->contractTemplate->admin_signature) &&
               !is_null($this->contractTemplate->admin_signed_at);
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