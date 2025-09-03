<?php

namespace App\Services;

use App\Models\BailMobiliteSignature;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SecurityService
{
    /**
     * Verify signature integrity and detect tampering
     */
    public function verifySignatureIntegrity(BailMobiliteSignature $signature): array
    {
        $result = [
            'is_valid' => true,
            'checks' => [],
            'errors' => [],
            'warnings' => [],
            'security_score' => 100,
            'verified_at' => now()
        ];

        // Check 1: Signature data encryption integrity
        try {
            $decryptedData = decrypt($signature->tenant_signature);
            $result['checks']['encryption'] = 'valid';
        } catch (\Exception $e) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Signature data encryption compromised';
            $result['checks']['encryption'] = 'failed';
            $result['security_score'] -= 30;
        }

        // Check 2: Integrity hash verification
        if (isset($signature->signature_metadata['integrity_hash'])) {
            $expectedHash = $this->calculateSignatureHash($signature);
            $storedHash = $signature->signature_metadata['integrity_hash'];
            
            if ($expectedHash === $storedHash) {
                $result['checks']['integrity_hash'] = 'valid';
            } else {
                $result['is_valid'] = false;
                $result['errors'][] = 'Signature integrity hash mismatch - possible tampering detected';
                $result['checks']['integrity_hash'] = 'failed';
                $result['security_score'] -= 40;
            }
        } else {
            $result['warnings'][] = 'No integrity hash found (legacy signature)';
            $result['checks']['integrity_hash'] = 'missing';
            $result['security_score'] -= 10;
        }

        // Check 3: Timestamp validation
        if ($signature->tenant_signed_at) {
            $signedAt = $signature->tenant_signed_at;
            $now = now();
            
            // Check if signature is from the future (suspicious)
            if ($signedAt->gt($now)) {
                $result['is_valid'] = false;
                $result['errors'][] = 'Signature timestamp is in the future - possible tampering';
                $result['checks']['timestamp'] = 'invalid';
                $result['security_score'] -= 25;
            } else {
                $result['checks']['timestamp'] = 'valid';
            }
            
            // Check if signature is too old (warning only)
            if ($signedAt->diffInDays($now) > 365) {
                $result['warnings'][] = 'Signature is older than 1 year';
                $result['security_score'] -= 5;
            }
        } else {
            $result['is_valid'] = false;
            $result['errors'][] = 'Missing signature timestamp';
            $result['checks']['timestamp'] = 'missing';
            $result['security_score'] -= 20;
        }

        // Check 4: PDF integrity verification
        if ($signature->contract_pdf_path) {
            $pdfIntegrity = $this->verifyPdfIntegrity($signature);
            $result['checks']['pdf_integrity'] = $pdfIntegrity['status'];
            
            if (!$pdfIntegrity['valid']) {
                $result['is_valid'] = false;
                $result['errors'][] = $pdfIntegrity['error'];
                $result['security_score'] -= 20;
            }
        } else {
            $result['warnings'][] = 'No PDF contract generated';
            $result['checks']['pdf_integrity'] = 'missing';
            $result['security_score'] -= 5;
        }

        // Check 5: Metadata consistency
        $metadataCheck = $this->verifyMetadataConsistency($signature);
        $result['checks']['metadata'] = $metadataCheck['status'];
        
        if (!$metadataCheck['valid']) {
            $result['warnings'] = array_merge($result['warnings'], $metadataCheck['warnings']);
            $result['security_score'] -= $metadataCheck['score_deduction'];
        }

        // Check 6: Device fingerprint validation
        if (isset($signature->signature_metadata['device_fingerprint'])) {
            $fingerprintCheck = $this->validateDeviceFingerprint($signature);
            $result['checks']['device_fingerprint'] = $fingerprintCheck['status'];
            
            if (!$fingerprintCheck['valid']) {
                $result['warnings'][] = $fingerprintCheck['warning'];
                $result['security_score'] -= 5;
            }
        } else {
            $result['warnings'][] = 'No device fingerprint recorded';
            $result['checks']['device_fingerprint'] = 'missing';
            $result['security_score'] -= 5;
        }

        // Check 7: IP address reputation
        if (isset($signature->signature_metadata['ip_address'])) {
            $ipCheck = $this->checkIpReputation($signature->signature_metadata['ip_address']);
            $result['checks']['ip_reputation'] = $ipCheck['status'];
            
            if ($ipCheck['suspicious']) {
                $result['warnings'][] = $ipCheck['warning'];
                $result['security_score'] -= 10;
            }
        }

        // Final security score calculation
        $result['security_score'] = max(0, $result['security_score']);
        $result['security_level'] = $this->getSecurityLevel($result['security_score']);

        // Log verification attempt
        $this->logSecurityVerification($signature, $result);

        return $result;
    }

    /**
     * Calculate signature hash for integrity verification
     */
    private function calculateSignatureHash(BailMobiliteSignature $signature): string
    {
        $components = [
            $signature->bail_mobilite_id,
            $signature->signature_type,
            $signature->contract_template_id,
            $signature->tenant_signature, // encrypted data
            $signature->tenant_signed_at?->toISOString(),
            $signature->created_at->toISOString()
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Verify PDF file integrity
     */
    private function verifyPdfIntegrity(BailMobiliteSignature $signature): array
    {
        $pdfPath = $signature->contract_pdf_path;
        
        if (!Storage::disk('private')->exists($pdfPath)) {
            return [
                'valid' => false,
                'status' => 'missing',
                'error' => 'PDF file not found'
            ];
        }

        $hashPath = $pdfPath . '.hash';
        if (!Storage::disk('private')->exists($hashPath)) {
            return [
                'valid' => true, // Not invalid, just no hash to verify
                'status' => 'no_hash',
                'error' => 'PDF hash file not found (legacy file)'
            ];
        }

        $pdfContent = Storage::disk('private')->get($pdfPath);
        $currentHash = hash('sha256', $pdfContent);
        $storedHash = Storage::disk('private')->get($hashPath);

        if ($currentHash === $storedHash) {
            return [
                'valid' => true,
                'status' => 'valid',
                'error' => null
            ];
        } else {
            return [
                'valid' => false,
                'status' => 'tampered',
                'error' => 'PDF file has been modified after creation'
            ];
        }
    }

    /**
     * Verify metadata consistency
     */
    private function verifyMetadataConsistency(BailMobiliteSignature $signature): array
    {
        $metadata = $signature->signature_metadata ?? [];
        $warnings = [];
        $scoreDeduction = 0;

        // Check required metadata fields
        $requiredFields = [
            'ip_address',
            'user_agent',
            'timestamp',
            'device_fingerprint'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($metadata[$field]) || empty($metadata[$field])) {
                $warnings[] = "Missing metadata field: {$field}";
                $scoreDeduction += 2;
            }
        }

        // Check timestamp consistency
        if (isset($metadata['timestamp'])) {
            $metadataTimestamp = Carbon::parse($metadata['timestamp']);
            $signatureTimestamp = $signature->tenant_signed_at;
            
            if ($signatureTimestamp && abs($metadataTimestamp->diffInMinutes($signatureTimestamp)) > 5) {
                $warnings[] = 'Timestamp inconsistency between metadata and signature record';
                $scoreDeduction += 5;
            }
        }

        // Check IP address format
        if (isset($metadata['ip_address']) && !filter_var($metadata['ip_address'], FILTER_VALIDATE_IP)) {
            $warnings[] = 'Invalid IP address format in metadata';
            $scoreDeduction += 3;
        }

        return [
            'valid' => empty($warnings) || $scoreDeduction < 10,
            'status' => empty($warnings) ? 'valid' : 'inconsistent',
            'warnings' => $warnings,
            'score_deduction' => $scoreDeduction
        ];
    }

    /**
     * Validate device fingerprint
     */
    private function validateDeviceFingerprint(BailMobiliteSignature $signature): array
    {
        $metadata = $signature->signature_metadata ?? [];
        $fingerprint = $metadata['device_fingerprint'] ?? '';
        
        // Basic validation - check if fingerprint looks valid
        if (strlen($fingerprint) !== 64) { // SHA256 hash should be 64 chars
            return [
                'valid' => false,
                'status' => 'invalid',
                'warning' => 'Device fingerprint format is invalid'
            ];
        }

        // Check if fingerprint is all zeros or other suspicious patterns
        if (preg_match('/^0+$/', $fingerprint) || preg_match('/^(.)\1+$/', $fingerprint)) {
            return [
                'valid' => false,
                'status' => 'suspicious',
                'warning' => 'Device fingerprint appears to be artificially generated'
            ];
        }

        return [
            'valid' => true,
            'status' => 'valid',
            'warning' => null
        ];
    }

    /**
     * Check IP address reputation
     */
    private function checkIpReputation(string $ip): array
    {
        // Basic IP reputation checks
        $suspicious = false;
        $warning = '';

        // Check for localhost/private IPs in production
        if (app()->environment('production')) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $suspicious = true;
                $warning = 'Signature created from private/reserved IP address in production environment';
            }
        }

        // Check for known suspicious patterns
        $suspiciousPatterns = [
            '0.0.0.0',
            '127.0.0.1',
            '255.255.255.255'
        ];

        if (in_array($ip, $suspiciousPatterns)) {
            $suspicious = true;
            $warning = 'Signature created from suspicious IP address: ' . $ip;
        }

        return [
            'suspicious' => $suspicious,
            'status' => $suspicious ? 'suspicious' : 'clean',
            'warning' => $warning
        ];
    }

    /**
     * Get security level based on score
     */
    private function getSecurityLevel(int $score): string
    {
        if ($score >= 90) return 'high';
        if ($score >= 70) return 'medium';
        if ($score >= 50) return 'low';
        return 'critical';
    }

    /**
     * Log security verification attempt
     */
    private function logSecurityVerification(BailMobiliteSignature $signature, array $result): void
    {
        Log::channel('security')->info('Signature security verification performed', [
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'is_valid' => $result['is_valid'],
            'security_score' => $result['security_score'],
            'security_level' => $result['security_level'],
            'checks_passed' => count(array_filter($result['checks'], fn($status) => $status === 'valid')),
            'total_checks' => count($result['checks']),
            'errors_count' => count($result['errors']),
            'warnings_count' => count($result['warnings']),
            'verified_by' => auth()->user()?->email ?? 'system',
            'verified_at' => $result['verified_at'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Detect potential tampering patterns
     */
    public function detectTamperingPatterns(BailMobiliteSignature $signature): array
    {
        $patterns = [];
        $metadata = $signature->signature_metadata ?? [];

        // Pattern 1: Signature created too quickly after bail mobilité
        if ($signature->bailMobilite && $signature->tenant_signed_at) {
            $timeDiff = $signature->bailMobilite->created_at->diffInMinutes($signature->tenant_signed_at);
            if ($timeDiff < 5) {
                $patterns[] = [
                    'type' => 'timing_anomaly',
                    'description' => 'Signature created unusually quickly after bail mobilité creation',
                    'severity' => 'medium',
                    'details' => "Signed {$timeDiff} minutes after creation"
                ];
            }
        }

        // Pattern 2: Multiple signatures from same device/IP in short time
        $recentSignatures = BailMobiliteSignature::where('id', '!=', $signature->id)
            ->where('tenant_signed_at', '>=', $signature->tenant_signed_at->subHours(1))
            ->where('tenant_signed_at', '<=', $signature->tenant_signed_at->addHours(1))
            ->get();

        $sameDeviceCount = 0;
        $sameIpCount = 0;

        foreach ($recentSignatures as $recentSig) {
            $recentMetadata = $recentSig->signature_metadata ?? [];
            
            if (isset($metadata['device_fingerprint'], $recentMetadata['device_fingerprint']) &&
                $metadata['device_fingerprint'] === $recentMetadata['device_fingerprint']) {
                $sameDeviceCount++;
            }
            
            if (isset($metadata['ip_address'], $recentMetadata['ip_address']) &&
                $metadata['ip_address'] === $recentMetadata['ip_address']) {
                $sameIpCount++;
            }
        }

        if ($sameDeviceCount > 3) {
            $patterns[] = [
                'type' => 'device_reuse',
                'description' => 'Multiple signatures from same device in short timeframe',
                'severity' => 'high',
                'details' => "{$sameDeviceCount} signatures from same device within 2 hours"
            ];
        }

        if ($sameIpCount > 5) {
            $patterns[] = [
                'type' => 'ip_reuse',
                'description' => 'Multiple signatures from same IP in short timeframe',
                'severity' => 'medium',
                'details' => "{$sameIpCount} signatures from same IP within 2 hours"
            ];
        }

        // Pattern 3: Signature metadata inconsistencies
        if (isset($metadata['browser_info']['contains_mobile']) && 
            isset($metadata['user_agent']) &&
            $metadata['browser_info']['contains_mobile'] !== (stripos($metadata['user_agent'], 'mobile') !== false)) {
            $patterns[] = [
                'type' => 'metadata_inconsistency',
                'description' => 'Browser metadata inconsistent with user agent',
                'severity' => 'medium',
                'details' => 'Mobile detection mismatch'
            ];
        }

        // Pattern 4: Suspicious signature timing (e.g., exactly on the hour/minute)
        if ($signature->tenant_signed_at) {
            $seconds = $signature->tenant_signed_at->second;
            $minutes = $signature->tenant_signed_at->minute;
            
            if ($seconds === 0 && $minutes % 15 === 0) {
                $patterns[] = [
                    'type' => 'timing_pattern',
                    'description' => 'Signature created at suspiciously round timestamp',
                    'severity' => 'low',
                    'details' => 'Signed at exactly ' . $signature->tenant_signed_at->format('H:i:s')
                ];
            }
        }

        // Log detected patterns
        if (!empty($patterns)) {
            Log::channel('security')->warning('Tampering patterns detected', [
                'signature_id' => $signature->id,
                'patterns_count' => count($patterns),
                'patterns' => $patterns,
                'detected_at' => now()
            ]);
        }

        return $patterns;
    }

    /**
     * Generate comprehensive security report for signature
     */
    public function generateSecurityReport(BailMobiliteSignature $signature): array
    {
        $verification = $this->verifySignatureIntegrity($signature);
        $tamperingPatterns = $this->detectTamperingPatterns($signature);
        
        return [
            'signature_id' => $signature->id,
            'report_generated_at' => now(),
            'generated_by' => auth()->user()?->email ?? 'system',
            
            'integrity_verification' => $verification,
            'tampering_analysis' => [
                'patterns_detected' => count($tamperingPatterns),
                'patterns' => $tamperingPatterns,
                'risk_level' => $this->calculateRiskLevel($tamperingPatterns)
            ],
            
            'compliance_status' => [
                'is_compliant' => $verification['is_valid'] && count($tamperingPatterns) === 0,
                'legal_validity' => $this->assessLegalValidity($signature, $verification, $tamperingPatterns),
                'retention_status' => $this->getRetentionStatus($signature),
                'audit_trail_complete' => $this->isAuditTrailComplete($signature)
            ],
            
            'recommendations' => $this->generateSecurityRecommendations($verification, $tamperingPatterns),
            
            'metadata' => [
                'signature_age_days' => $signature->tenant_signed_at?->diffInDays(now()),
                'last_verified' => now(),
                'verification_count' => 1, // In production, you might track this
                'security_events_count' => count($tamperingPatterns)
            ]
        ];
    }

    /**
     * Calculate risk level based on tampering patterns
     */
    private function calculateRiskLevel(array $patterns): string
    {
        if (empty($patterns)) return 'low';
        
        $highSeverityCount = count(array_filter($patterns, fn($p) => $p['severity'] === 'high'));
        $mediumSeverityCount = count(array_filter($patterns, fn($p) => $p['severity'] === 'medium'));
        
        if ($highSeverityCount > 0) return 'high';
        if ($mediumSeverityCount > 2) return 'high';
        if ($mediumSeverityCount > 0) return 'medium';
        
        return 'low';
    }

    /**
     * Assess legal validity of signature
     */
    private function assessLegalValidity(BailMobiliteSignature $signature, array $verification, array $patterns): array
    {
        $isValid = $verification['is_valid'] && 
                   $verification['security_score'] >= 70 && 
                   count(array_filter($patterns, fn($p) => $p['severity'] === 'high')) === 0;

        $reasons = [];
        if (!$verification['is_valid']) {
            $reasons[] = 'Failed integrity verification';
        }
        if ($verification['security_score'] < 70) {
            $reasons[] = 'Security score below acceptable threshold';
        }
        if (count(array_filter($patterns, fn($p) => $p['severity'] === 'high')) > 0) {
            $reasons[] = 'High-severity tampering patterns detected';
        }

        return [
            'is_legally_valid' => $isValid,
            'confidence_level' => $isValid ? 'high' : 'low',
            'validity_reasons' => $isValid ? ['All security checks passed'] : $reasons,
            'legal_notes' => $this->getLegalNotes($signature, $verification, $patterns)
        ];
    }

    /**
     * Get retention status
     */
    private function getRetentionStatus(BailMobiliteSignature $signature): array
    {
        $retentionPeriodYears = 10;
        $createdAt = $signature->created_at;
        $retentionUntil = $createdAt->copy()->addYears($retentionPeriodYears);
        $daysRemaining = now()->diffInDays($retentionUntil, false);

        return [
            'retention_period_years' => $retentionPeriodYears,
            'retention_until' => $retentionUntil,
            'days_remaining' => max(0, $daysRemaining),
            'is_expired' => $daysRemaining < 0,
            'should_archive' => $daysRemaining < 365 && $daysRemaining > 0
        ];
    }

    /**
     * Check if audit trail is complete
     */
    private function isAuditTrailComplete(BailMobiliteSignature $signature): bool
    {
        $requiredFields = [
            'tenant_signed_at',
            'signature_metadata',
            'contract_pdf_path'
        ];

        foreach ($requiredFields as $field) {
            if (empty($signature->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate security recommendations
     */
    private function generateSecurityRecommendations(array $verification, array $patterns): array
    {
        $recommendations = [];

        if ($verification['security_score'] < 90) {
            $recommendations[] = [
                'type' => 'security_improvement',
                'priority' => 'medium',
                'description' => 'Consider implementing additional security measures for future signatures',
                'action' => 'Review signature capture process and metadata collection'
            ];
        }

        if (!empty($patterns)) {
            $recommendations[] = [
                'type' => 'investigation',
                'priority' => 'high',
                'description' => 'Investigate detected tampering patterns',
                'action' => 'Review signature creation circumstances and verify with tenant'
            ];
        }

        if (count($verification['errors']) > 0) {
            $recommendations[] = [
                'type' => 'integrity_issue',
                'priority' => 'critical',
                'description' => 'Address integrity verification failures',
                'action' => 'Contact legal team and consider signature re-capture if possible'
            ];
        }

        return $recommendations;
    }

    /**
     * Get legal notes for compliance
     */
    private function getLegalNotes(BailMobiliteSignature $signature, array $verification, array $patterns): array
    {
        $notes = [];

        $notes[] = "Electronic signature created on " . $signature->tenant_signed_at?->format('Y-m-d H:i:s T');
        $notes[] = "Security verification score: {$verification['security_score']}/100";
        
        if ($verification['is_valid']) {
            $notes[] = "Signature passed all integrity checks";
        } else {
            $notes[] = "WARNING: Signature failed integrity verification";
        }

        if (!empty($patterns)) {
            $notes[] = "Security analysis detected " . count($patterns) . " potential issues";
        }

        $metadata = $signature->signature_metadata ?? [];
        if (isset($metadata['ip_address'])) {
            $notes[] = "Signed from IP address: " . $metadata['ip_address'];
        }

        return $notes;
    }
}