<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Services\SignatureValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class SignatureController extends Controller
{
    public function __construct(
        private SignatureValidationService $signatureValidationService
    ) {}

    /**
     * Validate signature data
     */
    public function validateSignature(Request $request)
    {
        try {
            $request->validate([
                'signature_data' => 'required|string',
                'signature_type' => 'required|in:tenant,agent'
            ]);

            $validation = $this->signatureValidationService->validateSignatureData(
                $request->signature_data
            );

            return response()->json([
                'success' => true,
                'validation' => $validation
            ]);

        } catch (Exception $e) {
            Log::error('Signature validation failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Signature validation failed'
            ], 500);
        }
    }

    /**
     * Save signature to checklist
     */
    public function saveSignature(Request $request, Checklist $checklist)
    {
        try {
            $this->authorize('update', $checklist);

            $request->validate([
                'signature_data' => 'required|string',
                'signature_type' => 'required|in:tenant,agent'
            ]);

            // Validate signature data
            $validation = $this->signatureValidationService->validateSignatureData(
                $request->signature_data
            );

            if (!$validation['is_valid']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature data',
                    'validation_errors' => $validation['errors']
                ], 422);
            }

            // Optimize signature image
            $optimizedSignature = $this->signatureValidationService->optimizeSignatureImage(
                $request->signature_data
            );

            // Extract metadata
            $metadata = $this->signatureValidationService->extractSignatureMetadata(
                $optimizedSignature,
                [
                    'signature_type' => $request->signature_type,
                    'checklist_id' => $checklist->id,
                    'user_id' => auth()->id()
                ]
            );

            DB::beginTransaction();

            try {
                // Update checklist with signature
                $updateData = [];
                if ($request->signature_type === 'tenant') {
                    $updateData['tenant_signature'] = $optimizedSignature;
                } else {
                    $updateData['agent_signature'] = $optimizedSignature;
                }

                $checklist->update($updateData);

                // Log signature save
                Log::info('Signature saved to checklist', [
                    'checklist_id' => $checklist->id,
                    'signature_type' => $request->signature_type,
                    'user_id' => auth()->id(),
                    'metadata' => $metadata
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Signature saved successfully',
                    'signature_type' => $request->signature_type,
                    'metadata' => $metadata
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Failed to save signature', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save signature'
            ], 500);
        }
    }

    /**
     * Get signature data
     */
    public function getSignature(Checklist $checklist, string $type)
    {
        try {
            $this->authorize('view', $checklist);

            if (!in_array($type, ['tenant', 'agent'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature type'
                ], 422);
            }

            $signatureField = $type . '_signature';
            $signatureData = $checklist->$signatureField;

            if (!$signatureData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Signature not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'signature_data' => $signatureData,
                'signature_type' => $type
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get signature', [
                'checklist_id' => $checklist->id,
                'signature_type' => $type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get signature'
            ], 500);
        }
    }

    /**
     * Delete signature
     */
    public function deleteSignature(Checklist $checklist, string $type)
    {
        try {
            $this->authorize('update', $checklist);

            if (!in_array($type, ['tenant', 'agent'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature type'
                ], 422);
            }

            $signatureField = $type . '_signature';
            
            $checklist->update([
                $signatureField => null
            ]);

            Log::info('Signature deleted', [
                'checklist_id' => $checklist->id,
                'signature_type' => $type,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Signature deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete signature', [
                'checklist_id' => $checklist->id,
                'signature_type' => $type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete signature'
            ], 500);
        }
    }

    /**
     * Create signature thumbnail
     */
    public function createThumbnail(Request $request)
    {
        try {
            $request->validate([
                'signature_data' => 'required|string',
                'width' => 'integer|min:50|max:500',
                'height' => 'integer|min:25|max:250'
            ]);

            $width = $request->input('width', 200);
            $height = $request->input('height', 100);

            $thumbnail = $this->signatureValidationService->createSignatureThumbnail(
                $request->signature_data,
                $width,
                $height
            );

            return response()->json([
                'success' => true,
                'thumbnail' => $thumbnail,
                'dimensions' => ['width' => $width, 'height' => $height]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create signature thumbnail', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create thumbnail'
            ], 500);
        }
    }

    /**
     * Verify signature integrity
     */
    public function verifyIntegrity(Request $request, Checklist $checklist)
    {
        try {
            $this->authorize('view', $checklist);

            $request->validate([
                'signature_type' => 'required|in:tenant,agent',
                'expected_hash' => 'required|string'
            ]);

            $signatureField = $request->signature_type . '_signature';
            $signatureData = $checklist->$signatureField;

            if (!$signatureData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Signature not found'
                ], 404);
            }

            $isValid = $this->signatureValidationService->verifySignatureIntegrity(
                $signatureData,
                $request->expected_hash
            );

            return response()->json([
                'success' => true,
                'is_valid' => $isValid,
                'signature_type' => $request->signature_type
            ]);

        } catch (Exception $e) {
            Log::error('Failed to verify signature integrity', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to verify signature integrity'
            ], 500);
        }
    }
}