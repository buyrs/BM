<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ApiRateLimiter;
use App\Models\User;
use App\Services\FileSecurityService;
use App\Services\SessionSecurityService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityFeaturesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_security_services_can_be_instantiated()
    {
        $twoFactorService = app(TwoFactorService::class);
        $fileSecurityService = app(FileSecurityService::class);
        $sessionSecurityService = app(SessionSecurityService::class);

        $this->assertInstanceOf(TwoFactorService::class, $twoFactorService);
        $this->assertInstanceOf(FileSecurityService::class, $fileSecurityService);
        $this->assertInstanceOf(SessionSecurityService::class, $sessionSecurityService);
    }

    /** @test */
    public function rate_limiting_middleware_exists()
    {
        $middleware = new ApiRateLimiter();
        
        $this->assertInstanceOf(ApiRateLimiter::class, $middleware);
        $this->assertTrue(method_exists($middleware, 'handle'));
    }

    /** @test */
    public function two_factor_service_has_required_methods()
    {
        $service = app(TwoFactorService::class);
        
        $methods = [
            'generateSecretKey',
            'getQrCodeUrl',
            'verifyCode',
            'enableTwoFactor',
            'disableTwoFactor',
            'getDecryptedSecret',
            'verifyRecoveryCode'
        ];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($service, $method));
        }
    }

    /** @test */
    public function file_security_service_validates_files()
    {
        Storage::fake('local');
        $service = app(FileSecurityService::class);
        
        // Test valid image file
        $validFile = UploadedFile::fake()->image('test.jpg');
        $result = $service->validateFile($validFile, 'images');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        
        // Test dangerous file extension
        $dangerousFile = UploadedFile::fake()->create('malicious.php');
        $result = $service->validateFile($dangerousFile);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function file_security_service_generates_secure_filenames()
    {
        $service = app(FileSecurityService::class);
        $file = UploadedFile::fake()->image('test image.jpg');
        
        $secureFilename = $service->generateSecureFilename($file);
        
        $this->assertStringNotContainsString(' ', $secureFilename);
        $this->assertStringNotContainsString('test image', $secureFilename);
        $this->assertStringEndsWith('.jpg', $secureFilename);
    }

    /** @test */
    public function session_security_service_has_required_methods()
    {
        $service = app(SessionSecurityService::class);
        
        $methods = [
            'trackLogin',
            'validateSession',
            'invalidateSession',
            'getActiveSessions',
            'terminateSession',
            'checkSuspiciousActivity'
        ];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($service, $method));
        }
    }

    /** @test */
    public function user_model_has_two_factor_methods()
    {
        $user = User::factory()->create();
        
        $this->assertTrue(method_exists($user, 'hasTwoFactorEnabled'));
        $this->assertTrue(method_exists($user, 'getRecoveryCodes'));
        $this->assertTrue(method_exists($user, 'generateRecoveryCodes'));
        $this->assertTrue(method_exists($user, 'replaceRecoveryCode'));
    }

    /** @test */
    public function user_model_two_factor_status_detection()
    {
        $userWithoutTwoFactor = User::factory()->create();
        $this->assertFalse($userWithoutTwoFactor->hasTwoFactorEnabled());

        $userWithTwoFactor = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'encrypted_secret'
        ]);
        $this->assertTrue($userWithTwoFactor->hasTwoFactorEnabled());
    }

    /** @test */
    public function file_security_service_checks_file_permissions()
    {
        $service = app(FileSecurityService::class);
        
        // Admin can access all files
        $this->assertTrue($service->hasFileAccess('any/path/file.jpg', 1, 'admin'));
        
        // Ops can access property files
        $this->assertTrue($service->hasFileAccess('secure/properties/1/file.jpg', 2, 'ops'));
        
        // Checker can access mission files
        $this->assertTrue($service->hasFileAccess('secure/missions/1/file.jpg', 3, 'checker'));
        
        // User can access their own files
        $this->assertTrue($service->hasFileAccess('secure/users/1/file.jpg', 1, 'checker'));
        $this->assertFalse($service->hasFileAccess('secure/users/2/file.jpg', 1, 'checker'));
    }

    /** @test */
    public function security_middleware_classes_exist()
    {
        $middlewareClasses = [
            \App\Http\Middleware\ApiRateLimiter::class,
            \App\Http\Middleware\AuditMiddleware::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\SecureSession::class,
        ];

        foreach ($middlewareClasses as $class) {
            $this->assertTrue(class_exists($class), "Middleware class {$class} should exist");
        }
    }

    /** @test */
    public function security_services_integration_works()
    {
        $user = User::factory()->create();
        
        // Test Two-Factor Service
        $twoFactorService = app(TwoFactorService::class);
        $secret = $twoFactorService->generateSecretKey();
        $this->assertEquals(32, strlen($secret));
        
        // Test File Security Service
        $fileSecurityService = app(FileSecurityService::class);
        $categories = $fileSecurityService->getAllowedCategories();
        $this->assertContains('images', $categories);
        $this->assertContains('documents', $categories);
        
        // Test Session Security Service
        $sessionSecurityService = app(SessionSecurityService::class);
        $this->assertFalse($sessionSecurityService->validateSession()); // No authenticated user
        
        $this->assertTrue(true); // All services integrated successfully
    }
}