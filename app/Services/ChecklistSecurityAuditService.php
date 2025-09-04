<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ChecklistSecurityAuditService
{
    /**
     * Perform comprehensive security audit of checklist data
     */
    public function performSecurityAudit(Checklist $checklist): array
    {
        $audit = [
            'checklist_id' => $checklist->id,
            'audit_timestamp' => now()->toISOString(),
            'auditor' => auth()->user()?->name ?? 'System',
            'findings' => [],
            'recommendations' => [],
            'risk_level' => 'low',
            'compliance_score' => 100
        ];

        try {
            // Check data encryption
            $encryptionAudit = $this->auditDataEncryption($checklist);
            $audit['findings'] = array_merge($audit['findings'], $encryptionAudit['findings']);
            $audit['recommendations'] = array_merge($audit['recommendations'], $encryptionAudit['recommendations']);

            // Check access controls
            $accessAudit = $this->auditAccessControls($checklist);
            $audit['findings'] = array_merge($audit['findings'], $accessAudit['findings']);
            $audit['recommendations'] = array_merge($audit['recommendations'], $accessAudit['recommendations']);

            // Check data integrity
            $integrityAudit = $this->auditDataIntegrity($checklist);
            $audit['findings'] = array_merge($audit['findings'], $integrityAudit['findings']);
            $audit['recommendations'] = array_merge($audit['recommendations'], $integrityAudit['recommendations']);

            // Check signature security
            $signatureAudit = $this->auditSignatureSecurity($checklist);
            $audit['findings'] = array_merge($audit['findings'], $signatureAudit['findings']);
            $audit['recommendations'] = array_merge($audit['recommendations'], $signatureAudit['recommendations']);

            // Check photo security
            $photoAudit = $this->auditPhotoSecurity($checklist);
            $audit['findings'] = array_merge($audit['findings'], $photoAudit['findings']);
            $audit['recommendations'] = array_merge($audit['recommendations'], $photoAudit['recommendations']);

            // Calculate risk level and compliance score
            $audit['risk_level'] = $this->calculateRiskLevel($audit['findings']);
            $audit['compliance_score'] = $this->calculateComplianceScore($audit['findings']);

            // Log audit results
            Log::info('Checklist security audit completed', [
                'checklist_id' => $checklist->id,
                'risk_level' => $audit['risk_level'],
                'compliance_score' => $audit['compliance_score'],
                'findings_count' => count($audit['findings'])
            ]);

            return $audit;

        } catch (Exception $e) {
            Log::error('Security audit failed', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            $audit['findings'][] = [
                'type' => 'error',
                'category' => 'audit_failure',
                'severity' => 'high',
                'description' => 'Security audit failed: ' . $e->getMessage(),
                'recommendation' => 'Review system logs and retry audit'
            ];

            $audit['risk_level'] = 'high';
            $audit['compliance_score'] = 0;

            return $audit;
        }
    }

    /**
     * Audit data encryption
     */
    protected function auditDataEncryption(Checklist $checklist): array
    {
        $findings = [];
        $recommendations = [];

        // Check if sensitive data is encrypted
        $sensitiveFields = ['general_info', 'rooms', 'utilities', 'tenant_signature', 'agent_signature', 'ops_validation_comments'];
        
        foreach ($sensitiveFields as $field) {
            $value = $checklist->$field;
            
            if ($value && !$this->isEncrypted($value)) {
                $findings[] = [
                    'type' => 'security',
                    'category' => 'encryption',
                    'severity' => 'high',
                    'description' => "Field '{$field}' contains unencrypted sensitive data",
                    'field' => $field
                ];
                
                $recommendations[] = "Encrypt the '{$field}' field before storage";
            }
        }

        return compact('findings', 'recommendations');
    }

    /**
     * Audit access controls
     */
    protected function auditAccessControls(Checklist $checklist): array
    {
        $findings = [];
        $recommendations = [];

        // Check if checklist has proper access controls
        $mission = $checklist->mission;
        
        if (!$mission->agent_id) {
            $findings[] = [
                'type' => 'security',
                'category' => 'access_control',
                'severity' => 'medium',
                'description' => 'Checklist has no assigned agent',
                'field' => 'agent_id'
            ];
            
            $recommendations[] = 'Assign an agent to the checklist for proper access control';
        }

        // Check if checklist is accessible by unauthorized users
        $unauthorizedAccess = $this->checkUnauthorizedAccess($checklist);
        if ($unauthorizedAccess) {
            $findings[] = [
                'type' => 'security',
                'category' => 'access_control',
                'severity' => 'high',
                'description' => 'Checklist may be accessible by unauthorized users',
                'details' => $unauthorizedAccess
            ];
            
            $recommendations[] = 'Review and tighten access controls for this checklist';
        }

        return compact('findings', 'recommendations');
    }

    /**
     * Audit data integrity
     */
    protected function auditDataIntegrity(Checklist $checklist): array
    {
        $findings = [];
        $recommendations = [];

        // Check for data corruption
        $corruptedFields = $this->checkDataCorruption($checklist);
        if (!empty($corruptedFields)) {
            $findings[] = [
                'type' => 'integrity',
                'category' => 'data_corruption',
                'severity' => 'high',
                'description' => 'Data corruption detected in fields: ' . implode(', ', $corruptedFields),
                'corrupted_fields' => $corruptedFields
            ];
            
            $recommendations[] = 'Restore data from backup or regenerate corrupted fields';
        }

        // Check for missing required data
        $missingFields = $this->checkMissingRequiredData($checklist);
        if (!empty($missingFields)) {
            $findings[] = [
                'type' => 'integrity',
                'category' => 'missing_data',
                'severity' => 'medium',
                'description' => 'Missing required data in fields: ' . implode(', ', $missingFields),
                'missing_fields' => $missingFields
            ];
            
            $recommendations[] = 'Complete missing required fields';
        }

        return compact('findings', 'recommendations');
    }

    /**
     * Audit signature security
     */
    protected function auditSignatureSecurity(Checklist $checklist): array
    {
        $findings = [];
        $recommendations = [];

        // Check signature presence
        if (!$checklist->tenant_signature) {
            $findings[] = [
                'type' => 'security',
                'category' => 'signature',
                'severity' => 'medium',
                'description' => 'Missing tenant signature',
                'field' => 'tenant_signature'
            ];
            
            $recommendations[] = 'Obtain tenant signature for legal compliance';
        }

        if (!$checklist->agent_signature) {
            $findings[] = [
                'type' => 'security',
                'category' => 'signature',
                'severity' => 'medium',
                'description' => 'Missing agent signature',
                'field' => 'agent_signature'
            ];
            
            $recommendations[] = 'Obtain agent signature for legal compliance';
        }

        // Check signature integrity
        if ($checklist->tenant_signature && !$this->isValidSignature($checklist->tenant_signature)) {
            $findings[] = [
                'type' => 'security',
                'category' => 'signature',
                'severity' => 'high',
                'description' => 'Invalid or corrupted tenant signature',
                'field' => 'tenant_signature'
            ];
            
            $recommendations[] = 'Re-obtain tenant signature';
        }

        if ($checklist->agent_signature && !$this->isValidSignature($checklist->agent_signature)) {
            $findings[] = [
                'type' => 'security',
                'category' => 'signature',
                'severity' => 'high',
                'description' => 'Invalid or corrupted agent signature',
                'field' => 'agent_signature'
            ];
            
            $recommendations[] = 'Re-obtain agent signature';
        }

        return compact('findings', 'recommendations');
    }

    /**
     * Audit photo security
     */
    protected function auditPhotoSecurity(Checklist $checklist): array
    {
        $findings = [];
        $recommendations = [];

        $photos = $checklist->items()->with('photos')->get()->pluck('photos')->flatten();

        foreach ($photos as $photo) {
            // Check if photo file exists
            if (!file_exists(storage_path('app/public/' . $photo->photo_path))) {
                $findings[] = [
                    'type' => 'security',
                    'category' => 'photo',
                    'severity' => 'medium',
                    'description' => "Photo file not found: {$photo->photo_path}",
                    'photo_id' => $photo->id
                ];
                
                $recommendations[] = 'Restore missing photo file or remove reference';
            }

            // Check photo file permissions
            if (file_exists(storage_path('app/public/' . $photo->photo_path))) {
                $permissions = fileperms(storage_path('app/public/' . $photo->photo_path));
                if ($permissions & 0x0004) { // Check if world-readable
                    $findings[] = [
                        'type' => 'security',
                        'category' => 'photo',
                        'severity' => 'medium',
                        'description' => "Photo file has overly permissive permissions: {$photo->photo_path}",
                        'photo_id' => $photo->id
                    ];
                    
                    $recommendations[] = 'Restrict photo file permissions';
                }
            }
        }

        return compact('findings', 'recommendations');
    }

    /**
     * Check if data appears to be encrypted
     */
    protected function isEncrypted($data): bool
    {
        if (is_array($data)) {
            // Check if array contains encrypted strings
            foreach ($data as $value) {
                if (is_string($value) && $this->isEncryptedString($value)) {
                    return true;
                }
            }
            return false;
        }

        return $this->isEncryptedString($data);
    }

    /**
     * Check if string appears to be encrypted
     */
    protected function isEncryptedString(string $data): bool
    {
        // Laravel's Crypt produces base64 encoded strings that start with 'eyJ'
        return str_starts_with($data, 'eyJ') && base64_decode($data, true) !== false;
    }

    /**
     * Check for unauthorized access
     */
    protected function checkUnauthorizedAccess(Checklist $checklist): ?array
    {
        // This would check logs for unauthorized access attempts
        // For now, return null as this would require log analysis
        return null;
    }

    /**
     * Check for data corruption
     */
    protected function checkDataCorruption(Checklist $checklist): array
    {
        $corruptedFields = [];

        // Check JSON fields
        $jsonFields = ['general_info', 'rooms', 'utilities'];
        foreach ($jsonFields as $field) {
            $value = $checklist->$field;
            if ($value && !is_array($value)) {
                $corruptedFields[] = $field;
            }
        }

        return $corruptedFields;
    }

    /**
     * Check for missing required data
     */
    protected function checkMissingRequiredData(Checklist $checklist): array
    {
        $missingFields = [];

        $requiredFields = ['general_info', 'rooms', 'utilities'];
        foreach ($requiredFields as $field) {
            if (empty($checklist->$field)) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * Check if signature is valid
     */
    protected function isValidSignature(string $signature): bool
    {
        // Check if it's a valid base64 image
        return preg_match('/^data:image\/[a-zA-Z]+;base64,/', $signature) && 
               base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $signature)) !== false;
    }

    /**
     * Calculate risk level based on findings
     */
    protected function calculateRiskLevel(array $findings): string
    {
        $highSeverityCount = count(array_filter($findings, fn($f) => $f['severity'] === 'high'));
        $mediumSeverityCount = count(array_filter($findings, fn($f) => $f['severity'] === 'medium'));

        if ($highSeverityCount > 0) {
            return 'high';
        } elseif ($mediumSeverityCount > 2) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Calculate compliance score based on findings
     */
    protected function calculateComplianceScore(array $findings): int
    {
        $totalFindings = count($findings);
        if ($totalFindings === 0) {
            return 100;
        }

        $highSeverityCount = count(array_filter($findings, fn($f) => $f['severity'] === 'high'));
        $mediumSeverityCount = count(array_filter($findings, fn($f) => $f['severity'] === 'medium'));

        $score = 100;
        $score -= $highSeverityCount * 20; // High severity findings reduce score by 20 points each
        $score -= $mediumSeverityCount * 10; // Medium severity findings reduce score by 10 points each

        return max(0, $score);
    }

    /**
     * Generate security report
     */
    public function generateSecurityReport(array $auditResults): string
    {
        $report = "SECURITY AUDIT REPORT\n";
        $report .= "====================\n\n";
        $report .= "Checklist ID: {$auditResults['checklist_id']}\n";
        $report .= "Audit Date: {$auditResults['audit_timestamp']}\n";
        $report .= "Auditor: {$auditResults['auditor']}\n";
        $report .= "Risk Level: " . strtoupper($auditResults['risk_level']) . "\n";
        $report .= "Compliance Score: {$auditResults['compliance_score']}%\n\n";

        if (!empty($auditResults['findings'])) {
            $report .= "FINDINGS:\n";
            $report .= "---------\n";
            foreach ($auditResults['findings'] as $index => $finding) {
                $report .= ($index + 1) . ". [{$finding['severity']}] {$finding['description']}\n";
                if (isset($finding['recommendation'])) {
                    $report .= "   Recommendation: {$finding['recommendation']}\n";
                }
                $report .= "\n";
            }
        }

        if (!empty($auditResults['recommendations'])) {
            $report .= "RECOMMENDATIONS:\n";
            $report .= "----------------\n";
            foreach (array_unique($auditResults['recommendations']) as $index => $recommendation) {
                $report .= ($index + 1) . ". {$recommendation}\n";
            }
        }

        return $report;
    }
}
