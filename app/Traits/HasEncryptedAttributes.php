<?php

namespace App\Traits;

use App\Services\DataEncryptionService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

trait HasEncryptedAttributes
{
    /**
     * The attributes that should be encrypted (defined in model)
     */
    // protected $encrypted = [];

    /**
     * The attributes that should be searchable while encrypted (defined in model)
     */
    // protected $searchableEncrypted = [];

    /**
     * Boot the trait
     */
    public static function bootHasEncryptedAttributes()
    {
        // Encrypt attributes before saving
        static::saving(function ($model) {
            $model->encryptAttributes();
        });

        // Decrypt attributes after retrieving
        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });

        // Log attribute access for sensitive models
        static::retrieved(function ($model) {
            if ($model->isSensitiveModel()) {
                AuditService::logViewed($model, auth()->user(), [
                    'access_type' => 'model_retrieved',
                    'encrypted_fields' => $model->getEncryptedAttributes()
                ]);
            }
        });
    }

    /**
     * Encrypt specified attributes before saving
     */
    protected function encryptAttributes(): void
    {
        $encryptionService = app(DataEncryptionService::class);
        
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                $originalValue = $this->attributes[$attribute];
                
                // Skip if already encrypted (check for encryption metadata)
                if (isset($this->attributes[$attribute . '_encryption_metadata'])) {
                    continue;
                }

                try {
                    $encryptionResult = $encryptionService->encryptSensitiveData(
                        is_array($originalValue) ? json_encode($originalValue) : (string)$originalValue,
                        [
                            'model' => get_class($this),
                            'attribute' => $attribute,
                            'model_id' => $this->getKey()
                        ]
                    );

                    $this->attributes[$attribute] = $encryptionResult['encrypted_data'];
                    $this->attributes[$attribute . '_encryption_metadata'] = json_encode($encryptionResult['metadata']);

                    // Create searchable hash if this is a searchable encrypted field
                    if (in_array($attribute, $this->getSearchableEncryptedAttributes())) {
                        $this->attributes[$attribute . '_search_hash'] = $encryptionService->createSearchableHash($originalValue);
                    }

                    // Log encryption
                    Log::channel('security')->debug('Model attribute encrypted', [
                        'model' => get_class($this),
                        'model_id' => $this->getKey(),
                        'attribute' => $attribute,
                        'encryption_id' => $encryptionResult['metadata']['encryption_id']
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to encrypt model attribute', [
                        'model' => get_class($this),
                        'model_id' => $this->getKey(),
                        'attribute' => $attribute,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Keep original value if encryption fails
                    // In production, you might want to throw an exception instead
                }
            }
        }
    }

    /**
     * Decrypt specified attributes after retrieving
     */
    protected function decryptAttributes(): void
    {
        $encryptionService = app(DataEncryptionService::class);
        
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                $metadataAttribute = $attribute . '_encryption_metadata';
                $metadata = $this->attributes[$metadataAttribute] ?? [];

                // Skip if not encrypted (no metadata)
                if (empty($metadata)) {
                    continue;
                }

                try {
                    $decryptionResult = $encryptionService->decryptSensitiveData(
                        $this->attributes[$attribute],
                        $metadata
                    );

                    $decryptedValue = $decryptionResult['decrypted_data'];

                    // Try to decode JSON if it was originally an array
                    if (isset($metadata['data_type']) && $metadata['data_type'] === 'array') {
                        $jsonDecoded = json_decode($decryptedValue, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decryptedValue = $jsonDecoded;
                        }
                    }

                    $this->attributes[$attribute] = $decryptedValue;

                    // Log decryption for sensitive operations
                    if ($this->isSensitiveModel()) {
                        Log::channel('security')->debug('Model attribute decrypted', [
                            'model' => get_class($this),
                            'model_id' => $this->getKey(),
                            'attribute' => $attribute,
                            'encryption_id' => $metadata['encryption_id'] ?? 'unknown',
                            'integrity_verified' => $decryptionResult['integrity_verified']
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to decrypt model attribute', [
                        'model' => get_class($this),
                        'model_id' => $this->getKey(),
                        'attribute' => $attribute,
                        'encryption_id' => $metadata['encryption_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    
                    // Keep encrypted value if decryption fails
                    // This prevents data loss but the attribute will be unusable
                }
            }
        }
    }

    /**
     * Get the encrypted attributes for this model
     */
    public function getEncryptedAttributes(): array
    {
        return property_exists($this, 'encrypted') ? $this->encrypted : [];
    }

    /**
     * Get the searchable encrypted attributes for this model
     */
    public function getSearchableEncryptedAttributes(): array
    {
        return property_exists($this, 'searchableEncrypted') ? $this->searchableEncrypted : [];
    }

    /**
     * Search by encrypted attribute using hash
     */
    public function scopeWhereEncrypted($query, string $attribute, string $value)
    {
        if (!in_array($attribute, $this->getSearchableEncryptedAttributes())) {
            throw new \Exception("Attribute {$attribute} is not configured for searchable encryption");
        }

        $encryptionService = app(DataEncryptionService::class);
        $searchHash = $encryptionService->createSearchableHash($value);
        
        return $query->where($attribute . '_search_hash', $searchHash);
    }

    /**
     * Verify encryption integrity for all encrypted attributes
     */
    public function verifyEncryptionIntegrity(): array
    {
        $encryptionService = app(DataEncryptionService::class);
        $results = [];

        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                $metadataAttribute = $attribute . '_encryption_metadata';
                $metadata = $this->attributes[$metadataAttribute] ?? [];

                if (!empty($metadata)) {
                    try {
                        $verificationResult = $encryptionService->verifyEncryptionIntegrity(
                            $this->attributes[$attribute],
                            $metadata
                        );
                        
                        $results[$attribute] = $verificationResult;
                    } catch (\Exception $e) {
                        $results[$attribute] = [
                            'is_valid' => false,
                            'errors' => ['Verification failed: ' . $e->getMessage()],
                            'checks_performed' => []
                        ];
                    }
                } else {
                    $results[$attribute] = [
                        'is_valid' => false,
                        'errors' => ['No encryption metadata found'],
                        'checks_performed' => []
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Rotate encryption keys for all encrypted attributes
     */
    public function rotateEncryptionKeys(): array
    {
        $encryptionService = app(DataEncryptionService::class);
        $results = [];

        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                $metadataAttribute = $attribute . '_encryption_metadata';
                $oldMetadata = $this->attributes[$metadataAttribute] ?? [];

                if (!empty($oldMetadata)) {
                    try {
                        $rotationResult = $encryptionService->rotateEncryptionKeys(
                            $this->attributes[$attribute],
                            $oldMetadata
                        );
                        
                        $this->attributes[$attribute] = $rotationResult['encrypted_data'];
                        $this->attributes[$metadataAttribute] = $rotationResult['metadata'];
                        
                        $results[$attribute] = [
                            'success' => true,
                            'old_encryption_id' => $oldMetadata['encryption_id'] ?? 'unknown',
                            'new_encryption_id' => $rotationResult['metadata']['encryption_id']
                        ];
                    } catch (\Exception $e) {
                        $results[$attribute] = [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
        }

        // Save the model with new encryption
        if (!empty($results)) {
            $this->save();
            
            // Log key rotation
            AuditService::logSecurityEvent(
                'encryption_key_rotation',
                'Encryption keys rotated for model',
                auth()->user(),
                $this,
                [
                    'rotated_attributes' => array_keys($results),
                    'success_count' => count(array_filter($results, fn($r) => $r['success'])),
                    'failure_count' => count(array_filter($results, fn($r) => !$r['success']))
                ]
            );
        }

        return $results;
    }

    /**
     * Check if this model contains sensitive data
     */
    protected function isSensitiveModel(): bool
    {
        $sensitiveModels = [
            'App\Models\BailMobiliteSignature',
            'App\Models\ContractTemplate',
            'App\Models\User',
            'App\Models\BailMobilite'
        ];

        return in_array(get_class($this), $sensitiveModels);
    }

    /**
     * Get encryption metadata for an attribute
     */
    public function getEncryptionMetadata(string $attribute): ?array
    {
        $metadataAttribute = $attribute . '_encryption_metadata';
        return $this->attributes[$metadataAttribute] ?? null;
    }

    /**
     * Check if an attribute is encrypted
     */
    public function isAttributeEncrypted(string $attribute): bool
    {
        return in_array($attribute, $this->getEncryptedAttributes()) &&
               isset($this->attributes[$attribute . '_encryption_metadata']);
    }

    /**
     * Get all encryption metadata for the model
     */
    public function getAllEncryptionMetadata(): array
    {
        $metadata = [];
        
        foreach ($this->getEncryptedAttributes() as $attribute) {
            $attributeMetadata = $this->getEncryptionMetadata($attribute);
            if ($attributeMetadata) {
                $metadata[$attribute] = $attributeMetadata;
            }
        }

        return $metadata;
    }

    /**
     * Create encrypted backup of the model
     */
    public function createEncryptedBackup(): string
    {
        $encryptionService = app(DataEncryptionService::class);
        $backupId = 'model_backup_' . $this->getKey() . '_' . now()->format('Y-m-d_H-i-s');
        
        $sensitiveData = [];
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute])) {
                $sensitiveData[$attribute] = [
                    'value' => $this->attributes[$attribute],
                    'metadata' => $this->getEncryptionMetadata($attribute)
                ];
            }
        }

        $encryptedBackup = $encryptionService->createEncryptedBackup($sensitiveData, $backupId);

        // Log backup creation
        AuditService::logSecurityEvent(
            'encrypted_backup_created',
            'Encrypted backup created for model',
            auth()->user(),
            $this,
            [
                'backup_id' => $backupId,
                'attributes_backed_up' => array_keys($sensitiveData)
            ]
        );

        return $encryptedBackup;
    }
}