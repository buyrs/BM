<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;

class DataEncryptionService
{
    private const ENCRYPTION_VERSION = 'v1';
    private const HASH_ALGORITHM = 'sha256';
    
    /**
     * Sensitive fields that should always be encrypted
     */
    private const SENSITIVE_FIELDS = [
        'signature_data',
        'tenant_signature',
        'admin_signature',
        'signature_metadata',
        'contract_content',
        'tenant_phone',
        'tenant_email',
        'personal_notes',
        'private_comments',
        'api_key',
        'secret_key',
        'password_hash',
        'recovery_codes',
        'two_factor_secret'
    ];

    /**
     * Fields that should be hashed for searching while keeping original encrypted
     */
    private const SEARCHABLE_ENCRYPTED_FIELDS = [
        'tenant_email',
        'tenant_phone'
    ];

    /**
     * Encrypt sensitive data with versioning and integrity checking
     */
    public function encryptSensitiveData(string $data, array $metadata = []): array
    {
        try {
            // Generate unique encryption ID for tracking
            $encryptionId = $this->generateEncryptionId();
            
            // Create encryption metadata
            $encryptionMetadata = [
                'encryption_id' => $encryptionId,
                'version' => self::ENCRYPTION_VERSION,
                'algorithm' => 'AES-256-CBC',
                'encrypted_at' => now()->toISOString(),
                'data_type' => $this->detectDataType($data),
                'original_size' => strlen($data),
                'metadata' => $metadata
            ];

            // Generate integrity hash before encryption
            $originalHash = hash(self::HASH_ALGORITHM, $data);
            $encryptionMetadata['original_hash'] = $originalHash;

            // Encrypt the data
            $encryptedData = Crypt::encrypt($data);
            
            // Generate post-encryption hash for storage verification
            $encryptedHash = hash(self::HASH_ALGORITHM, $encryptedData);
            $encryptionMetadata['encrypted_hash'] = $encryptedHash;
            $encryptionMetadata['encrypted_size'] = strlen($encryptedData);

            // Log encryption operation
            $this->logEncryptionOperation('encrypt', $encryptionId, $encryptionMetadata);

            return [
                'encrypted_data' => $encryptedData,
                'metadata' => $encryptionMetadata,
                'success' => true
            ];

        } catch (EncryptException $e) {
            Log::error('Data encryption failed', [
                'error' => $e->getMessage(),
                'data_size' => strlen($data),
                'metadata' => $metadata
            ]);

            throw new \Exception('Failed to encrypt sensitive data: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt sensitive data with integrity verification
     */
    public function decryptSensitiveData(string $encryptedData, array $metadata = []): array
    {
        try {
            $encryptionId = $metadata['encryption_id'] ?? 'unknown';

            // Verify encrypted data integrity if hash is available
            if (isset($metadata['encrypted_hash'])) {
                $currentEncryptedHash = hash(self::HASH_ALGORITHM, $encryptedData);
                if ($currentEncryptedHash !== $metadata['encrypted_hash']) {
                    throw new \Exception('Encrypted data integrity check failed');
                }
            }

            // Decrypt the data
            $decryptedData = Crypt::decrypt($encryptedData);

            // Verify original data integrity if hash is available
            if (isset($metadata['original_hash'])) {
                $currentOriginalHash = hash(self::HASH_ALGORITHM, $decryptedData);
                if ($currentOriginalHash !== $metadata['original_hash']) {
                    throw new \Exception('Decrypted data integrity check failed');
                }
            }

            // Log decryption operation
            $this->logEncryptionOperation('decrypt', $encryptionId, $metadata);

            return [
                'decrypted_data' => $decryptedData,
                'metadata' => $metadata,
                'integrity_verified' => true,
                'success' => true
            ];

        } catch (DecryptException $e) {
            Log::error('Data decryption failed', [
                'error' => $e->getMessage(),
                'encryption_id' => $encryptionId,
                'metadata' => $metadata
            ]);

            throw new \Exception('Failed to decrypt sensitive data: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt model attributes that contain sensitive data
     */
    public function encryptModelAttributes(array $attributes): array
    {
        $encryptedAttributes = [];
        $encryptionLog = [];

        foreach ($attributes as $key => $value) {
            if ($this->isSensitiveField($key) && !empty($value)) {
                try {
                    $encryptionResult = $this->encryptSensitiveData(
                        is_array($value) ? json_encode($value) : (string)$value,
                        ['field_name' => $key, 'model_context' => true]
                    );

                    $encryptedAttributes[$key] = $encryptionResult['encrypted_data'];
                    $encryptedAttributes[$key . '_encryption_metadata'] = $encryptionResult['metadata'];
                    
                    $encryptionLog[] = [
                        'field' => $key,
                        'encryption_id' => $encryptionResult['metadata']['encryption_id'],
                        'status' => 'encrypted'
                    ];

                    // Create searchable hash for searchable encrypted fields
                    if (in_array($key, self::SEARCHABLE_ENCRYPTED_FIELDS)) {
                        $encryptedAttributes[$key . '_search_hash'] = hash(self::HASH_ALGORITHM, strtolower(trim($value)));
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to encrypt model attribute', [
                        'field' => $key,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Keep original value if encryption fails (with warning)
                    $encryptedAttributes[$key] = $value;
                    $encryptionLog[] = [
                        'field' => $key,
                        'status' => 'encryption_failed',
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                $encryptedAttributes[$key] = $value;
            }
        }

        // Log encryption summary
        if (!empty($encryptionLog)) {
            Log::channel('security')->info('Model attributes encrypted', [
                'encrypted_fields' => count(array_filter($encryptionLog, fn($log) => $log['status'] === 'encrypted')),
                'failed_fields' => count(array_filter($encryptionLog, fn($log) => $log['status'] === 'encryption_failed')),
                'encryption_log' => $encryptionLog
            ]);
        }

        return $encryptedAttributes;
    }

    /**
     * Decrypt model attributes that contain encrypted sensitive data
     */
    public function decryptModelAttributes(array $attributes): array
    {
        $decryptedAttributes = [];
        $decryptionLog = [];

        foreach ($attributes as $key => $value) {
            // Skip metadata fields
            if (str_ends_with($key, '_encryption_metadata') || str_ends_with($key, '_search_hash')) {
                continue;
            }

            if ($this->isSensitiveField($key) && !empty($value)) {
                $metadataKey = $key . '_encryption_metadata';
                $metadata = $attributes[$metadataKey] ?? [];

                try {
                    $decryptionResult = $this->decryptSensitiveData($value, $metadata);
                    $decryptedData = $decryptionResult['decrypted_data'];

                    // Try to decode JSON if it was originally an array
                    if (isset($metadata['data_type']) && $metadata['data_type'] === 'array') {
                        $jsonDecoded = json_decode($decryptedData, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decryptedData = $jsonDecoded;
                        }
                    }

                    $decryptedAttributes[$key] = $decryptedData;
                    $decryptionLog[] = [
                        'field' => $key,
                        'encryption_id' => $metadata['encryption_id'] ?? 'unknown',
                        'status' => 'decrypted',
                        'integrity_verified' => $decryptionResult['integrity_verified']
                    ];

                } catch (\Exception $e) {
                    Log::error('Failed to decrypt model attribute', [
                        'field' => $key,
                        'encryption_id' => $metadata['encryption_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    
                    // Keep encrypted value if decryption fails
                    $decryptedAttributes[$key] = $value;
                    $decryptionLog[] = [
                        'field' => $key,
                        'status' => 'decryption_failed',
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                $decryptedAttributes[$key] = $value;
            }
        }

        // Log decryption summary
        if (!empty($decryptionLog)) {
            Log::channel('security')->info('Model attributes decrypted', [
                'decrypted_fields' => count(array_filter($decryptionLog, fn($log) => $log['status'] === 'decrypted')),
                'failed_fields' => count(array_filter($decryptionLog, fn($log) => $log['status'] === 'decryption_failed')),
                'decryption_log' => $decryptionLog
            ]);
        }

        return $decryptedAttributes;
    }

    /**
     * Create searchable hash for encrypted field
     */
    public function createSearchableHash(string $value): string
    {
        return hash(self::HASH_ALGORITHM, strtolower(trim($value)));
    }

    /**
     * Search encrypted fields by hash
     */
    public function searchEncryptedField(string $searchValue, string $fieldName): string
    {
        if (!in_array($fieldName, self::SEARCHABLE_ENCRYPTED_FIELDS)) {
            throw new \Exception("Field {$fieldName} is not configured for searchable encryption");
        }

        return $this->createSearchableHash($searchValue);
    }

    /**
     * Rotate encryption keys for existing encrypted data
     */
    public function rotateEncryptionKeys(string $encryptedData, array $oldMetadata): array
    {
        try {
            // Decrypt with old key
            $decryptedData = $this->decryptSensitiveData($encryptedData, $oldMetadata);
            
            // Re-encrypt with new key
            $newEncryptionResult = $this->encryptSensitiveData(
                $decryptedData['decrypted_data'],
                array_merge($oldMetadata['metadata'] ?? [], ['key_rotation' => true])
            );

            // Log key rotation
            Log::channel('security')->info('Encryption key rotated', [
                'old_encryption_id' => $oldMetadata['encryption_id'] ?? 'unknown',
                'new_encryption_id' => $newEncryptionResult['metadata']['encryption_id'],
                'rotated_at' => now()->toISOString()
            ]);

            return $newEncryptionResult;

        } catch (\Exception $e) {
            Log::error('Encryption key rotation failed', [
                'old_encryption_id' => $oldMetadata['encryption_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Failed to rotate encryption keys: ' . $e->getMessage());
        }
    }

    /**
     * Verify data encryption integrity
     */
    public function verifyEncryptionIntegrity(string $encryptedData, array $metadata): array
    {
        $result = [
            'is_valid' => false,
            'checks_performed' => [],
            'errors' => [],
            'metadata' => $metadata
        ];

        try {
            // Check 1: Verify encrypted data hash
            if (isset($metadata['encrypted_hash'])) {
                $currentEncryptedHash = hash(self::HASH_ALGORITHM, $encryptedData);
                if ($currentEncryptedHash === $metadata['encrypted_hash']) {
                    $result['checks_performed'][] = 'encrypted_hash_verified';
                } else {
                    $result['errors'][] = 'Encrypted data hash mismatch';
                    return $result;
                }
            }

            // Check 2: Attempt decryption and verify original hash
            $decryptionResult = $this->decryptSensitiveData($encryptedData, $metadata);
            $result['checks_performed'][] = 'decryption_successful';

            if ($decryptionResult['integrity_verified']) {
                $result['checks_performed'][] = 'original_hash_verified';
                $result['is_valid'] = true;
            } else {
                $result['errors'][] = 'Original data hash verification failed';
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Decryption failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get encryption statistics
     */
    public function getEncryptionStatistics(): array
    {
        // This would typically query your database for encrypted fields
        // For now, return basic statistics
        return [
            'total_encrypted_fields' => count(self::SENSITIVE_FIELDS),
            'searchable_encrypted_fields' => count(self::SEARCHABLE_ENCRYPTED_FIELDS),
            'encryption_version' => self::ENCRYPTION_VERSION,
            'hash_algorithm' => self::HASH_ALGORITHM,
            'supported_algorithms' => ['AES-256-CBC'],
            'last_key_rotation' => null, // Would be stored in config/database
            'encryption_health' => 'healthy' // Would be determined by integrity checks
        ];
    }

    /**
     * Generate unique encryption ID
     */
    private function generateEncryptionId(): string
    {
        return 'enc_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4));
    }

    /**
     * Detect data type for proper handling during decryption
     */
    private function detectDataType($data): string
    {
        if (is_array($data)) {
            return 'array';
        } elseif (is_object($data)) {
            return 'object';
        } elseif (is_numeric($data)) {
            return 'numeric';
        } elseif (is_bool($data)) {
            return 'boolean';
        } else {
            return 'string';
        }
    }

    /**
     * Check if field contains sensitive data
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $fieldLower = strtolower($fieldName);
        
        // Check exact matches
        if (in_array($fieldLower, array_map('strtolower', self::SENSITIVE_FIELDS))) {
            return true;
        }

        // Check patterns
        $sensitivePatterns = [
            'signature',
            'password',
            'secret',
            'token',
            'key',
            'phone',
            'email',
            'ssn',
            'credit_card',
            'bank_account'
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($fieldLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log encryption/decryption operations
     */
    private function logEncryptionOperation(string $operation, string $encryptionId, array $metadata): void
    {
        Log::channel('security')->info("Data {$operation} operation", [
            'operation' => $operation,
            'encryption_id' => $encryptionId,
            'version' => $metadata['version'] ?? 'unknown',
            'algorithm' => $metadata['algorithm'] ?? 'unknown',
            'data_type' => $metadata['data_type'] ?? 'unknown',
            'original_size' => $metadata['original_size'] ?? 0,
            'encrypted_size' => $metadata['encrypted_size'] ?? 0,
            'user_id' => auth()->user()?->id,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Securely wipe sensitive data from memory
     */
    public function secureWipe(string &$data): void
    {
        // Overwrite the string with random data multiple times
        $length = strlen($data);
        for ($i = 0; $i < 3; $i++) {
            $data = str_repeat(chr(random_int(0, 255)), $length);
        }
        
        // Finally set to empty string
        $data = '';
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Create encrypted backup of sensitive data
     */
    public function createEncryptedBackup(array $sensitiveData, string $backupId): string
    {
        $backupData = [
            'backup_id' => $backupId,
            'created_at' => now()->toISOString(),
            'data_count' => count($sensitiveData),
            'encrypted_data' => []
        ];

        foreach ($sensitiveData as $key => $value) {
            $encryptionResult = $this->encryptSensitiveData(
                json_encode($value),
                ['backup_context' => true, 'original_key' => $key]
            );
            
            $backupData['encrypted_data'][$key] = $encryptionResult;
        }

        // Encrypt the entire backup
        $encryptedBackup = $this->encryptSensitiveData(
            json_encode($backupData),
            ['backup_id' => $backupId, 'backup_type' => 'full']
        );

        Log::channel('security')->info('Encrypted backup created', [
            'backup_id' => $backupId,
            'data_count' => count($sensitiveData),
            'backup_size' => strlen($encryptedBackup['encrypted_data']),
            'created_by' => auth()->user()?->email
        ]);

        return $encryptedBackup['encrypted_data'];
    }

    /**
     * Restore from encrypted backup
     */
    public function restoreFromEncryptedBackup(string $encryptedBackup, array $backupMetadata): array
    {
        try {
            // Decrypt the backup
            $decryptionResult = $this->decryptSensitiveData($encryptedBackup, $backupMetadata);
            $backupData = json_decode($decryptionResult['decrypted_data'], true);

            $restoredData = [];
            foreach ($backupData['encrypted_data'] as $key => $encryptedItem) {
                $itemDecryption = $this->decryptSensitiveData(
                    $encryptedItem['encrypted_data'],
                    $encryptedItem['metadata']
                );
                
                $restoredData[$key] = json_decode($itemDecryption['decrypted_data'], true);
            }

            Log::channel('security')->info('Encrypted backup restored', [
                'backup_id' => $backupData['backup_id'],
                'data_count' => count($restoredData),
                'restored_by' => auth()->user()?->email
            ]);

            return $restoredData;

        } catch (\Exception $e) {
            Log::error('Failed to restore encrypted backup', [
                'error' => $e->getMessage(),
                'backup_metadata' => $backupMetadata
            ]);

            throw new \Exception('Failed to restore encrypted backup: ' . $e->getMessage());
        }
    }
}