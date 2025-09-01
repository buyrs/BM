<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SignatureService
{
    /**
     * Create a tenant signature for a bail mobilite
     */
    public function createTenantSignature(
        BailMobilite $bailMobilite,
        string $signatureType,
        string $signatureData,
        array $metadata = []
    ): BailMobiliteSignature {
        // Get the active contract template for this signature type
        $contractTemplate = ContractTemplate::active()
            ->where('type', $signatureType)
            ->first();

        if (!$contractTemplate) {
            throw new \Exception("No active contract template found for type: {$signatureType}");
        }

        if (!$contractTemplate->admin_signature) {
            throw new \Exception("Contract template must be signed by admin before tenant can sign");
        }

        // Capture comprehensive signature metadata for legal verification
        $signatureMetadata = $this->captureSignatureMetadata($metadata);
        
        // Encrypt sensitive signature data
        $encryptedSignatureData = $this->encryptSignatureData($signatureData);

        // Create signature record with comprehensive metadata
        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => $signatureType,
            'contract_template_id' => $contractTemplate->id,
            'tenant_signature' => $encryptedSignatureData,
            'tenant_signed_at' => now(),
            'signature_metadata' => $signatureMetadata
        ]);

        // Generate integrity hash for the signature
        $signature->update([
            'signature_metadata' => array_merge($signatureMetadata, [
                'integrity_hash' => $this->generateIntegrityHash($signature, $signatureData)
            ])
        ]);

        // Generate the PDF contract with both signatures
        $pdfPath = $this->generateSignedContract($signature, $signatureData);
        $signature->update(['contract_pdf_path' => $pdfPath]);

        // Log signature creation with security details
        Log::channel('security')->info('Tenant signature created', [
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => $signatureType,
            'signature_id' => $signature->id,
            'ip_address' => $signatureMetadata['ip_address'],
            'user_agent_hash' => hash('sha256', $signatureMetadata['user_agent']),
            'timestamp' => $signatureMetadata['timestamp']
        ]);

        return $signature;
    }

    /**
     * Capture comprehensive signature metadata for legal verification
     */
    private function captureSignatureMetadata(array $additionalMetadata = []): array
    {
        $request = request();
        
        return array_merge($additionalMetadata, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'geolocation' => $this->getGeolocationFromIP($request->ip()),
            'browser_info' => $this->parseBrowserInfo($request->userAgent()),
            'security_headers' => $this->captureSecurityHeaders($request),
            'signature_method' => 'electronic_pad',
            'verification_level' => 'standard'
        ]);
    }

    /**
     * Generate device fingerprint for additional security
     */
    private function generateDeviceFingerprint($request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->header('Accept'),
            $request->ip()
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Get approximate geolocation from IP (for logging purposes)
     */
    private function getGeolocationFromIP(string $ip): ?array
    {
        // In production, you might want to use a geolocation service
        // For now, just return basic info
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [
                'ip' => $ip,
                'type' => 'public',
                'note' => 'Geolocation service not implemented'
            ];
        }

        return [
            'ip' => $ip,
            'type' => 'private',
            'note' => 'Private IP address'
        ];
    }

    /**
     * Parse browser information from user agent
     */
    private function parseBrowserInfo(string $userAgent): array
    {
        return [
            'user_agent_hash' => hash('sha256', $userAgent),
            'length' => strlen($userAgent),
            'contains_mobile' => stripos($userAgent, 'mobile') !== false,
            'contains_tablet' => stripos($userAgent, 'tablet') !== false
        ];
    }

    /**
     * Capture relevant security headers
     */
    private function captureSecurityHeaders($request): array
    {
        $securityHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Forwarded-Proto',
            'Referer',
            'Origin'
        ];

        $headers = [];
        foreach ($securityHeaders as $header) {
            if ($request->hasHeader($header)) {
                $headers[$header] = hash('sha256', $request->header($header));
            }
        }

        return $headers;
    }

    /**
     * Encrypt signature data for secure storage
     */
    private function encryptSignatureData(string $signatureData): string
    {
        return encrypt($signatureData);
    }

    /**
     * Decrypt signature data for verification
     */
    private function decryptSignatureData(string $encryptedData): string
    {
        try {
            return decrypt($encryptedData);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt signature data', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Signature data corruption detected');
        }
    }

    /**
     * Generate integrity hash for signature verification
     */
    private function generateIntegrityHash(BailMobiliteSignature $signature, string $originalSignatureData): string
    {
        $components = [
            $signature->bail_mobilite_id,
            $signature->signature_type,
            $signature->contract_template_id,
            $originalSignatureData,
            $signature->tenant_signed_at->toISOString()
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Verify signature integrity
     */
    public function verifySignatureIntegrity(BailMobiliteSignature $signature): bool
    {
        try {
            $originalSignatureData = $this->decryptSignatureData($signature->tenant_signature);
            $expectedHash = $this->generateIntegrityHash($signature, $originalSignatureData);
            $storedHash = $signature->signature_metadata['integrity_hash'] ?? null;

            return $expectedHash === $storedHash;
        } catch (\Exception $e) {
            Log::error('Signature integrity verification failed', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate PDF contract with both admin and tenant signatures
     */
    public function generateSignedContract(BailMobiliteSignature $signature, string $originalSignatureData = null): string
    {
        $bailMobilite = $signature->bailMobilite;
        $contractTemplate = $signature->contractTemplate;

        // Decrypt signature data for PDF generation
        $tenantSignatureData = $originalSignatureData ?? $this->decryptSignatureData($signature->tenant_signature);

        // Prepare contract data
        $contractData = [
            'bail_mobilite' => $bailMobilite,
            'contract_template' => $contractTemplate,
            'signature' => $signature,
            'tenant_info' => [
                'name' => $bailMobilite->tenant_name,
                'email' => $bailMobilite->tenant_email,
                'phone' => $bailMobilite->tenant_phone,
            ],
            'property_info' => [
                'address' => $bailMobilite->address,
                'start_date' => $bailMobilite->start_date,
                'end_date' => $bailMobilite->end_date,
            ],
            'signatures' => [
                'admin' => [
                    'signature' => $contractTemplate->admin_signature,
                    'signed_at' => $contractTemplate->admin_signed_at,
                    'name' => $contractTemplate->creator->name ?? 'Administrator'
                ],
                'tenant' => [
                    'signature' => $tenantSignatureData,
                    'signed_at' => $signature->tenant_signed_at,
                    'name' => $bailMobilite->tenant_name
                ]
            ],
            'security_info' => [
                'signature_id' => $signature->id,
                'integrity_verified' => $this->verifySignatureIntegrity($signature),
                'generated_at' => now(),
                'ip_address' => $signature->signature_metadata['ip_address'] ?? 'unknown'
            ]
        ];

        // Generate PDF
        $pdf = Pdf::loadView('contracts.signed-contract', $contractData);
        
        // Create filename with timestamp and security hash
        $filename = sprintf(
            'contracts/%s_%s_%s_%s.pdf',
            $bailMobilite->id,
            $signature->signature_type,
            $signature->tenant_signed_at->format('Y-m-d_H-i-s'),
            substr(hash('sha256', $signature->id . $signature->tenant_signed_at), 0, 8)
        );

        // Store PDF securely with additional metadata
        $pdfContent = $pdf->output();
        Storage::disk('private')->put($filename, $pdfContent);
        
        // Store PDF hash for integrity verification
        $pdfHash = hash('sha256', $pdfContent);
        Storage::disk('private')->put($filename . '.hash', $pdfHash);

        Log::channel('security')->info('Signed contract PDF generated', [
            'signature_id' => $signature->id,
            'filename' => $filename,
            'pdf_hash' => $pdfHash,
            'integrity_verified' => $contractData['security_info']['integrity_verified']
        ]);

        return $filename;
    }

    /**
     * Validate signature integrity and security
     */
    public function validateSignature(BailMobiliteSignature $signature): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'security_checks' => []
        ];

        // Check if signature data exists
        if (empty($signature->tenant_signature)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Tenant signature is missing';
        } else {
            // Test signature decryption
            try {
                $this->decryptSignatureData($signature->tenant_signature);
                $validation['security_checks']['encryption'] = 'valid';
            } catch (\Exception $e) {
                $validation['is_valid'] = false;
                $validation['errors'][] = 'Signature data is corrupted or cannot be decrypted';
                $validation['security_checks']['encryption'] = 'failed';
            }
        }

        // Check if admin signature exists in template
        if (empty($signature->contractTemplate->admin_signature)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Admin signature is missing from contract template';
        }

        // Verify signature integrity hash
        if (!$this->verifySignatureIntegrity($signature)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Signature integrity verification failed';
            $validation['security_checks']['integrity'] = 'failed';
        } else {
            $validation['security_checks']['integrity'] = 'valid';
        }

        // Check if PDF exists and verify its integrity
        if ($signature->contract_pdf_path) {
            if (!Storage::disk('private')->exists($signature->contract_pdf_path)) {
                $validation['is_valid'] = false;
                $validation['errors'][] = 'Signed contract PDF file is missing';
                $validation['security_checks']['pdf_exists'] = 'missing';
            } else {
                $validation['security_checks']['pdf_exists'] = 'valid';
                
                // Verify PDF hash if it exists
                $hashFile = $signature->contract_pdf_path . '.hash';
                if (Storage::disk('private')->exists($hashFile)) {
                    $storedHash = Storage::disk('private')->get($hashFile);
                    $currentHash = hash('sha256', Storage::disk('private')->get($signature->contract_pdf_path));
                    
                    if ($storedHash !== $currentHash) {
                        $validation['is_valid'] = false;
                        $validation['errors'][] = 'PDF file integrity verification failed';
                        $validation['security_checks']['pdf_integrity'] = 'failed';
                    } else {
                        $validation['security_checks']['pdf_integrity'] = 'valid';
                    }
                } else {
                    $validation['warnings'][] = 'PDF integrity hash not found (older signature)';
                    $validation['security_checks']['pdf_integrity'] = 'unknown';
                }
            }
        }

        // Check signature timestamp
        if (!$signature->tenant_signed_at) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Signature timestamp is missing';
        }

        // Check metadata completeness
        $metadata = $signature->signature_metadata ?? [];
        $requiredMetadata = ['ip_address', 'user_agent', 'timestamp', 'device_fingerprint'];
        $missingMetadata = array_diff($requiredMetadata, array_keys($metadata));
        
        if (!empty($missingMetadata)) {
            $validation['warnings'][] = 'Some signature metadata is missing: ' . implode(', ', $missingMetadata);
            $validation['security_checks']['metadata_complete'] = 'partial';
        } else {
            $validation['security_checks']['metadata_complete'] = 'complete';
        }

        // Check if signature is too old (warning only)
        if ($signature->tenant_signed_at && $signature->tenant_signed_at->diffInDays(now()) > 365) {
            $validation['warnings'][] = 'Signature is older than 1 year';
        }

        // Check for suspicious patterns
        if (isset($metadata['ip_address']) && $this->isSuspiciousIP($metadata['ip_address'])) {
            $validation['warnings'][] = 'Signature created from potentially suspicious IP address';
            $validation['security_checks']['ip_reputation'] = 'suspicious';
        } else {
            $validation['security_checks']['ip_reputation'] = 'clean';
        }

        return $validation;
    }

    /**
     * Check if IP address is suspicious (basic implementation)
     */
    private function isSuspiciousIP(string $ip): bool
    {
        // Basic checks - in production you might want to use a threat intelligence service
        $suspiciousPatterns = [
            '127.0.0.1', // localhost (might be suspicious in production)
            '0.0.0.0',   // invalid IP
        ];

        return in_array($ip, $suspiciousPatterns);
    }

    /**
     * Get comprehensive signature metadata for legal verification
     */
    public function getSignatureMetadata(BailMobiliteSignature $signature): array
    {
        $validation = $this->validateSignature($signature);
        $metadata = $signature->signature_metadata ?? [];
        
        return [
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'signature_type' => $signature->signature_type,
            'tenant_signed_at' => $signature->tenant_signed_at,
            'admin_signed_at' => $signature->contractTemplate->admin_signed_at,
            'contract_template_id' => $signature->contract_template_id,
            'contract_template_name' => $signature->contractTemplate->name,
            'pdf_path' => $signature->contract_pdf_path,
            
            // Security metadata
            'security_metadata' => [
                'ip_address' => $metadata['ip_address'] ?? null,
                'device_fingerprint' => $metadata['device_fingerprint'] ?? null,
                'session_id' => $metadata['session_id'] ?? null,
                'user_agent_hash' => isset($metadata['user_agent']) ? hash('sha256', $metadata['user_agent']) : null,
                'geolocation' => $metadata['geolocation'] ?? null,
                'browser_info' => $metadata['browser_info'] ?? null,
                'security_headers' => $metadata['security_headers'] ?? null,
                'signature_method' => $metadata['signature_method'] ?? 'unknown',
                'verification_level' => $metadata['verification_level'] ?? 'unknown',
                'integrity_hash' => $metadata['integrity_hash'] ?? null,
            ],
            
            // Validation results
            'validation' => $validation,
            
            // Legal compliance info
            'legal_info' => [
                'retention_period_years' => 10,
                'created_at' => $signature->created_at,
                'updated_at' => $signature->updated_at,
                'is_legally_valid' => $validation['is_valid'] && $signature->isComplete(),
                'compliance_notes' => $this->getComplianceNotes($signature, $validation)
            ],
            
            // Audit trail
            'audit_trail' => [
                'signature_created' => $signature->created_at,
                'last_validated' => now(),
                'validation_count' => $this->getValidationCount($signature),
                'access_log_available' => true
            ]
        ];
    }

    /**
     * Get compliance notes for legal documentation
     */
    private function getComplianceNotes(BailMobiliteSignature $signature, array $validation): array
    {
        $notes = [];
        
        if ($validation['is_valid']) {
            $notes[] = 'Signature passes all integrity and security checks';
        }
        
        if (!empty($validation['warnings'])) {
            $notes[] = 'Warnings present: ' . implode('; ', $validation['warnings']);
        }
        
        if (!empty($validation['errors'])) {
            $notes[] = 'Errors detected: ' . implode('; ', $validation['errors']);
        }
        
        $metadata = $signature->signature_metadata ?? [];
        if (isset($metadata['ip_address'])) {
            $notes[] = 'Signed from IP: ' . $metadata['ip_address'];
        }
        
        if (isset($metadata['device_fingerprint'])) {
            $notes[] = 'Device fingerprint recorded for verification';
        }
        
        return $notes;
    }

    /**
     * Get validation count (placeholder - in production you might track this)
     */
    private function getValidationCount(BailMobiliteSignature $signature): int
    {
        // In production, you might want to track how many times a signature has been validated
        return 1;
    }

    /**
     * Retrieve signed contract PDF
     */
    public function getSignedContractPdf(BailMobiliteSignature $signature): ?string
    {
        if (!$signature->contract_pdf_path) {
            return null;
        }

        if (!Storage::disk('private')->exists($signature->contract_pdf_path)) {
            Log::warning('Signed contract PDF not found', [
                'signature_id' => $signature->id,
                'pdf_path' => $signature->contract_pdf_path
            ]);
            return null;
        }

        return Storage::disk('private')->get($signature->contract_pdf_path);
    }

    /**
     * Log signature access for audit trail
     */
    public function logSignatureAccess(BailMobiliteSignature $signature, string $action, ?User $user = null): void
    {
        $logData = [
            'event' => 'signature_access',
            'action' => $action,
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'signature_type' => $signature->signature_type,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($user) {
            $logData['user_id'] = $user->id;
            $logData['user_email'] = $user->email;
            $logData['user_roles'] = $user->getRoleNames()->toArray();
        }

        Log::channel('security')->info('Signature accessed', $logData);
    }

    /**
     * Create complete audit trail for signature
     */
    public function createAuditTrail(BailMobiliteSignature $signature): array
    {
        return [
            'signature_info' => [
                'id' => $signature->id,
                'type' => $signature->signature_type,
                'created_at' => $signature->created_at,
                'signed_at' => $signature->tenant_signed_at,
            ],
            'security_verification' => $this->validateSignature($signature),
            'metadata' => $this->getSignatureMetadata($signature),
            'integrity_status' => $this->verifySignatureIntegrity($signature),
            'audit_timestamp' => now(),
            'audit_user' => auth()->user()?->email ?? 'system',
        ];
    }

    /**
     * Archive signatures for legal retention with enhanced security
     */
    public function archiveSignatures(BailMobilite $bailMobilite): void
    {
        $signatures = $bailMobilite->signatures;

        foreach ($signatures as $signature) {
            // Create comprehensive archive record
            $archiveData = [
                'original_signature_id' => $signature->id,
                'bail_mobilite_data' => $bailMobilite->toArray(),
                'signature_data' => $signature->toArray(),
                'contract_template_data' => $signature->contractTemplate->toArray(),
                'validation_data' => $this->validateSignature($signature),
                'security_metadata' => $this->getSignatureMetadata($signature),
                'audit_trail' => $this->createAuditTrail($signature),
                'archived_at' => now(),
                'archived_by' => auth()->user()?->email ?? 'system',
                'retention_until' => now()->addYears(10), // Legal retention period
            ];

            // Store archive data with encryption
            $archiveFilename = sprintf(
                'archives/signatures/%s_%s_%s_archive.json',
                $bailMobilite->id,
                $signature->signature_type,
                now()->format('Y-m-d_H-i-s')
            );

            $encryptedArchiveData = encrypt(json_encode($archiveData, JSON_PRETTY_PRINT));
            Storage::disk('private')->put($archiveFilename, $encryptedArchiveData);

            // Create archive hash for integrity verification
            $archiveHash = hash('sha256', $encryptedArchiveData);
            Storage::disk('private')->put($archiveFilename . '.hash', $archiveHash);

            // Log archival with security details
            Log::channel('security')->info('Signature archived', [
                'signature_id' => $signature->id,
                'archive_file' => $archiveFilename,
                'archive_hash' => $archiveHash,
                'retention_until' => $archiveData['retention_until'],
                'archived_by' => $archiveData['archived_by']
            ]);
        }
    }

    /**
     * Verify archived signature integrity
     */
    public function verifyArchivedSignature(string $archiveFilename): array
    {
        try {
            // Check if archive file exists
            if (!Storage::disk('private')->exists($archiveFilename)) {
                return ['valid' => false, 'error' => 'Archive file not found'];
            }

            // Check if hash file exists
            $hashFile = $archiveFilename . '.hash';
            if (!Storage::disk('private')->exists($hashFile)) {
                return ['valid' => false, 'error' => 'Archive hash file not found'];
            }

            // Verify file integrity
            $archiveContent = Storage::disk('private')->get($archiveFilename);
            $storedHash = Storage::disk('private')->get($hashFile);
            $currentHash = hash('sha256', $archiveContent);

            if ($storedHash !== $currentHash) {
                return ['valid' => false, 'error' => 'Archive file integrity check failed'];
            }

            // Decrypt and validate archive data
            $archiveData = json_decode(decrypt($archiveContent), true);
            
            if (!$archiveData) {
                return ['valid' => false, 'error' => 'Failed to decrypt or parse archive data'];
            }

            return [
                'valid' => true,
                'archive_data' => $archiveData,
                'verified_at' => now(),
                'integrity_confirmed' => true
            ];

        } catch (\Exception $e) {
            Log::error('Archive verification failed', [
                'archive_file' => $archiveFilename,
                'error' => $e->getMessage()
            ]);

            return ['valid' => false, 'error' => 'Archive verification failed: ' . $e->getMessage()];
        }
    }
}