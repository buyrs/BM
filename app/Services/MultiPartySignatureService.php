<?php

namespace App\Services;

use App\Models\BailMobiliteSignature;
use App\Models\ContractTemplate;
use App\Models\SignatureInvitation;
use App\Models\SignatureParty;
use App\Models\SignatureWorkflowStep;
use App\Services\NotificationService;
use App\Services\SignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MultiPartySignatureService
{
    public function __construct(
        private NotificationService $notificationService,
        private SignatureService $signatureService
    ) {}

    /**
     * Initialize multi-party signature workflow for a contract
     */
    public function initializeWorkflow(BailMobiliteSignature $signature): bool
    {
        if (!$signature->contractTemplate->requires_multi_party) {
            return false;
        }

        DB::beginTransaction();
        try {
            // Start the workflow
            $signature->startWorkflow();

            // Create invitations for all workflow steps
            $workflowSteps = $signature->workflowSteps()->orderBy('order')->get();
            
            foreach ($workflowSteps as $step) {
                $this->createSignatureInvitation($signature, $step);
            }

            // Send invitation for the first step
            $firstStep = $workflowSteps->first();
            if ($firstStep) {
                $this->sendInvitationForStep($signature, $firstStep);
            }

            DB::commit();

            Log::info('Multi-party signature workflow initialized', [
                'signature_id' => $signature->id,
                'bail_mobilite_id' => $signature->bail_mobilite_id,
                'workflow_steps' => $workflowSteps->count()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initialize multi-party signature workflow', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create signature invitation for a workflow step
     */
    private function createSignatureInvitation(BailMobiliteSignature $signature, SignatureWorkflowStep $step): SignatureInvitation
    {
        $invitation = SignatureInvitation::create([
            'bail_mobilite_signature_id' => $signature->id,
            'signature_party_id' => $step->signature_party_id,
            'token' => SignatureInvitation::generateToken(),
            'status' => 'pending',
            'expires_at' => $step->getTimeoutAt()
        ]);

        return $invitation;
    }

    /**
     * Send invitation for a specific workflow step
     */
    public function sendInvitationForStep(BailMobiliteSignature $signature, SignatureWorkflowStep $step): bool
    {
        $invitation = $signature->signatureInvitations()
            ->where('signature_party_id', $step->signature_party_id)
            ->first();

        if (!$invitation) {
            $invitation = $this->createSignatureInvitation($signature, $step);
        }

        $signatureParty = $step->signatureParty;
        $notificationSettings = $step->notification_settings ?? [];

        // Send email notification
        if ($notificationSettings['email'] ?? true) {
            $this->sendEmailInvitation($invitation, $signatureParty, $signature);
        }

        // Send SMS notification if configured
        if ($notificationSettings['sms'] ?? false && $signatureParty->phone) {
            $this->sendSmsInvitation($invitation, $signatureParty, $signature);
        }

        $invitation->markAsSent([
            'sent_via' => array_filter([
                'email' => $notificationSettings['email'] ?? true,
                'sms' => $notificationSettings['sms'] ?? false && $signatureParty->phone
            ]),
            'sent_timestamp' => now()->toISOString()
        ]);

        Log::info('Signature invitation sent', [
            'invitation_id' => $invitation->id,
            'signature_party' => $signatureParty->email,
            'signature_id' => $signature->id
        ]);

        return true;
    }

    /**
     * Send email invitation
     */
    private function sendEmailInvitation(SignatureInvitation $invitation, SignatureParty $party, BailMobiliteSignature $signature): void
    {
        $data = [
            'invitation' => $invitation,
            'party' => $party,
            'signature' => $signature,
            'bailMobilite' => $signature->bailMobilite,
            'contractTemplate' => $signature->contractTemplate,
            'signatureUrl' => $invitation->getSignatureUrl(),
            'expiresAt' => $invitation->expires_at
        ];

        $this->notificationService->sendSignatureInvitationEmail($party->email, $data);
    }

    /**
     * Send SMS invitation
     */
    private function sendSmsInvitation(SignatureInvitation $invitation, SignatureParty $party, BailMobiliteSignature $signature): void
    {
        $message = "You have been invited to sign a contract. Please check your email or visit: " . $invitation->getSignatureUrl();
        
        $this->notificationService->sendSms(
            $party->phone,
            $message,
            [
                'invitation_id' => $invitation->id,
                'signature_id' => $signature->id,
                'party_role' => $party->role
            ]
        );
    }

    /**
     * Process a completed signature invitation
     */
    public function processCompletedInvitation(SignatureInvitation $invitation, array $signatureData): bool
    {
        DB::beginTransaction();
        try {
            // Validate signature data
            $validationErrors = $invitation->validateSignatureData($signatureData);
            if (!empty($validationErrors)) {
                throw new \Exception('Signature validation failed: ' . implode(', ', $validationErrors));
            }

            // Mark invitation as completed
            $invitation->markAsCompleted($signatureData);

            // Update signature workflow history
            $signature = $invitation->bailMobiliteSignature;
            $history = $signature->signature_workflow_history ?? [];
            $history['completed_steps'] = array_merge($history['completed_steps'] ?? [], [
                [
                    'step_id' => $invitation->workflowStep->id,
                    'party_id' => $invitation->signature_party_id,
                    'completed_at' => now()->toISOString(),
                    'signature_method' => $invitation->signatureParty->signature_method
                ]
            ]);

            $signature->update(['signature_workflow_history' => $history]);

            // Check if workflow is complete
            if ($signature->isMultiPartyComplete()) {
                $signature->completeWorkflow();
                $this->onWorkflowCompletion($signature);
            } else {
                // Send invitation for next step
                $nextStep = $this->getNextPendingStep($signature);
                if ($nextStep) {
                    $this->sendInvitationForStep($signature, $nextStep);
                }
            }

            DB::commit();

            Log::info('Signature invitation completed successfully', [
                'invitation_id' => $invitation->id,
                'signature_id' => $signature->id,
                'party_role' => $invitation->signatureParty->role
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process completed signature invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the next pending workflow step
     */
    private function getNextPendingStep(BailMobiliteSignature $signature): ?SignatureWorkflowStep
    {
        $currentStep = $signature->getCurrentWorkflowStep();
        if (!$currentStep) {
            return null;
        }

        return $currentStep->getNextStep();
    }

    /**
     * Handle workflow completion
     */
    private function onWorkflowCompletion(BailMobiliteSignature $signature): void
    {
        // Generate final PDF with all signatures
        $this->signatureService->generateSignedContract($signature);

        // Send completion notifications
        $this->sendWorkflowCompletionNotifications($signature);

        // Log completion
        Log::info('Multi-party signature workflow completed', [
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'completed_at' => now()->toISOString()
        ]);
    }

    /**
     * Send notifications for workflow completion
     */
    private function sendWorkflowCompletionNotifications(BailMobiliteSignature $signature): void
    {
        $bailMobilite = $signature->bailMobilite;
        $contractTemplate = $signature->contractTemplate;

        // Notify Ops staff
        $this->notificationService->sendWorkflowCompletionNotification($signature);

        // Notify tenant
        if ($bailMobilite->tenant_email) {
            $this->notificationService->sendTenantCompletionNotification($bailMobilite, $signature);
        }

        // Notify all signing parties
        $completedInvitations = $signature->signatureInvitations()
            ->where('status', 'completed')
            ->with('signatureParty')
            ->get();

        foreach ($completedInvitations as $invitation) {
            $this->notificationService->sendPartyCompletionNotification($invitation, $signature);
        }
    }

    /**
     * Resend invitation for a specific step
     */
    public function resendInvitation(SignatureInvitation $invitation): bool
    {
        if (!$invitation->canBeResent()) {
            return false;
        }

        $signature = $invitation->bailMobiliteSignature;
        $step = $invitation->workflowStep;

        if (!$step) {
            return false;
        }

        return $this->sendInvitationForStep($signature, $step);
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(SignatureInvitation $invitation, string $reason): bool
    {
        return $invitation->update([
            'status' => 'cancelled',
            'delivery_metadata' => array_merge($invitation->delivery_metadata ?? [], [
                'cancelled_at' => now()->toISOString(),
                'cancellation_reason' => $reason
            ])
        ]);
    }

    /**
     * Get workflow status for a signature
     */
    public function getWorkflowStatus(BailMobiliteSignature $signature): array
    {
        $steps = $signature->workflowSteps()->orderBy('order')->get();
        $status = [
            'overall_status' => $signature->signature_status,
            'completion_percentage' => $signature->getCompletionPercentage(),
            'started_at' => $signature->workflow_started_at,
            'completed_at' => $signature->workflow_completed_at,
            'steps' => []
        ];

        foreach ($steps as $step) {
            $invitation = $signature->signatureInvitations()
                ->where('signature_party_id', $step->signature_party_id)
                ->first();

            $status['steps'][] = [
                'step_order' => $step->order,
                'party' => $step->signatureParty->toArray(),
                'is_required' => $step->is_required,
                'timeout_hours' => $step->timeout_hours,
                'invitation_status' => $invitation->status ?? 'not_sent',
                'invitation_sent_at' => $invitation->sent_at ?? null,
                'invitation_expires_at' => $invitation->expires_at ?? null,
                'invitation_completed_at' => $invitation->completed_at ?? null
            ];
        }

        return $status;
    }

    /**
     * Check for expired invitations and handle them
     */
    public function handleExpiredInvitations(): void
    {
        $expiredInvitations = SignatureInvitation::where('expires_at', '<', now())
            ->where('status', 'sent')
            ->with(['bailMobiliteSignature', 'signatureParty'])
            ->get();

        foreach ($expiredInvitations as $invitation) {
            $this->handleExpiredInvitation($invitation);
        }
    }

    /**
     * Handle a single expired invitation
     */
    private function handleExpiredInvitation(SignatureInvitation $invitation): void
    {
        DB::beginTransaction();
        try {
            $invitation->markAsExpired();

            // Notify ops staff about expired invitation
            $this->notificationService->sendInvitationExpiredNotification($invitation);

            // Try to resend or escalate based on workflow rules
            $step = $invitation->workflowStep;
            if ($step && $step->notification_settings['escalation_after_days'] ?? false) {
                $this->escalateExpiredInvitation($invitation, $step);
            }

            DB::commit();

            Log::warning('Signature invitation expired', [
                'invitation_id' => $invitation->id,
                'signature_id' => $invitation->bail_mobilite_signature_id,
                'party_email' => $invitation->signatureParty->email
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle expired invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Escalate expired invitation
     */
    private function escalateExpiredInvitation(SignatureInvitation $invitation, SignatureWorkflowStep $step): void
    {
        $escalationSettings = $step->notification_settings['escalation'] ?? null;
        
        if ($escalationSettings) {
            // Implement escalation logic (notify manager, assign to alternate party, etc.)
            $this->notificationService->sendEscalationNotification($invitation, $escalationSettings);
        }
    }
}