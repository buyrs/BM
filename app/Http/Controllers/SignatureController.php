<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            'signature_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $metadata = [
                'mission_id' => $request->input('mission_id'),
                'checker_id' => auth()->id(),
                'device_info' => $request->input('device_info', [])
            ];

            $signature = $this->signatureService->createTenantSignature(
                $bailMobilite,
                $request->input('signature_type'),
                $request->input('signature_data'),
                $metadata
            );

            return response()->json([
                'success' => true,
                'signature' => $signature->load('contractTemplate'),
                'message' => 'Signature créée avec succès'
            ]);

        } catch (\Exception $e) {
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
        $signature->load(['bailMobilite', 'contractTemplate']);
        
        return response()->json([
            'signature' => $signature,
            'validation' => $this->signatureService->validateSignature($signature),
            'metadata' => $this->signatureService->getSignatureMetadata($signature)
        ]);
    }

    /**
     * Download signed contract PDF
     */
    public function downloadContract(BailMobiliteSignature $signature)
    {
        // Check permissions
        if (!auth()->user()->can('view_bail_mobilite') && 
            !auth()->user()->hasRole(['super-admin', 'ops'])) {
            abort(403, 'Accès non autorisé');
        }

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
        if (!auth()->user()->can('view_bail_mobilite') && 
            !auth()->user()->hasRole(['super-admin', 'ops'])) {
            abort(403, 'Accès non autorisé');
        }

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
        $validation = $this->signatureService->validateSignature($signature);
        
        return response()->json([
            'signature_id' => $signature->id,
            'validation' => $validation,
            'metadata' => $this->signatureService->getSignatureMetadata($signature)
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
        // Only super-admin and ops can archive
        if (!auth()->user()->hasRole(['super-admin', 'ops'])) {
            abort(403, 'Accès non autorisé');
        }

        try {
            $this->signatureService->archiveSignatures($bailMobilite);

            return response()->json([
                'success' => true,
                'message' => 'Signatures archivées avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage: ' . $e->getMessage()
            ], 500);
        }
    }
}