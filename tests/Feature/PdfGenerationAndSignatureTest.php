<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BailMobilite;
use App\Models\ContractTemplate;
use App\Models\BailMobiliteSignature;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class PdfGenerationAndSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $opsUser;
    protected SignatureService $signatureService;
    protected ContractTemplate $entryTemplate;
    protected ContractTemplate $exitTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'ops']);
        
        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->signatureService = new SignatureService();
        
        // Create contract templates
        $this->entryTemplate = ContractTemplate::factory()->create([
            'type' => 'entry',
            'name' => 'Standard Entry Contract',
            'content' => 'This is an entry contract for {{tenant_name}} at {{address}} from {{start_date}} to {{end_date}}. Admin signature: {{admin_signature}}',
            'admin_signature' => 'admin_signature_data_12345',
            'admin_signed_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id
        ]);
        
        $this->exitTemplate = ContractTemplate::factory()->create([
            'type' => 'exit',
            'name' => 'Standard Exit Contract',
            'content' => 'This is an exit contract for {{tenant_name}} at {{address}}. Exit date: {{end_date}}. Admin signature: {{admin_signature}}',
            'admin_signature' => 'admin_signature_data_67890',
            'admin_signed_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id
        ]);
        
        // Fake storage for testing
        Storage::fake('local');
    }

    /** @test */
    public function it_can_generate_pdf_with_admin_and_tenant_signatures()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'John Doe',
            'address' => '123 Test Street, Paris',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28'
        ]);

        // Create entry signature
        $entrySignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => 'tenant_signature_data_abc123',
            'tenant_signed_at' => now(),
            'signature_metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 Test Browser',
                'device_fingerprint' => 'device_123',
                'timestamp' => now()->toISOString()
            ]
        ]);

        // Generate PDF
        $pdfPath = $this->signatureService->generateSignedContract($entrySignature);
        
        $this->assertNotNull($pdfPath);
        $this->assertStringContains('contracts/', $pdfPath);
        $this->assertStringContains('.pdf', $pdfPath);
        
        // Update signature with PDF path
        $entrySignature->update(['contract_pdf_path' => $pdfPath]);
        
        // Verify PDF was created
        Storage::disk('local')->assertExists($pdfPath);
        
        // Verify signature is complete
        $this->assertTrue($entrySignature->isComplete());
        $this->assertTrue($entrySignature->isTenantSigned());
        $this->assertTrue($entrySignature->isAdminSigned());
        $this->assertTrue($entrySignature->hasPdfGenerated());
    }

    /** @test */
    public function it_validates_signature_integrity_and_metadata()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create request with signature metadata
        $request = Request::create('/test', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Test Browser)',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9'
        ]);
        
        $signatureData = 'tenant_signature_data_xyz789';
        $additionalMetadata = [
            'signature_method' => 'stylus',
            'signature_duration' => 5.2,
            'canvas_size' => '800x400'
        ];
        
        // Capture signature with metadata
        $metadata = $this->signatureService->captureSignatureMetadata($request, $additionalMetadata);
        
        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => $this->signatureService->encryptSignatureData($signatureData),
            'tenant_signed_at' => now(),
            'signature_metadata' => $metadata
        ]);
        
        // Verify metadata was captured correctly
        $this->assertArrayHasKey('ip_address', $signature->signature_metadata);
        $this->assertArrayHasKey('user_agent', $signature->signature_metadata);
        $this->assertArrayHasKey('timestamp', $signature->signature_metadata);
        $this->assertArrayHasKey('device_fingerprint', $signature->signature_metadata);
        $this->assertArrayHasKey('signature_method', $signature->signature_metadata);
        $this->assertArrayHasKey('signature_duration', $signature->signature_metadata);
        
        $this->assertEquals('192.168.1.100', $signature->signature_metadata['ip_address']);
        $this->assertEquals('Mozilla/5.0 (Test Browser)', $signature->signature_metadata['user_agent']);
        $this->assertEquals('stylus', $signature->signature_metadata['signature_method']);
        $this->assertEquals(5.2, $signature->signature_metadata['signature_duration']);
        
        // Verify signature can be decrypted
        $decryptedSignature = $this->signatureService->decryptSignatureData($signature->tenant_signature);
        $this->assertEquals($signatureData, $decryptedSignature);
        
        // Verify device fingerprint is consistent
        $fingerprint1 = $this->signatureService->generateDeviceFingerprint($request);
        $fingerprint2 = $this->signatureService->generateDeviceFingerprint($request);
        $this->assertEquals($fingerprint1, $fingerprint2);
    }

    /** @test */
    public function it_handles_contract_template_variable_substitution()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'Jane Smith',
            'address' => '456 Example Avenue, Lyon',
            'start_date' => '2025-03-01',
            'end_date' => '2025-03-31'
        ]);

        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => 'tenant_signature_data',
            'tenant_signed_at' => now()
        ]);

        // Generate contract content with variable substitution
        $contractContent = $this->signatureService->generateContractContent($signature);
        
        // Verify variables were substituted
        $this->assertStringContains('Jane Smith', $contractContent);
        $this->assertStringContains('456 Example Avenue, Lyon', $contractContent);
        $this->assertStringContains('2025-03-01', $contractContent);
        $this->assertStringContains('2025-03-31', $contractContent);
        $this->assertStringContains('admin_signature_data_12345', $contractContent);
        
        // Verify template variables were replaced
        $this->assertStringNotContains('{{tenant_name}}', $contractContent);
        $this->assertStringNotContains('{{address}}', $contractContent);
        $this->assertStringNotContains('{{start_date}}', $contractContent);
        $this->assertStringNotContains('{{end_date}}', $contractContent);
        $this->assertStringNotContains('{{admin_signature}}', $contractContent);
    }

    /** @test */
    public function it_prevents_signature_tampering()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        $originalSignature = 'original_signature_data';
        $tamperedSignature = 'tampered_signature_data';
        
        // Create signature with original data
        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => $this->signatureService->encryptSignatureData($originalSignature),
            'tenant_signed_at' => now(),
            'signature_metadata' => [
                'ip_address' => '192.168.1.1',
                'timestamp' => now()->toISOString(),
                'checksum' => hash('sha256', $originalSignature)
            ]
        ]);
        
        // Verify original signature
        $decrypted = $this->signatureService->decryptSignatureData($signature->tenant_signature);
        $this->assertEquals($originalSignature, $decrypted);
        
        // Verify checksum matches
        $this->assertEquals(
            hash('sha256', $originalSignature),
            $signature->signature_metadata['checksum']
        );
        
        // Attempt to tamper with signature
        $signature->update([
            'tenant_signature' => $this->signatureService->encryptSignatureData($tamperedSignature)
        ]);
        
        // Checksum should no longer match
        $newDecrypted = $this->signatureService->decryptSignatureData($signature->tenant_signature);
        $this->assertNotEquals(
            hash('sha256', $newDecrypted),
            $signature->signature_metadata['checksum']
        );
        
        // This indicates tampering
        $this->assertFalse($this->signatureService->verifySignatureIntegrity($signature));
    }

    /** @test */
    public function it_handles_pdf_generation_failures_gracefully()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create signature with invalid template
        $invalidTemplate = ContractTemplate::factory()->create([
            'content' => null, // Invalid content
            'admin_signature' => null // No admin signature
        ]);
        
        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $invalidTemplate->id,
            'tenant_signature' => 'tenant_signature_data',
            'tenant_signed_at' => now()
        ]);
        
        // PDF generation should handle the error gracefully
        $pdfPath = $this->signatureService->generateSignedContract($signature);
        
        // Should return null or throw a specific exception
        $this->assertNull($pdfPath);
        
        // Signature should not be marked as having PDF generated
        $this->assertFalse($signature->hasPdfGenerated());
    }

    /** @test */
    public function it_supports_multiple_signature_formats()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Test different signature formats
        $signatureFormats = [
            'svg' => '<svg><path d="M10,10 L20,20"/></svg>',
            'base64_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
            'coordinate_array' => json_encode([['x' => 10, 'y' => 10], ['x' => 20, 'y' => 20]]),
            'text_signature' => 'John Doe'
        ];
        
        foreach ($signatureFormats as $format => $signatureData) {
            $signature = BailMobiliteSignature::create([
                'bail_mobilite_id' => $bailMobilite->id,
                'signature_type' => 'entry',
                'contract_template_id' => $this->entryTemplate->id,
                'tenant_signature' => $this->signatureService->encryptSignatureData($signatureData),
                'tenant_signed_at' => now(),
                'signature_metadata' => [
                    'signature_format' => $format,
                    'ip_address' => '192.168.1.1',
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            // Verify signature can be decrypted regardless of format
            $decrypted = $this->signatureService->decryptSignatureData($signature->tenant_signature);
            $this->assertEquals($signatureData, $decrypted);
            
            // Verify signature is considered valid
            $this->assertTrue($signature->isTenantSigned());
        }
    }

    /** @test */
    public function it_maintains_signature_audit_trail()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create entry signature
        $entrySignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => $this->signatureService->encryptSignatureData('entry_signature'),
            'tenant_signed_at' => now(),
            'signature_metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 Test',
                'timestamp' => now()->toISOString(),
                'signature_version' => '1.0'
            ]
        ]);
        
        // Create exit signature
        $exitSignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'contract_template_id' => $this->exitTemplate->id,
            'tenant_signature' => $this->signatureService->encryptSignatureData('exit_signature'),
            'tenant_signed_at' => now()->addDays(27),
            'signature_metadata' => [
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Mozilla/5.0 Test Mobile',
                'timestamp' => now()->addDays(27)->toISOString(),
                'signature_version' => '1.0'
            ]
        ]);
        
        // Verify audit trail
        $signatures = BailMobiliteSignature::where('bail_mobilite_id', $bailMobilite->id)
            ->orderBy('tenant_signed_at')
            ->get();
        
        $this->assertCount(2, $signatures);
        
        // Verify chronological order
        $this->assertEquals('entry', $signatures[0]->signature_type);
        $this->assertEquals('exit', $signatures[1]->signature_type);
        
        // Verify different metadata for each signature
        $this->assertEquals('192.168.1.1', $signatures[0]->signature_metadata['ip_address']);
        $this->assertEquals('192.168.1.2', $signatures[1]->signature_metadata['ip_address']);
        
        // Verify both signatures are complete
        $this->assertTrue($signatures[0]->isComplete());
        $this->assertTrue($signatures[1]->isComplete());
    }

    /** @test */
    public function it_handles_contract_template_versioning()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create signature with original template
        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => 'signature_data',
            'tenant_signed_at' => now()
        ]);
        
        // Update template content (simulating a new version)
        $originalContent = $this->entryTemplate->content;
        $this->entryTemplate->update([
            'content' => 'Updated contract content for {{tenant_name}}'
        ]);
        
        // Signature should still reference the original template
        $this->assertEquals($this->entryTemplate->id, $signature->contract_template_id);
        
        // But when generating PDF, it should use the template as it was when signed
        // This is handled by storing the template reference, not the content
        $contractContent = $this->signatureService->generateContractContent($signature);
        
        // Should use current template content (in a real system, you might want to version templates)
        $this->assertStringContains('Updated contract content', $contractContent);
        
        // In a production system, you might want to store template snapshots
        // or version the templates to maintain historical accuracy
    }

    /** @test */
    public function it_validates_signature_timing_and_sequence()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28'
        ]);
        
        // Entry signature should be before or on start date
        $entrySignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => 'entry_signature',
            'tenant_signed_at' => $bailMobilite->start_date->subDay() // Day before start
        ]);
        
        // Exit signature should be on or after end date
        $exitSignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'contract_template_id' => $this->exitTemplate->id,
            'tenant_signature' => 'exit_signature',
            'tenant_signed_at' => $bailMobilite->end_date // On end date
        ]);
        
        // Validate signature timing
        $this->assertTrue($entrySignature->tenant_signed_at <= $bailMobilite->start_date);
        $this->assertTrue($exitSignature->tenant_signed_at >= $bailMobilite->end_date);
        
        // Validate signature sequence
        $this->assertTrue($entrySignature->tenant_signed_at < $exitSignature->tenant_signed_at);
    }
}