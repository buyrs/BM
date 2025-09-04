<?php

namespace App\Http\Controllers;

use App\Models\BailMobiliteSignature;
use App\Models\SignatureInvitation;
use App\Services\MultiPartySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SignatureWorkflowController extends Controller
{
    public function __construct(
        private MultiPartySignatureService $multiPartyService
    ) {}

    /**
     * Show signature workflow status
     */
    public function showWorkflowStatus(BailMobiliteSignature $signature)
    {
        $this->authorize('view', $signature);

        $workflowStatus = $this->multiPartyService->getWorkflowStatus($signature);

        return Inertia::render('Signatures/WorkflowStatus', [
            'signature' => $signature->load(['bailMobilite', 'contractTemplate']),
            'workflowStatus' => $workflowStatus,
            'completedSignatures' => $signature->getCompletedSignatures()
        ]);
    }

    /**
     * Initialize multi-party signature workflow
     */
    public function initializeWorkflow(BailMobiliteSignature $signature)
    {
        $this->authorize('update', $signature);

        if (!$signature->contractTemplate->requires_multi_party) {
            return redirect()->back()
                ->with('error', 'This contract template does not require multi-party signatures');
        }

        $success = $this->multiPartyService->initializeWorkflow($signature);

        if ($success) {
            return redirect()->back()
                ->with('success', 'Multi-party signature workflow initialized successfully');
        }

        return redirect()->back()
            ->with('error', 'Failed to initialize multi-party signature workflow');
    }

    /**
     * Resend invitation
     */
    public function resendInvitation(SignatureInvitation $invitation)
    {
        $this->authorize('update', $invitation->bailMobiliteSignature);

        $success = $this->multiPartyService->resendInvitation($invitation);

        if ($success) {
            return redirect()->back()
                ->with('success', 'Invitation resent successfully');
        }

        return redirect()->back()
            ->with('error', 'Failed to resend invitation');
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(SignatureInvitation $invitation, Request $request)
    {
        $this->authorize('update', $invitation->bailMobiliteSignature);

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $success = $this->multiPartyService->cancelInvitation($invitation, $request->reason);

        if ($success) {
            return redirect()->back()
                ->with('success', 'Invitation cancelled successfully');
        }

        return redirect()->back()
            ->with('error', 'Failed to cancel invitation');
    }

    /**
     * Show invitation signature page
     */
    public function showInvitation(string $token)
    {
        $invitation = SignatureInvitation::findByToken($token);

        if (!$invitation) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired signature invitation');
        }

        if ($invitation->isExpired()) {
            return Inertia::render('Signatures/InvitationExpired', [
                'invitation' => $invitation->load(['signatureParty', 'bailMobiliteSignature.bailMobilite'])
            ]);
        }

        // Track invitation view
        $invitation->markAsOpened([
            'viewed_at' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return Inertia::render('Signatures/SignInvitation', [
            'invitation' => $invitation->load([
                'signatureParty',
                'bailMobiliteSignature.bailMobilite',
                'bailMobiliteSignature.contractTemplate'
            ]),
            'token' => $token
        ]);
    }

    /**
     * Process signature submission
     */
    public function processSignature(Request $request, string $token)
    {
        $invitation = SignatureInvitation::findByToken($token);

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired signature invitation'
            ], 404);
        }

        $request->validate([
            'signature_data' => 'required|array',
            'signature_data.signature' => 'required|string',
            'signature_data.timestamp' => 'required|date',
            'signature_data.ip_address' => 'required|ip',
            'consent' => 'required|accepted'
        ]);

        try {
            $success = $this->multiPartyService->processCompletedInvitation(
                $invitation,
                $request->signature_data
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Signature submitted successfully',
                    'redirect_url' => route('signatures.invitation.completed', $invitation->id)
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process signature'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Signature processing failed', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your signature: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show signature completion page
     */
    public function showCompletion(SignatureInvitation $invitation)
    {
        if (!$invitation->isCompleted()) {
            return redirect()->route('signatures.invitation', $invitation->token)
                ->with('error', 'Signature not completed yet');
        }

        return Inertia::render('Signatures/Completion', [
            'invitation' => $invitation->load([
                'signatureParty',
                'bailMobiliteSignature.bailMobilite',
                'bailMobiliteSignature.contractTemplate'
            ])
        ]);
    }

    /**
     * Get workflow status via API
     */
    public function getWorkflowStatusApi(BailMobiliteSignature $signature)
    {
        $this->authorize('view', $signature);

        $workflowStatus = $this->multiPartyService->getWorkflowStatus($signature);

        return response()->json([
            'success' => true,
            'data' => $workflowStatus
        ]);
    }

    /**
     * Download signed contract
     */
    public function downloadContract(BailMobiliteSignature $signature)
    {
        $this->authorize('view', $signature);

        if (!$signature->isComplete()) {
            return redirect()->back()
                ->with('error', 'Contract is not fully signed yet');
        }

        $pdfContent = $this->multiPartyService->getSignedContractPdf($signature);

        if (!$pdfContent) {
            return redirect()->back()
                ->with('error', 'Contract PDF not found');
        }

        return response()->make($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contract_' . $signature->id . '.pdf"'
        ]);
    }
}