<?php

namespace Tests\Unit\Security;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorService $twoFactorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twoFactorService = new TwoFactorService();
    }

    /** @test */
    public function generates_valid_secret_key()
    {
        $secret = $this->twoFactorService->generateSecretKey();

        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    /** @test */
    public function generates_different_secret_keys()
    {
        $secret1 = $this->twoFactorService->generateSecretKey();
        $secret2 = $this->twoFactorService->generateSecretKey();

        $this->assertNotEquals($secret1, $secret2);
    }

    /** @test */
    public function generates_qr_code_url_with_correct_format()
    {
        $user = User::factory()->make(['email' => 'test@example.com']);
        $secret = 'TESTSECRET123456789012345678901234';

        $qrUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $qrUrl);
        $this->assertStringContainsString('test@example.com', $qrUrl);
        $this->assertStringContainsString($secret, $qrUrl);
        $this->assertStringContainsString('otpauth://totp/', $qrUrl);
    }

    /** @test */
    public function verifies_valid_totp_codes()
    {
        $secret = 'TESTSECRET123456789012345678901234';
        $validCode = $this->generateValidTotpCode($secret);

        $result = $this->twoFactorService->verifyCode($secret, $validCode);

        $this->assertTrue($result);
    }

    /** @test */
    public function rejects_invalid_totp_codes()
    {
        $secret = 'TESTSECRET123456789012345678901234';

        $result = $this->twoFactorService->verifyCode($secret, '000000');

        $this->assertFalse($result);
    }

    /** @test */
    public function verifies_codes_within_time_window()
    {
        $secret = 'TESTSECRET123456789012345678901234';
        
        // Generate code for previous time slice
        $previousTimeSlice = floor(time() / 30) - 1;
        $previousCode = $this->generateTotpCodeForTimeSlice($secret, $previousTimeSlice);

        $result = $this->twoFactorService->verifyCode($secret, $previousCode, 1);

        $this->assertTrue($result);
    }

    /** @test */
    public function rejects_codes_outside_time_window()
    {
        $secret = 'TESTSECRET123456789012345678901234';
        
        // Generate code for time slice outside window
        $oldTimeSlice = floor(time() / 30) - 5;
        $oldCode = $this->generateTotpCodeForTimeSlice($secret, $oldTimeSlice);

        $result = $this->twoFactorService->verifyCode($secret, $oldCode, 1);

        $this->assertFalse($result);
    }

    /** @test */
    public function enables_two_factor_for_user()
    {
        $user = User::factory()->create();
        $secret = 'TESTSECRET123456789012345678901234';

        $this->twoFactorService->enableTwoFactor($user, $secret);

        $user->refresh();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotEmpty($user->two_factor_recovery_codes);
        
        // Verify secret is encrypted
        $decryptedSecret = Crypt::decryptString($user->two_factor_secret);
        $this->assertEquals($secret, $decryptedSecret);
    }

    /** @test */
    public function disables_two_factor_for_user()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => Crypt::encryptString('TESTSECRET123456789012345678901234'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['code1', 'code2']
        ]);

        $this->twoFactorService->disableTwoFactor($user);

        $user->refresh();
        $this->assertFalse($user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);
    }

    /** @test */
    public function gets_decrypted_secret_for_user()
    {
        $secret = 'TESTSECRET123456789012345678901234';
        $user = User::factory()->create([
            'two_factor_secret' => Crypt::encryptString($secret)
        ]);

        $decryptedSecret = $this->twoFactorService->getDecryptedSecret($user);

        $this->assertEquals($secret, $decryptedSecret);
    }

    /** @test */
    public function returns_null_for_user_without_secret()
    {
        $user = User::factory()->create(['two_factor_secret' => null]);

        $decryptedSecret = $this->twoFactorService->getDecryptedSecret($user);

        $this->assertNull($decryptedSecret);
    }

    /** @test */
    public function verifies_valid_recovery_codes()
    {
        $user = User::factory()->create([
            'two_factor_recovery_codes' => ['validcode1', 'validcode2', 'validcode3']
        ]);

        $result = $this->twoFactorService->verifyRecoveryCode($user, 'validcode1');

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertNotContains('validcode1', $user->two_factor_recovery_codes);
        $this->assertCount(2, $user->two_factor_recovery_codes);
    }

    /** @test */
    public function rejects_invalid_recovery_codes()
    {
        $user = User::factory()->create([
            'two_factor_recovery_codes' => ['validcode1', 'validcode2', 'validcode3']
        ]);

        $result = $this->twoFactorService->verifyRecoveryCode($user, 'invalidcode');

        $this->assertFalse($result);
        
        $user->refresh();
        $this->assertCount(3, $user->two_factor_recovery_codes);
    }

    /** @test */
    public function handles_empty_recovery_codes()
    {
        $user = User::factory()->create(['two_factor_recovery_codes' => null]);

        $result = $this->twoFactorService->verifyRecoveryCode($user, 'anycode');

        $this->assertFalse($result);
    }

    /** @test */
    public function base32_decode_handles_valid_input()
    {
        $secret = 'TESTSECRET123456789012345678901234';
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->twoFactorService);
        $method = $reflection->getMethod('base32Decode');
        $method->setAccessible(true);

        $result = $method->invoke($this->twoFactorService, $secret);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function generates_consistent_codes_for_same_time_slice()
    {
        $secret = 'TESTSECRET123456789012345678901234';
        $timeSlice = floor(time() / 30);

        $code1 = $this->generateTotpCodeForTimeSlice($secret, $timeSlice);
        $code2 = $this->generateTotpCodeForTimeSlice($secret, $timeSlice);

        $this->assertEquals($code1, $code2);
    }

    /** @test */
    public function generates_different_codes_for_different_secrets()
    {
        $secret1 = 'TESTSECRET123456789012345678901234';
        $secret2 = 'ANOTHERSECRET123456789012345678901';
        $timeSlice = floor(time() / 30);

        $code1 = $this->generateTotpCodeForTimeSlice($secret1, $timeSlice);
        $code2 = $this->generateTotpCodeForTimeSlice($secret2, $timeSlice);

        $this->assertNotEquals($code1, $code2);
    }

    /**
     * Generate a valid TOTP code for testing purposes.
     */
    private function generateValidTotpCode(string $secret): string
    {
        $timeSlice = floor(time() / 30);
        return $this->generateTotpCodeForTimeSlice($secret, $timeSlice);
    }

    /**
     * Generate TOTP code for specific time slice.
     */
    private function generateTotpCodeForTimeSlice(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string for testing.
     */
    private function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        
        return $binaryString;
    }
}