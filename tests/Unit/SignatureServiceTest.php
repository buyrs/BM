<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SignatureService;
use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SignatureServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SignatureService $signatureService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signatureService = new SignatureService();
    }

    /** @test */
    public function it_can_encrypt_and_decrypt_signature_data()
    {
        $originalData = 'test_signature_data_12345';
        
        // Use reflection to test private methods
        $reflection = new \ReflectionClass($this->signatureService);
        
        $encryptMethod = $reflection->getMethod('encryptSignatureData');
        $encryptMethod->setAccessible(true);
        
        $decryptMethod = $reflection->getMethod('decryptSignatureData');
        $decryptMethod->setAccessible(true);
        
        $encrypted = $encryptMethod->invoke($this->signatureService, $originalData);
        $decrypted = $decryptMethod->invoke($this->signatureService, $encrypted);
        
        $this->assertEquals($originalData, $decrypted);
        $this->assertNotEquals($originalData, $encrypted);
    }

    /** @test */
    public function it_can_generate_device_fingerprint()
    {
        $request = request();
        $request->headers->set('User-Agent', 'Test Browser');
        $request->headers->set('Accept-Language', 'en-US');
        
        $reflection = new \ReflectionClass($this->signatureService);
        $method = $reflection->getMethod('generateDeviceFingerprint');
        $method->setAccessible(true);
        
        $fingerprint = $method->invoke($this->signatureService, $request);
        
        $this->assertIsString($fingerprint);
        $this->assertEquals(64, strlen($fingerprint)); // SHA256 hash length
    }

    /** @test */
    public function it_can_capture_signature_metadata()
    {
        $reflection = new \ReflectionClass($this->signatureService);
        $method = $reflection->getMethod('captureSignatureMetadata');
        $method->setAccessible(true);
        
        $metadata = $method->invoke($this->signatureService, ['test' => 'value']);
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('ip_address', $metadata);
        $this->assertArrayHasKey('user_agent', $metadata);
        $this->assertArrayHasKey('timestamp', $metadata);
        $this->assertArrayHasKey('device_fingerprint', $metadata);
        $this->assertEquals('value', $metadata['test']);
    }
}