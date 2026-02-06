<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\ChecklistItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PhotoVerificationService extends BaseService
{
    /**
     * Verification rules configuration.
     */
    private array $rules = [
        'max_time_difference_hours' => 24,    // Max hours between photo and mission start
        'max_distance_km' => 0.5,             // Max km from property location
        'required_exif_fields' => ['DateTimeOriginal'],
        'min_resolution' => ['width' => 640, 'height' => 480],
        'allowed_formats' => ['jpg', 'jpeg', 'png', 'heic', 'heif'],
    ];

    /**
     * Verify a photo against mission context.
     */
    public function verifyPhoto(UploadedFile|string $photo, Mission $mission, ?ChecklistItem $item = null): array
    {
        $results = [
            'passed' => true,
            'score' => 100,
            'checks' => [],
            'warnings' => [],
            'metadata' => [],
        ];

        // Get photo path
        $photoPath = $photo instanceof UploadedFile 
            ? $photo->getRealPath() 
            : Storage::path($photo);

        if (!file_exists($photoPath)) {
            return [
                'passed' => false,
                'score' => 0,
                'checks' => [
                    'file_exists' => ['passed' => false, 'message' => 'Photo file not found'],
                ],
                'warnings' => [],
                'metadata' => [],
            ];
        }

        // Extract EXIF data
        $exif = $this->extractExif($photoPath);
        $results['metadata']['exif'] = $exif;

        // Run verification checks
        $results['checks']['format'] = $this->checkFormat($photoPath);
        $results['checks']['resolution'] = $this->checkResolution($photoPath);
        $results['checks']['timestamp'] = $this->checkTimestamp($exif, $mission);
        $results['checks']['location'] = $this->checkLocation($exif, $mission);
        $results['checks']['manipulation'] = $this->checkManipulation($exif);

        // Calculate overall score and pass/fail
        $totalWeight = 0;
        $weightedScore = 0;
        $weights = [
            'format' => 10,
            'resolution' => 10,
            'timestamp' => 35,
            'location' => 35,
            'manipulation' => 10,
        ];

        foreach ($results['checks'] as $checkName => $check) {
            $weight = $weights[$checkName] ?? 10;
            $totalWeight += $weight;
            
            if ($check['passed']) {
                $weightedScore += $weight;
            } else {
                $results['passed'] = false;
            }

            // Collect warnings
            if (isset($check['warning'])) {
                $results['warnings'][] = $check['warning'];
            }
        }

        $results['score'] = $totalWeight > 0 
            ? round(($weightedScore / $totalWeight) * 100) 
            : 0;

        // Determine verification status
        $results['status'] = $this->determineStatus($results);

        return $results;
    }

    /**
     * Extract EXIF metadata from photo.
     */
    private function extractExif(string $path): array
    {
        try {
            $exif = @exif_read_data($path, 'ANY_TAG', true);
            
            if (!$exif) {
                return ['available' => false];
            }

            $data = ['available' => true];

            // DateTime
            if (isset($exif['EXIF']['DateTimeOriginal'])) {
                $data['datetime'] = $exif['EXIF']['DateTimeOriginal'];
                $data['datetime_parsed'] = Carbon::createFromFormat(
                    'Y:m:d H:i:s', 
                    $exif['EXIF']['DateTimeOriginal']
                );
            } elseif (isset($exif['IFD0']['DateTime'])) {
                $data['datetime'] = $exif['IFD0']['DateTime'];
                $data['datetime_parsed'] = Carbon::createFromFormat(
                    'Y:m:d H:i:s', 
                    $exif['IFD0']['DateTime']
                );
            }

            // GPS coordinates
            if (isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
                $data['latitude'] = $this->convertGpsCoordinate(
                    $exif['GPS']['GPSLatitude'],
                    $exif['GPS']['GPSLatitudeRef'] ?? 'N'
                );
                $data['longitude'] = $this->convertGpsCoordinate(
                    $exif['GPS']['GPSLongitude'],
                    $exif['GPS']['GPSLongitudeRef'] ?? 'E'
                );
            }

            // Device info
            if (isset($exif['IFD0']['Make'])) {
                $data['device_make'] = $exif['IFD0']['Make'];
            }
            if (isset($exif['IFD0']['Model'])) {
                $data['device_model'] = $exif['IFD0']['Model'];
            }

            // Software (editing detection)
            if (isset($exif['IFD0']['Software'])) {
                $data['software'] = $exif['IFD0']['Software'];
            }

            return $data;
        } catch (\Exception $e) {
            return ['available' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Convert GPS coordinates from EXIF format.
     */
    private function convertGpsCoordinate(array $coordinate, string $ref): float
    {
        $degrees = $this->rationalToFloat($coordinate[0]);
        $minutes = $this->rationalToFloat($coordinate[1]);
        $seconds = $this->rationalToFloat($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal *= -1;
        }

        return $decimal;
    }

    /**
     * Convert rational EXIF value to float.
     */
    private function rationalToFloat(string $rational): float
    {
        $parts = explode('/', $rational);
        if (count($parts) === 2 && $parts[1] != 0) {
            return (float) $parts[0] / (float) $parts[1];
        }
        return (float) $parts[0];
    }

    /**
     * Check photo format.
     */
    private function checkFormat(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($path);
        
        $allowed = in_array($extension, $this->rules['allowed_formats']) ||
                   in_array(strtolower(explode('/', $mimeType)[1] ?? ''), $this->rules['allowed_formats']);

        return [
            'passed' => $allowed,
            'message' => $allowed ? 'Valid format' : "Invalid format: {$extension}",
            'details' => ['extension' => $extension, 'mime' => $mimeType],
        ];
    }

    /**
     * Check photo resolution.
     */
    private function checkResolution(string $path): array
    {
        $imageInfo = @getimagesize($path);
        
        if (!$imageInfo) {
            return [
                'passed' => false,
                'message' => 'Could not read image dimensions',
            ];
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $minWidth = $this->rules['min_resolution']['width'];
        $minHeight = $this->rules['min_resolution']['height'];

        $passed = $width >= $minWidth && $height >= $minHeight;

        return [
            'passed' => $passed,
            'message' => $passed 
                ? "Resolution OK ({$width}x{$height})" 
                : "Resolution too low ({$width}x{$height}), minimum {$minWidth}x{$minHeight}",
            'details' => ['width' => $width, 'height' => $height],
        ];
    }

    /**
     * Check photo timestamp against mission.
     */
    private function checkTimestamp(array $exif, Mission $mission): array
    {
        if (!isset($exif['datetime_parsed'])) {
            return [
                'passed' => false,
                'message' => 'No timestamp in photo metadata',
                'warning' => 'Photo has no EXIF timestamp - cannot verify when it was taken',
            ];
        }

        $photoTime = $exif['datetime_parsed'];
        $missionStart = $mission->started_at ?? $mission->created_at;
        $missionEnd = $mission->completed_at ?? Carbon::now();

        // Photo should be taken during or shortly before mission
        $maxHours = $this->rules['max_time_difference_hours'];
        $earliestAllowed = Carbon::parse($missionStart)->subHours($maxHours);
        $latestAllowed = Carbon::parse($missionEnd)->addHours(1);

        $isValid = $photoTime->between($earliestAllowed, $latestAllowed);
        
        $hoursDiff = $photoTime->diffInHours($missionStart, false);

        return [
            'passed' => $isValid,
            'message' => $isValid 
                ? 'Timestamp verified' 
                : "Photo taken outside mission window (diff: {$hoursDiff}h)",
            'details' => [
                'photo_time' => $photoTime->toIso8601String(),
                'mission_start' => Carbon::parse($missionStart)->toIso8601String(),
                'hours_difference' => $hoursDiff,
            ],
            'warning' => !$isValid ? "Photo timestamp ({$photoTime->format('Y-m-d H:i')}) doesn't match mission time" : null,
        ];
    }

    /**
     * Check photo location against property.
     */
    private function checkLocation(array $exif, Mission $mission): array
    {
        if (!isset($exif['latitude']) || !isset($exif['longitude'])) {
            return [
                'passed' => false,
                'message' => 'No GPS data in photo',
                'warning' => 'Photo has no location data - cannot verify where it was taken',
            ];
        }

        $property = $mission->property;
        
        if (!$property || !$property->latitude || !$property->longitude) {
            return [
                'passed' => true, // Can't verify, so pass with warning
                'message' => 'Property has no location data for comparison',
                'warning' => 'Cannot verify location - property coordinates not set',
            ];
        }

        $distance = $this->calculateDistance(
            $exif['latitude'],
            $exif['longitude'],
            $property->latitude,
            $property->longitude
        );

        $maxDistance = $this->rules['max_distance_km'];
        $passed = $distance <= $maxDistance;

        return [
            'passed' => $passed,
            'message' => $passed 
                ? "Location verified ({$distance}km from property)" 
                : "Photo taken {$distance}km from property (max: {$maxDistance}km)",
            'details' => [
                'photo_location' => ['lat' => $exif['latitude'], 'lng' => $exif['longitude']],
                'property_location' => ['lat' => $property->latitude, 'lng' => $property->longitude],
                'distance_km' => round($distance, 3),
            ],
            'warning' => !$passed ? "Photo location is {$distance}km from property" : null,
        ];
    }

    /**
     * Check for signs of photo manipulation.
     */
    private function checkManipulation(array $exif): array
    {
        $suspiciousSoftware = [
            'photoshop', 'gimp', 'lightroom', 'snapseed', 'vsco',
            'facetune', 'airbrush', 'beautycam', 'meitu'
        ];

        $warnings = [];

        // Check for known editing software
        if (isset($exif['software'])) {
            $software = strtolower($exif['software']);
            foreach ($suspiciousSoftware as $editor) {
                if (strpos($software, $editor) !== false) {
                    $warnings[] = "Edited with {$exif['software']}";
                }
            }
        }

        // Missing expected metadata could indicate manipulation
        if (!$exif['available']) {
            $warnings[] = 'No EXIF metadata (may have been stripped)';
        } elseif (!isset($exif['device_make']) && !isset($exif['device_model'])) {
            $warnings[] = 'No device information in metadata';
        }

        $passed = empty($warnings);

        return [
            'passed' => $passed,
            'message' => $passed ? 'No manipulation detected' : 'Possible manipulation detected',
            'details' => ['warnings' => $warnings],
            'warning' => !$passed ? implode(', ', $warnings) : null,
        ];
    }

    /**
     * Calculate distance between two GPS points.
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 3);
    }

    /**
     * Determine overall verification status.
     */
    private function determineStatus(array $results): string
    {
        if ($results['score'] >= 90 && $results['passed']) {
            return 'verified';
        } elseif ($results['score'] >= 70) {
            return 'review_needed';
        } elseif ($results['score'] >= 50) {
            return 'suspicious';
        } else {
            return 'failed';
        }
    }

    /**
     * Batch verify all photos for a mission.
     */
    public function verifyMissionPhotos(Mission $mission): array
    {
        $results = [
            'mission_id' => $mission->id,
            'total_photos' => 0,
            'verified' => 0,
            'review_needed' => 0,
            'suspicious' => 0,
            'failed' => 0,
            'overall_score' => 0,
            'photos' => [],
        ];

        $checklists = $mission->checklists()->with('items')->get();
        
        foreach ($checklists as $checklist) {
            foreach ($checklist->items as $item) {
                if ($item->photo_path) {
                    $verification = $this->verifyPhoto($item->photo_path, $mission, $item);
                    
                    $results['photos'][] = [
                        'checklist_item_id' => $item->id,
                        'photo_path' => $item->photo_path,
                        'verification' => $verification,
                    ];

                    $results['total_photos']++;
                    $results[$verification['status']]++;
                }
            }
        }

        // Calculate overall mission photo score
        if ($results['total_photos'] > 0) {
            $totalScore = array_sum(array_column(
                array_column($results['photos'], 'verification'),
                'score'
            ));
            $results['overall_score'] = round($totalScore / $results['total_photos']);
        }

        return $results;
    }
}
