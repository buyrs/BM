<?php

namespace App\Console\Commands;

use App\Models\BailMobiliteSignature;
use App\Models\User;
use App\Services\SecurityService;
use App\Services\DataEncryptionService;
use App\Services\SecureFileStorageService;
use App\Services\AuditService;
use App\Services\RoleBasedAccessControlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSecurityFeatures extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:test {--feature=all : Which feature to test (all, encryption, audit, rbac, files, signatures)}';

    /**
     * The console command description.
     */
    protected $description = 'Test the security and compliance features implementation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feature = $this->option('feature');

        $this->info('🔒 Testing Security and Compliance Features');
        $this->info('==========================================');

        switch ($feature) {
            case 'encryption':
                $this->testEncryption();
                break;
            case 'audit':
                $this->testAuditLogging();
                break;
            case 'rbac':
                $this->testRoleBasedAccessControl();
                break;
            case 'files':
                $this->testSecureFileStorage();
                break;
            case 'signatures':
                $this->testSignatureVerification();
                break;
            case 'all':
            default:
                $this->testEncryption();
                $this->testAuditLogging();
                $this->testRoleBasedAccessControl();
                $this->testSecureFileStorage();
                $this->testSignatureVerification();
                break;
        }

        $this->info('✅ Security features testing completed!');
        return 0;
    }

    /**
     * Test data encryption features
     */
    private function testEncryption()
    {
        $this->info("\n🔐 Testing Data Encryption...");
        
        $encryptionService = app(DataEncryptionService::class);
        
        try {
            // Test basic encryption/decryption
            $testData = 'This is sensitive signature data';
            $this->line("Original data: {$testData}");
            
            $encryptionResult = $encryptionService->encryptSensitiveData($testData, [
                'test_context' => 'command_test'
            ]);
            
            $this->line("✓ Data encrypted successfully");
            $this->line("  Encryption ID: " . $encryptionResult['metadata']['encryption_id']);
            $this->line("  Algorithm: " . $encryptionResult['metadata']['algorithm']);
            
            $decryptionResult = $encryptionService->decryptSensitiveData(
                $encryptionResult['encrypted_data'],
                $encryptionResult['metadata']
            );
            
            $this->line("✓ Data decrypted successfully");
            $this->line("  Integrity verified: " . ($decryptionResult['integrity_verified'] ? 'Yes' : 'No'));
            $this->line("  Decrypted data matches: " . ($decryptionResult['decrypted_data'] === $testData ? 'Yes' : 'No'));
            
            // Test searchable encryption
            $email = 'test@example.com';
            $searchHash = $encryptionService->createSearchableHash($email);
            $this->line("✓ Searchable hash created for email: " . substr($searchHash, 0, 16) . '...');
            
            // Test encryption statistics
            $stats = $encryptionService->getEncryptionStatistics();
            $this->line("✓ Encryption statistics retrieved");
            $this->line("  Total encrypted fields: " . $stats['total_encrypted_fields']);
            $this->line("  Searchable encrypted fields: " . $stats['searchable_encrypted_fields']);
            
        } catch (\Exception $e) {
            $this->error("❌ Encryption test failed: " . $e->getMessage());
        }
    }

    /**
     * Test audit logging features
     */
    private function testAuditLogging()
    {
        $this->info("\n📝 Testing Audit Logging...");
        
        try {
            // Test basic audit logging
            $testUser = User::first();
            if (!$testUser) {
                $this->warn("No users found, skipping user-specific audit tests");
                return;
            }
            
            // Test different types of audit logs
            AuditService::logAction(
                'test_action',
                'Testing audit logging from command',
                null,
                $testUser,
                [],
                ['test_data' => 'command_test'],
                ['command_test' => true],
                'info',
                false
            );
            $this->line("✓ Basic audit log created");
            
            // Test security event logging
            AuditService::logSecurityEvent(
                'test_security_event',
                'Testing security event logging',
                $testUser,
                null,
                ['test_context' => 'security_test'],
                'warning'
            );
            $this->line("✓ Security event logged");
            
            // Test authentication logging
            AuditService::logAuthentication('test_login', $testUser, [
                'test_mode' => true
            ]);
            $this->line("✓ Authentication event logged");
            
            // Test file operation logging
            AuditService::logFileOperation(
                'test_upload',
                'test_file.pdf',
                $testUser,
                ['test_context' => 'command_test']
            );
            $this->line("✓ File operation logged");
            
            // Get audit statistics
            $stats = AuditService::getAuditStatistics(1); // Last 1 day
            $this->line("✓ Audit statistics retrieved");
            $this->line("  Total events (last 24h): " . $stats['total_events']);
            $this->line("  Sensitive events: " . $stats['sensitive_events']);
            $this->line("  Failed events: " . $stats['failed_events']);
            
        } catch (\Exception $e) {
            $this->error("❌ Audit logging test failed: " . $e->getMessage());
        }
    }

    /**
     * Test role-based access control
     */
    private function testRoleBasedAccessControl()
    {
        $this->info("\n🛡️ Testing Role-Based Access Control...");
        
        $rbacService = app(RoleBasedAccessControlService::class);
        
        try {
            $testUser = User::first();
            if (!$testUser) {
                $this->warn("No users found, skipping RBAC tests");
                return;
            }
            
            // Test basic permission validation
            $validationResult = $rbacService->validateAccess(
                $testUser,
                'view_dashboard',
                null,
                ['test_context' => 'command_test']
            );
            
            $this->line("✓ Access validation performed");
            $this->line("  Permission: view_dashboard");
            $this->line("  Granted: " . ($validationResult['granted'] ? 'Yes' : 'No'));
            $this->line("  Checks performed: " . count($validationResult['checks_performed']));
            $this->line("  Validation time: " . $validationResult['validation_time_ms'] . 'ms');
            
            if (!empty($validationResult['failure_reasons'])) {
                $this->line("  Failure reasons: " . implode(', ', $validationResult['failure_reasons']));
            }
            
            // Test resource-specific access
            $bailMobilite = \App\Models\BailMobilite::first();
            if ($bailMobilite) {
                $resourceValidation = $rbacService->validateAccess(
                    $testUser,
                    'view_bail_mobilite',
                    $bailMobilite,
                    ['test_context' => 'resource_test']
                );
                
                $this->line("✓ Resource-specific validation performed");
                $this->line("  Resource: BailMobilite #{$bailMobilite->id}");
                $this->line("  Granted: " . ($resourceValidation['granted'] ? 'Yes' : 'No'));
            }
            
            // Get user access report
            $accessReport = $rbacService->getUserAccessReport($testUser);
            $this->line("✓ User access report generated");
            $this->line("  User roles: " . implode(', ', $accessReport['roles']));
            $this->line("  Total permissions: " . count($accessReport['all_permissions']));
            $this->line("  Recent access attempts: " . count($accessReport['recent_access_attempts']));
            
        } catch (\Exception $e) {
            $this->error("❌ RBAC test failed: " . $e->getMessage());
        }
    }

    /**
     * Test secure file storage
     */
    private function testSecureFileStorage()
    {
        $this->info("\n📁 Testing Secure File Storage...");
        
        $fileStorageService = app(SecureFileStorageService::class);
        
        try {
            // Create a test file
            $testContent = "This is a test contract content with sensitive information.";
            $testFilePath = storage_path('app/temp/test_contract.txt');
            
            // Ensure temp directory exists
            if (!is_dir(dirname($testFilePath))) {
                mkdir(dirname($testFilePath), 0755, true);
            }
            
            file_put_contents($testFilePath, $testContent);
            
            // Create UploadedFile instance for testing
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $testFilePath,
                'test_contract.txt',
                'text/plain',
                null,
                true
            );
            
            // Test secure file storage
            $storageResult = $fileStorageService->storeSecureFile(
                $uploadedFile,
                'test_contracts',
                null,
                true, // encrypt
                ['test_context' => 'command_test']
            );
            
            $this->line("✓ File stored securely");
            $this->line("  Path: " . $storageResult['path']);
            $this->line("  Encrypted: " . ($storageResult['metadata']['encrypted'] ? 'Yes' : 'No'));
            $this->line("  Security level: " . $storageResult['metadata']['security_level']);
            
            // Test file retrieval with admin user
            $testUser = User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->first() ?? User::first();
            
            if ($testUser) {
                try {
                    $retrievalResult = $fileStorageService->retrieveSecureFile(
                        $storageResult['path'],
                        $testUser
                    );
                    
                    $this->line("✓ File retrieved successfully");
                    $this->line("  Integrity verified: " . ($retrievalResult['integrity_verified'] ? 'Yes' : 'No'));
                    $this->line("  Content matches: " . ($retrievalResult['content'] === $testContent ? 'Yes' : 'No'));
                } catch (\Exception $e) {
                    $this->line("⚠ File retrieval failed (expected for non-admin users): " . $e->getMessage());
                }
            }
            
            // Test file integrity verification
            $integrityResult = $fileStorageService->verifyFileIntegrity($storageResult['path']);
            $this->line("✓ File integrity verification performed");
            $this->line("  Overall status: " . $integrityResult['overall_status']);
            $this->line("  Storage integrity: " . ($integrityResult['storage_integrity'] ? 'Valid' : 'Invalid'));
            $this->line("  Content integrity: " . ($integrityResult['content_integrity'] ? 'Valid' : 'Invalid'));
            
            // Clean up test file
            if (file_exists($testFilePath)) {
                unlink($testFilePath);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Secure file storage test failed: " . $e->getMessage());
        }
    }

    /**
     * Test signature verification
     */
    private function testSignatureVerification()
    {
        $this->info("\n✍️ Testing Signature Verification...");
        
        $securityService = app(SecurityService::class);
        
        try {
            $signature = BailMobiliteSignature::first();
            if (!$signature) {
                $this->warn("No signatures found, skipping signature verification tests");
                return;
            }
            
            // Test signature integrity verification
            $verificationResult = $securityService->verifySignatureIntegrity($signature);
            
            $this->line("✓ Signature integrity verification performed");
            $this->line("  Signature ID: " . $signature->id);
            $this->line("  Is valid: " . ($verificationResult['is_valid'] ? 'Yes' : 'No'));
            $this->line("  Security score: " . $verificationResult['security_score'] . '/100');
            $this->line("  Security level: " . $verificationResult['security_level']);
            $this->line("  Checks performed: " . count($verificationResult['checks']));
            
            if (!empty($verificationResult['errors'])) {
                $this->line("  Errors: " . implode(', ', $verificationResult['errors']));
            }
            
            if (!empty($verificationResult['warnings'])) {
                $this->line("  Warnings: " . implode(', ', $verificationResult['warnings']));
            }
            
            // Test tampering pattern detection
            $tamperingPatterns = $securityService->detectTamperingPatterns($signature);
            $this->line("✓ Tampering pattern detection performed");
            $this->line("  Patterns detected: " . count($tamperingPatterns));
            
            if (!empty($tamperingPatterns)) {
                foreach ($tamperingPatterns as $pattern) {
                    $this->line("  - " . $pattern['type'] . " (" . $pattern['severity'] . "): " . $pattern['description']);
                }
            }
            
            // Generate comprehensive security report
            $securityReport = $securityService->generateSecurityReport($signature);
            $this->line("✓ Security report generated");
            $this->line("  Report generated at: " . $securityReport['report_generated_at']);
            $this->line("  Compliance status: " . ($securityReport['compliance_status']['is_compliant'] ? 'Compliant' : 'Non-compliant'));
            $this->line("  Legal validity: " . ($securityReport['compliance_status']['legal_validity']['is_legally_valid'] ? 'Valid' : 'Invalid'));
            $this->line("  Recommendations: " . count($securityReport['recommendations']));
            
        } catch (\Exception $e) {
            $this->error("❌ Signature verification test failed: " . $e->getMessage());
        }
    }
}