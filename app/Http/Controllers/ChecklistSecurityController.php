<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Services\ChecklistEncryptionService;
use App\Services\ChecklistSecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ChecklistSecurityController extends Controller
{
    public function __construct(
        private ChecklistEncryptionService $encryptionService,
        private ChecklistSecurityAuditService $auditService
    ) {}

    /**
     * Encrypt checklist data
     */
    public function encryptChecklist(Checklist $checklist)
    {
        try {
            $this->authorize('update', $checklist);

            $encryptedChecklist = $this->encryptionService->encryptChecklistData($checklist);

            Log::info('Checklist data encrypted', [
                'checklist_id' => $checklist->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checklist data encrypted successfully',
                'checklist_id' => $checklist->id
            ]);

        } catch (Exception $e) {
            Log::error('Failed to encrypt checklist data', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to encrypt checklist data'
            ], 500);
        }
    }

    /**
     * Decrypt checklist data
     */
    public function decryptChecklist(Checklist $checklist)
    {
        try {
            $this->authorize('view', $checklist);

            $decryptedChecklist = $this->encryptionService->decryptChecklistData($checklist);

            Log::info('Checklist data decrypted', [
                'checklist_id' => $checklist->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checklist data decrypted successfully',
                'checklist' => $decryptedChecklist
            ]);

        } catch (Exception $e) {
            Log::error('Failed to decrypt checklist data', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to decrypt checklist data'
            ], 500);
        }
    }

    /**
     * Perform security audit
     */
    public function performSecurityAudit(Checklist $checklist)
    {
        try {
            $this->authorize('view', $checklist);

            $auditResults = $this->auditService->performSecurityAudit($checklist);

            Log::info('Security audit performed', [
                'checklist_id' => $checklist->id,
                'risk_level' => $auditResults['risk_level'],
                'compliance_score' => $auditResults['compliance_score'],
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'audit_results' => $auditResults
            ]);

        } catch (Exception $e) {
            Log::error('Security audit failed', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Security audit failed'
            ], 500);
        }
    }

    /**
     * Generate security report
     */
    public function generateSecurityReport(Checklist $checklist)
    {
        try {
            $this->authorize('view', $checklist);

            $auditResults = $this->auditService->performSecurityAudit($checklist);
            $report = $this->auditService->generateSecurityReport($auditResults);

            return response($report, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="security_report_' . $checklist->id . '.txt"'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to generate security report', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate security report'
            ], 500);
        }
    }

    /**
     * Create secure backup
     */
    public function createSecureBackup(Checklist $checklist)
    {
        try {
            $this->authorize('update', $checklist);

            $backupResult = $this->encryptionService->createSecureBackup($checklist);

            if ($backupResult['success']) {
                Log::info('Secure backup created', [
                    'checklist_id' => $checklist->id,
                    'backup_file' => $backupResult['backup_file'],
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Secure backup created successfully',
                    'backup_file' => $backupResult['backup_file'],
                    'integrity_hash' => $backupResult['integrity_hash'],
                    'created_at' => $backupResult['created_at']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $backupResult['error']
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Failed to create secure backup', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create secure backup'
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restoreFromBackup(Request $request, Checklist $checklist)
    {
        try {
            $this->authorize('update', $checklist);

            $request->validate([
                'backup_file' => 'required|string',
                'integrity_hash' => 'required|string'
            ]);

            $restoreResult = $this->encryptionService->restoreFromBackup(
                $request->backup_file,
                $request->integrity_hash
            );

            if ($restoreResult['success']) {
                Log::info('Checklist restored from backup', [
                    'checklist_id' => $checklist->id,
                    'backup_file' => $request->backup_file,
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Checklist restored from backup successfully',
                    'restored_at' => $restoreResult['restored_at']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $restoreResult['error']
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Failed to restore from backup', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to restore from backup'
            ], 500);
        }
    }

    /**
     * Verify data integrity
     */
    public function verifyIntegrity(Checklist $checklist)
    {
        try {
            $this->authorize('view', $checklist);

            $integrityHash = $this->encryptionService->generateIntegrityHash([
                'general_info' => $checklist->general_info,
                'rooms' => $checklist->rooms,
                'utilities' => $checklist->utilities,
                'tenant_signature' => $checklist->tenant_signature,
                'agent_signature' => $checklist->agent_signature
            ]);

            return response()->json([
                'success' => true,
                'integrity_hash' => $integrityHash,
                'verified_at' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to verify data integrity', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to verify data integrity'
            ], 500);
        }
    }

    /**
     * Get security status
     */
    public function getSecurityStatus(Checklist $checklist)
    {
        try {
            $this->authorize('view', $checklist);

            $status = [
                'checklist_id' => $checklist->id,
                'encryption_status' => $this->getEncryptionStatus($checklist),
                'access_controls' => $this->getAccessControlStatus($checklist),
                'data_integrity' => $this->getDataIntegrityStatus($checklist),
                'last_audit' => $this->getLastAuditDate($checklist),
                'risk_level' => 'unknown'
            ];

            return response()->json([
                'success' => true,
                'security_status' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get security status', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get security status'
            ], 500);
        }
    }

    /**
     * Get encryption status
     */
    protected function getEncryptionStatus(Checklist $checklist): array
    {
        $encryptedFields = [];
        $unencryptedFields = [];

        $sensitiveFields = ['general_info', 'rooms', 'utilities', 'tenant_signature', 'agent_signature', 'ops_validation_comments'];
        
        foreach ($sensitiveFields as $field) {
            $value = $checklist->$field;
            if ($value) {
                if ($this->encryptionService->isEncrypted($value)) {
                    $encryptedFields[] = $field;
                } else {
                    $unencryptedFields[] = $field;
                }
            }
        }

        return [
            'encrypted_fields' => $encryptedFields,
            'unencrypted_fields' => $unencryptedFields,
            'encryption_percentage' => count($sensitiveFields) > 0 
                ? round((count($encryptedFields) / count($sensitiveFields)) * 100, 2) 
                : 100
        ];
    }

    /**
     * Get access control status
     */
    protected function getAccessControlStatus(Checklist $checklist): array
    {
        $mission = $checklist->mission;
        
        return [
            'has_assigned_agent' => !is_null($mission->agent_id),
            'agent_name' => $mission->agent?->name,
            'mission_status' => $mission->status,
            'checklist_status' => $checklist->status
        ];
    }

    /**
     * Get data integrity status
     */
    protected function getDataIntegrityStatus(Checklist $checklist): array
    {
        $integrityHash = $this->encryptionService->generateIntegrityHash([
            'general_info' => $checklist->general_info,
            'rooms' => $checklist->rooms,
            'utilities' => $checklist->utilities
        ]);

        return [
            'integrity_hash' => $integrityHash,
            'has_required_data' => !empty($checklist->general_info) && 
                                 !empty($checklist->rooms) && 
                                 !empty($checklist->utilities),
            'has_signatures' => !empty($checklist->tenant_signature) && 
                              !empty($checklist->agent_signature)
        ];
    }

    /**
     * Get last audit date
     */
    protected function getLastAuditDate(Checklist $checklist): ?string
    {
        // This would typically come from an audit log table
        // For now, return null as this would require additional database structure
        return null;
    }
}
