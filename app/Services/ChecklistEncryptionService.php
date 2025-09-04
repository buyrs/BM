<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistPhoto;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ChecklistEncryptionService
{
    /**
     * Encrypt sensitive checklist data
     */
    public function encryptChecklistData(Checklist $checklist): Checklist
    {
        try {
            // Encrypt general info
            if ($checklist->general_info) {
                $checklist->general_info = $this->encryptArrayData($checklist->general_info);
            }

            // Encrypt rooms data
            if ($checklist->rooms) {
                $checklist->rooms = $this->encryptArrayData($checklist->rooms);
            }

            // Encrypt utilities data
            if ($checklist->utilities) {
                $checklist->utilities = $this->encryptArrayData($checklist->utilities);
            }

            // Encrypt signatures
            if ($checklist->tenant_signature) {
                $checklist->tenant_signature = $this->encryptSignatureData($checklist->tenant_signature);
            }

            if ($checklist->agent_signature) {
                $checklist->agent_signature = $this->encryptSignatureData($checklist->agent_signature);
            }

            // Encrypt validation comments
            if ($checklist->ops_validation_comments) {
                $checklist->ops_validation_comments = Crypt::encryptString($checklist->ops_validation_comments);
            }

            $checklist->save();

            Log::info('Checklist data encrypted', [
                'checklist_id' => $checklist->id
            ]);

            return $checklist;

        } catch (Exception $e) {
            Log::error('Failed to encrypt checklist data', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt sensitive checklist data
     */
    public function decryptChecklistData(Checklist $checklist): Checklist
    {
        try {
            // Decrypt general info
            if ($checklist->general_info) {
                $checklist->general_info = $this->decryptArrayData($checklist->general_info);
            }

            // Decrypt rooms data
            if ($checklist->rooms) {
                $checklist->rooms = $this->decryptArrayData($checklist->rooms);
            }

            // Decrypt utilities data
            if ($checklist->utilities) {
                $checklist->utilities = $this->decryptArrayData($checklist->utilities);
            }

            // Decrypt signatures
            if ($checklist->tenant_signature) {
                $checklist->tenant_signature = $this->decryptSignatureData($checklist->tenant_signature);
            }

            if ($checklist->agent_signature) {
                $checklist->agent_signature = $this->decryptSignatureData($checklist->agent_signature);
            }

            // Decrypt validation comments
            if ($checklist->ops_validation_comments) {
                $checklist->ops_validation_comments = Crypt::decryptString($checklist->ops_validation_comments);
            }

            Log::info('Checklist data decrypted', [
                'checklist_id' => $checklist->id
            ]);

            return $checklist;

        } catch (Exception $e) {
            Log::error('Failed to decrypt checklist data', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Encrypt checklist item data
     */
    public function encryptChecklistItemData(ChecklistItem $item): ChecklistItem
    {
        try {
            // Encrypt comment/notes
            if ($item->comment) {
                $item->comment = Crypt::encryptString($item->comment);
            }

            if ($item->notes) {
                $item->notes = Crypt::encryptString($item->notes);
            }

            $item->save();

            return $item;

        } catch (Exception $e) {
            Log::error('Failed to encrypt checklist item data', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt checklist item data
     */
    public function decryptChecklistItemData(ChecklistItem $item): ChecklistItem
    {
        try {
            // Decrypt comment/notes
            if ($item->comment) {
                $item->comment = Crypt::decryptString($item->comment);
            }

            if ($item->notes) {
                $item->notes = Crypt::decryptString($item->notes);
            }

            return $item;

        } catch (Exception $e) {
            Log::error('Failed to decrypt checklist item data', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Encrypt photo metadata
     */
    public function encryptPhotoMetadata(ChecklistPhoto $photo): ChecklistPhoto
    {
        try {
            // Encrypt original name
            if ($photo->original_name) {
                $photo->original_name = Crypt::encryptString($photo->original_name);
            }

            // Encrypt metadata if it exists
            if ($photo->metadata) {
                $photo->metadata = $this->encryptArrayData($photo->metadata);
            }

            $photo->save();

            return $photo;

        } catch (Exception $e) {
            Log::error('Failed to encrypt photo metadata', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt photo metadata
     */
    public function decryptPhotoMetadata(ChecklistPhoto $photo): ChecklistPhoto
    {
        try {
            // Decrypt original name
            if ($photo->original_name) {
                $photo->original_name = Crypt::decryptString($photo->original_name);
            }

            // Decrypt metadata if it exists
            if ($photo->metadata) {
                $photo->metadata = $this->decryptArrayData($photo->metadata);
            }

            return $photo;

        } catch (Exception $e) {
            Log::error('Failed to decrypt photo metadata', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Encrypt array data recursively
     */
    protected function encryptArrayData(array $data): array
    {
        $encrypted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $encrypted[$key] = $this->encryptArrayData($value);
            } elseif (is_string($value) && !empty($value)) {
                $encrypted[$key] = Crypt::encryptString($value);
            } else {
                $encrypted[$key] = $value;
            }
        }

        return $encrypted;
    }

    /**
     * Decrypt array data recursively
     */
    protected function decryptArrayData(array $data): array
    {
        $decrypted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $decrypted[$key] = $this->decryptArrayData($value);
            } elseif (is_string($value) && !empty($value)) {
                try {
                    $decrypted[$key] = Crypt::decryptString($value);
                } catch (Exception $e) {
                    // If decryption fails, assume it's not encrypted
                    $decrypted[$key] = $value;
                }
            } else {
                $decrypted[$key] = $value;
            }
        }

        return $decrypted;
    }

    /**
     * Encrypt signature data with additional security
     */
    protected function encryptSignatureData(string $signatureData): string
    {
        // Add timestamp and random salt for additional security
        $timestamp = time();
        $salt = bin2hex(random_bytes(16));
        $data = $signatureData . '|' . $timestamp . '|' . $salt;

        return Crypt::encryptString($data);
    }

    /**
     * Decrypt signature data
     */
    protected function decryptSignatureData(string $encryptedData): string
    {
        try {
            $decrypted = Crypt::decryptString($encryptedData);
            
            // Extract signature data (remove timestamp and salt)
            $parts = explode('|', $decrypted);
            if (count($parts) >= 3) {
                return $parts[0]; // Return only the signature data
            }

            return $decrypted;

        } catch (Exception $e) {
            Log::warning('Failed to decrypt signature data, trying direct decryption', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback to direct decryption for older data
            return Crypt::decryptString($encryptedData);
        }
    }

    /**
     * Encrypt file content
     */
    public function encryptFileContent(string $filePath): string
    {
        try {
            $content = Storage::disk('public')->get($filePath);
            $encrypted = Crypt::encryptString($content);
            
            // Save encrypted content
            $encryptedPath = $filePath . '.encrypted';
            Storage::disk('private')->put($encryptedPath, $encrypted);
            
            return $encryptedPath;

        } catch (Exception $e) {
            Log::error('Failed to encrypt file content', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt file content
     */
    public function decryptFileContent(string $encryptedPath): string
    {
        try {
            $encrypted = Storage::disk('private')->get($encryptedPath);
            return Crypt::decryptString($encrypted);

        } catch (Exception $e) {
            Log::error('Failed to decrypt file content', [
                'encrypted_path' => $encryptedPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate data integrity hash
     */
    public function generateIntegrityHash(array $data): string
    {
        $serialized = json_encode($data, JSON_SORT_KEYS);
        return hash('sha256', $serialized);
    }

    /**
     * Verify data integrity
     */
    public function verifyIntegrity(array $data, string $expectedHash): bool
    {
        $actualHash = $this->generateIntegrityHash($data);
        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Create secure backup of checklist data
     */
    public function createSecureBackup(Checklist $checklist): array
    {
        try {
            $backupData = [
                'checklist_id' => $checklist->id,
                'mission_id' => $checklist->mission_id,
                'created_at' => now()->toISOString(),
                'data' => [
                    'general_info' => $checklist->general_info,
                    'rooms' => $checklist->rooms,
                    'utilities' => $checklist->utilities,
                    'tenant_signature' => $checklist->tenant_signature,
                    'agent_signature' => $checklist->agent_signature,
                    'ops_validation_comments' => $checklist->ops_validation_comments,
                    'status' => $checklist->status,
                    'validated_by' => $checklist->validated_by,
                    'validated_at' => $checklist->validated_at
                ]
            ];

            // Encrypt the backup data
            $encryptedBackup = Crypt::encryptString(json_encode($backupData));
            
            // Generate filename
            $filename = 'checklist_backup_' . $checklist->id . '_' . time() . '.enc';
            $filePath = "backups/checklists/{$filename}";
            
            // Save encrypted backup
            Storage::disk('private')->put($filePath, $encryptedBackup);
            
            // Generate integrity hash
            $integrityHash = $this->generateIntegrityHash($backupData);

            Log::info('Secure checklist backup created', [
                'checklist_id' => $checklist->id,
                'backup_file' => $filePath,
                'integrity_hash' => $integrityHash
            ]);

            return [
                'success' => true,
                'backup_file' => $filePath,
                'integrity_hash' => $integrityHash,
                'created_at' => $backupData['created_at']
            ];

        } catch (Exception $e) {
            Log::error('Failed to create secure backup', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Restore checklist from secure backup
     */
    public function restoreFromBackup(string $backupFile, string $integrityHash): array
    {
        try {
            // Read encrypted backup
            $encryptedData = Storage::disk('private')->get($backupFile);
            $backupData = json_decode(Crypt::decryptString($encryptedData), true);

            // Verify integrity
            if (!$this->verifyIntegrity($backupData, $integrityHash)) {
                throw new Exception('Backup integrity verification failed');
            }

            // Find or create checklist
            $checklist = Checklist::findOrFail($backupData['checklist_id']);
            
            // Restore data
            $checklist->update($backupData['data']);

            Log::info('Checklist restored from secure backup', [
                'checklist_id' => $checklist->id,
                'backup_file' => $backupFile
            ]);

            return [
                'success' => true,
                'checklist_id' => $checklist->id,
                'restored_at' => now()->toISOString()
            ];

        } catch (Exception $e) {
            Log::error('Failed to restore from backup', [
                'backup_file' => $backupFile,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
