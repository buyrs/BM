<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class SignatureValidationService
{
    /**
     * Validate signature data integrity
     */
    public function validateSignatureData(string $signatureData): array
    {
        $validation = [
            'is_valid' => false,
            'errors' => [],
            'metadata' => []
        ];

        try {
            // Check if it's a valid base64 image
            if (!$this->isValidBase64Image($signatureData)) {
                $validation['errors'][] = 'Invalid base64 image data';
                return $validation;
            }

            // Decode and analyze the image
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
            $imageInfo = $this->analyzeImageData($imageData);

            // Check image dimensions
            if ($imageInfo['width'] < 100 || $imageInfo['height'] < 50) {
                $validation['errors'][] = 'Signature too small (minimum 100x50 pixels)';
            }

            if ($imageInfo['width'] > 2000 || $imageInfo['height'] > 1000) {
                $validation['errors'][] = 'Signature too large (maximum 2000x1000 pixels)';
            }

            // Check if signature has enough content (not just blank)
            if (!$this->hasSignatureContent($imageData)) {
                $validation['errors'][] = 'Signature appears to be blank or too light';
            }

            // Check file size
            if (strlen($imageData) > 2 * 1024 * 1024) { // 2MB max
                $validation['errors'][] = 'Signature file too large (maximum 2MB)';
            }

            $validation['is_valid'] = empty($validation['errors']);
            $validation['metadata'] = $imageInfo;

        } catch (Exception $e) {
            Log::error('Signature validation failed', [
                'error' => $e->getMessage()
            ]);
            $validation['errors'][] = 'Signature validation failed';
        }

        return $validation;
    }

    /**
     * Check if string is valid base64 image
     */
    protected function isValidBase64Image(string $data): bool
    {
        // Check if it starts with data:image/
        if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $data)) {
            return false;
        }

        // Extract base64 part
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $data);

        // Check if it's valid base64
        if (!base64_decode($base64, true)) {
            return false;
        }

        return true;
    }

    /**
     * Analyze image data to extract metadata
     */
    protected function analyzeImageData(string $imageData): array
    {
        try {
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return ['width' => 0, 'height' => 0, 'type' => 'unknown'];
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $type = $this->getImageType($imageData);

            imagedestroy($image);

            return [
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'file_size' => strlen($imageData)
            ];

        } catch (Exception $e) {
            return ['width' => 0, 'height' => 0, 'type' => 'unknown'];
        }
    }

    /**
     * Get image type from data
     */
    protected function getImageType(string $imageData): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpeg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            default:
                return 'unknown';
        }
    }

    /**
     * Check if signature has enough content (not blank)
     */
    protected function hasSignatureContent(string $imageData): bool
    {
        try {
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return false;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Sample pixels to check for non-white content
            $sampleCount = min(100, $width * $height / 10);
            $nonWhitePixels = 0;

            for ($i = 0; $i < $sampleCount; $i++) {
                $x = rand(0, $width - 1);
                $y = rand(0, $height - 1);
                
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Check if pixel is not white (threshold: 240)
                if ($r < 240 || $g < 240 || $b < 240) {
                    $nonWhitePixels++;
                }
            }

            imagedestroy($image);

            // Consider signature valid if at least 5% of sampled pixels are not white
            return ($nonWhitePixels / $sampleCount) >= 0.05;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generate signature hash for integrity verification
     */
    public function generateSignatureHash(string $signatureData, array $metadata = []): string
    {
        $data = $signatureData . json_encode($metadata);
        return hash('sha256', $data);
    }

    /**
     * Verify signature integrity
     */
    public function verifySignatureIntegrity(string $signatureData, string $expectedHash, array $metadata = []): bool
    {
        $actualHash = $this->generateSignatureHash($signatureData, $metadata);
        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Extract signature metadata for legal purposes
     */
    public function extractSignatureMetadata(string $signatureData, array $requestMetadata = []): array
    {
        $validation = $this->validateSignatureData($signatureData);
        
        return [
            'validation' => $validation,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'signature_hash' => $this->generateSignatureHash($signatureData, $requestMetadata),
            'request_metadata' => $requestMetadata
        ];
    }

    /**
     * Optimize signature image for storage
     */
    public function optimizeSignatureImage(string $signatureData, int $maxWidth = 800, int $quality = 90): string
    {
        try {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
            $image = imagecreatefromstring($imageData);
            
            if (!$image) {
                return $signatureData;
            }

            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Only resize if larger than max width
            if ($originalWidth > $maxWidth) {
                $newHeight = ($originalHeight * $maxWidth) / $originalWidth;
                $resizedImage = imagecreatetruecolor($maxWidth, $newHeight);
                
                // Preserve transparency
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $originalWidth, $originalHeight);
                
                imagedestroy($image);
                $image = $resizedImage;
            }

            // Convert to JPEG for better compression
            ob_start();
            imagejpeg($image, null, $quality);
            $optimizedData = ob_get_contents();
            ob_end_clean();

            imagedestroy($image);

            return 'data:image/jpeg;base64,' . base64_encode($optimizedData);

        } catch (Exception $e) {
            Log::warning('Failed to optimize signature image', [
                'error' => $e->getMessage()
            ]);
            return $signatureData;
        }
    }

    /**
     * Create signature preview thumbnail
     */
    public function createSignatureThumbnail(string $signatureData, int $width = 200, int $height = 100): string
    {
        try {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
            $image = imagecreatefromstring($imageData);
            
            if (!$image) {
                return $signatureData;
            }

            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Calculate new dimensions maintaining aspect ratio
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);

            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            ob_start();
            imagepng($thumbnail);
            $thumbnailData = ob_get_contents();
            ob_end_clean();

            imagedestroy($image);
            imagedestroy($thumbnail);

            return 'data:image/png;base64,' . base64_encode($thumbnailData);

        } catch (Exception $e) {
            Log::warning('Failed to create signature thumbnail', [
                'error' => $e->getMessage()
            ]);
            return $signatureData;
        }
    }
}
