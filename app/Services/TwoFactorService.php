<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class TwoFactorService
{
    /**
     * Generate a new secret key for TOTP.
     */
    public function generateSecretKey(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }

    /**
     * Generate QR code URL for Google Authenticator.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $appName = config('app.name', 'Laravel App');
        $email = $user->email;
        
        $qrCodeUrl = 'otpauth://totp/' . urlencode($appName . ':' . $email) . 
                     '?secret=' . $secret . 
                     '&issuer=' . urlencode($appName);
        
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCodeUrl);
    }

    /**
     * Verify a TOTP code.
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $timeSlice = floor(time() / 30);
        
        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->generateCode($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate TOTP code for a given time slice.
     */
    private function generateCode(string $secret, int $timeSlice): string
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
     * Decode base32 string.
     */
    private function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsFlipped)) {
                return false;
            }
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

    /**
     * Enable two-factor authentication for a user.
     */
    public function enableTwoFactor(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        $user->generateRecoveryCodes();
    }

    /**
     * Disable two-factor authentication for a user.
     */
    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    /**
     * Get the decrypted secret for a user.
     */
    public function getDecryptedSecret(User $user): ?string
    {
        if (!$user->two_factor_secret) {
            return null;
        }

        return Crypt::decryptString($user->two_factor_secret);
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = $user->getRecoveryCodes();
        
        foreach ($recoveryCodes as $recoveryCode) {
            if (hash_equals($recoveryCode, $code)) {
                $user->replaceRecoveryCode($code);
                return true;
            }
        }
        
        return false;
    }
}