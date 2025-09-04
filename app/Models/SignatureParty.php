<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SignatureParty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'signature_method',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * The roles that signature parties can have
     */
    public const ROLES = [
        'landlord',
        'agent',
        'witness',
        'notary',
        'legal_representative',
        'property_manager',
        'co_tenant'
    ];

    /**
     * Get the contract templates that require this signature party
     */
    public function contractTemplates(): BelongsToMany
    {
        return $this->belongsToMany(ContractTemplate::class, 'signature_workflow_steps')
            ->withPivot(['order', 'is_required', 'timeout_hours', 'validation_rules', 'notification_settings'])
            ->withTimestamps();
    }

    /**
     * Get the workflow steps for this signature party
     */
    public function workflowSteps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SignatureWorkflowStep::class);
    }

    /**
     * Get the signature invitations for this party
     */
    public function signatureInvitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SignatureInvitation::class);
    }

    /**
     * Scope to get active signature parties
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get signature parties by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Check if the role is valid
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ROLES);
    }

    /**
     * Get display name with role
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->role})";
    }

    /**
     * Check if this party can sign electronically
     */
    public function canSignElectronically(): bool
    {
        return $this->signature_method === 'electronic';
    }

    /**
     * Get notification settings for a specific contract template
     */
    public function getNotificationSettingsForTemplate(ContractTemplate $template): array
    {
        $step = $this->workflowSteps()
            ->where('contract_template_id', $template->id)
            ->first();

        return $step->notification_settings ?? [
            'email' => true,
            'sms' => false,
            'reminder_days' => [1, 3, 7],
            'escalation_after_days' => 7
        ];
    }
}