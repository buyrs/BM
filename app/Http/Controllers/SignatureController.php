<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SignatureController extends Controller
{
    protected SignatureService $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Create a tenant signature for a bail mobilite
     */
    public function createTenantSignature(Request $request, BailMobilite $bailMobilite)
    {
        $validator = Validator::make($request->all(), [
            'signature_type' => 'required|in:entry,exit',
            'signature_data' => 'required|string|min:10', // Ensure signature has minimum data
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Enhanced metadata collection for security
            $metadata = [
                'mission_id' => $request->input('mission_id'),
                'checker_id' => auth()->id(),
                'checker_email' => auth()->user()->email,
                'device_info' => $request->input('device_info', []),
                'signature_length' => strlen($request->input('signature_data')),
                'request_timestamp' => now()->toISOString(),
            ];

            $signature = $this->signatureService->createTenantSignature(
                $bailMobilite,
                $request->input('signature_type'),
                $request->input('signature_data'),
                $metadata
            );

            // Log signature creation for audit trail
            $this->signatureService->logSignatureAccess($signature, 'created', auth()->user());

            return response()->json([
                'success' => true,
                'signature' => $signature->load('contractTemplate'),
                'message' => 'Signature créée avec succès',
                'security_info' => [
                    'integrity_verified' => $this->signatureService->verifySignatureIntegrity($signature),
                    'created_at' => $signature->created_at
                ]
            ]);

        } catch (\Exception $e) {
            // Log failed signature creation attempt
            Log::channel('security')->error('Signature creation failed', [
                'bail_mobilite_id' => $bailMobilite->id,
                'signature_type' => $request->input('signature_type'),
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la signature: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get signature details with validation
     */
    public function getSignature(BailMobiliteSignature $signature)
    {
        // Log signature access
        $this->signatureService->logSignatureAccess($signature, 'viewed', auth()->user());
        
        $signature->load(['bailMobilite', 'contractTemplate']);
        
        return response()->json([
            'signature' => $signature,
            'validation' => $this->signatureService->validateSignature($signature),
            'metadata' => $this->signatureService->getSignatureMetadata($signature),
            'access_logged' => true
        ]);
    }

    /**
     * Download signed contract PDF
     */
    public function downloadContract(BailMobiliteSignature $signature)
    {
        // Check permissions
        if (!auth()->user()->can('view_signatures')) {
            abort(403, 'Accès non autorisé');
        }

        // Log PDF download for audit trail
        $this->signatureService->logSignatureAccess($signature, 'pdf_downloaded', auth()->user());

        $pdfContent = $this->signatureService->getSignedContractPdf($signature);
        
        if (!$pdfContent) {
            abort(404, 'Contrat PDF non trouvé');
        }

        $filename = sprintf(
            'Contrat_BM_%s_%s_%s.pdf',
            $signature->bail_mobilite_id,
            $signature->signature_type,
            $signature->tenant_signed_at->format('Y-m-d')
        );

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Preview contract PDF in browser
     */
    public function previewContract(BailMobiliteSignature $signature)
    {
        // Check permissions
        if (!auth()->user()->can('view_signatures')) {
            abort(403, 'Accès non autorisé');
        }

        // Log PDF preview for audit trail
        $this->signatureService->logSignatureAccess($signature, 'pdf_previewed', auth()->user());

        $pdfContent = $this->signatureService->getSignedContractPdf($signature);
        
        if (!$pdfContent) {
            abort(404, 'Contrat PDF non trouvé');
        }

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline');
    }

    /**
     * Validate signature integrity
     */
    public function validateSignature(BailMobiliteSignature $signature)
    {
        // Log signature validation for audit trail
        $this->signatureService->logSignatureAccess($signature, 'validated', auth()->user());
        
        $validation = $this->signatureService->validateSignature($signature);
        $metadata = $this->signatureService->getSignatureMetadata($signature);
        
        return response()->json([
            'signature_id' => $signature->id,
            'validation' => $validation,
            'metadata' => $metadata,
            'audit_trail' => $this->signatureService->createAuditTrail($signature)
        ]);
    }

    /**
     * Get all signatures for a bail mobilite
     */
    public function getBailMobiliteSignatures(BailMobilite $bailMobilite)
    {
        $signatures = $bailMobilite->signatures()
            ->with('contractTemplate')
            ->get()
            ->map(function ($signature) {
                return [
                    'signature' => $signature,
                    'validation' => $this->signatureService->validateSignature($signature)
                ];
            });

        return response()->json([
            'bail_mobilite_id' => $bailMobilite->id,
            'signatures' => $signatures
        ]);
    }

    /**
     * Archive signatures for legal retention
     */
    public function archiveSignatures(BailMobilite $bailMobilite)
    {
        // Check permissions
        if (!auth()->user()->can('archive_signatures')) {
            abort(403, 'Accès non autorisé');
        }

        try {
            // Log each signature before archiving
            foreach ($bailMobilite->signatures as $signature) {
                $this->signatureService->logSignatureAccess($signature, 'archived', auth()->user());
            }

            $this->signatureService->archiveSignatures($bailMobilite);

            return response()->json([
                'success' => true,
                'message' => 'Signatures archivées avec succès',
                'archived_at' => now(),
                'archived_by' => auth()->user()->email
            ]);

        } catch (\Exception $e) {
            Log::channel('security')->error('Signature archiving failed', [
                'bail_mobilite_id' => $bailMobilite->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'ip_address' => request()->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage: ' . $e->getMessage()
            ], 500);
        }
    }
}