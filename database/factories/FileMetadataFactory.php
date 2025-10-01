<?php

namespace Database\Factories;

use App\Models\FileMetadata;
use App\Models\Property;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileMetadataFactory extends Factory
{
    protected $model = FileMetadata::class;

    public function definition(): array
    {
        $mimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'text/plain',
            'application/msword'
        ];

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/msword' => 'doc'
        ];

        $mimeType = $this->faker->randomElement($mimeTypes);
        $extension = $extensions[$mimeType];
        $filename = $this->faker->slug() . '.' . $extension;

        return [
            'filename' => $filename,
            'original_name' => $this->faker->words(3, true) . '.' . $extension,
            'path' => 'files/' . $this->faker->year() . '/' . $this->faker->month() . '/' . $filename,
            'size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
            'mime_type' => $mimeType,
            'file_hash' => $this->faker->sha256(),
            'metadata' => $this->generateMetadata($mimeType),
            'property_id' => null,
            'mission_id' => null,
            'checklist_id' => null,
            'uploaded_by' => User::factory(),
            'storage_disk' => 'local',
            'is_public' => $this->faker->boolean(30), // 30% chance of being public
            'last_accessed_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate metadata based on MIME type.
     */
    private function generateMetadata(string $mimeType): ?array
    {
        if (str_starts_with($mimeType, 'image/')) {
            return [
                'width' => $this->faker->numberBetween(100, 4000),
                'height' => $this->faker->numberBetween(100, 3000),
                'type' => $this->faker->numberBetween(1, 3),
            ];
        }

        return null;
    }

    /**
     * Create an image file.
     */
    public function image(): static
    {
        return $this->state(function (array $attributes) {
            $mimeType = $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']);
            $extension = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif'
            ][$mimeType];

            $filename = $this->faker->slug() . '.' . $extension;

            return [
                'filename' => $filename,
                'original_name' => $this->faker->words(2, true) . '.' . $extension,
                'mime_type' => $mimeType,
                'metadata' => [
                    'width' => $this->faker->numberBetween(100, 2000),
                    'height' => $this->faker->numberBetween(100, 2000),
                    'type' => $mimeType === 'image/jpeg' ? 2 : ($mimeType === 'image/png' ? 3 : 1),
                ],
            ];
        });
    }

    /**
     * Create a document file.
     */
    public function document(): static
    {
        return $this->state(function (array $attributes) {
            $mimeType = $this->faker->randomElement(['application/pdf', 'application/msword', 'text/plain']);
            $extension = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'text/plain' => 'txt'
            ][$mimeType];

            $filename = $this->faker->slug() . '.' . $extension;

            return [
                'filename' => $filename,
                'original_name' => $this->faker->words(3, true) . '.' . $extension,
                'mime_type' => $mimeType,
                'metadata' => null,
            ];
        });
    }

    /**
     * Create a public file.
     */
    public function public(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_public' => true,
            ];
        });
    }

    /**
     * Create a private file.
     */
    public function private(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_public' => false,
            ];
        });
    }

    /**
     * Create a file with property association.
     */
    public function forProperty(?Property $property = null): static
    {
        return $this->state(function (array $attributes) use ($property) {
            return [
                'property_id' => $property?->id ?? Property::factory(),
            ];
        });
    }

    /**
     * Create a file with mission association.
     */
    public function forMission(?Mission $mission = null): static
    {
        return $this->state(function (array $attributes) use ($mission) {
            return [
                'mission_id' => $mission?->id ?? Mission::factory(),
            ];
        });
    }

    /**
     * Create a file with checklist association.
     */
    public function forChecklist(?Checklist $checklist = null): static
    {
        return $this->state(function (array $attributes) use ($checklist) {
            return [
                'checklist_id' => $checklist?->id ?? Checklist::factory(),
            ];
        });
    }

    /**
     * Create a file uploaded by specific user.
     */
    public function uploadedBy(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'uploaded_by' => $user?->id ?? User::factory(),
            ];
        });
    }

    /**
     * Create a large file.
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'size' => $this->faker->numberBetween(50 * 1024 * 1024, 100 * 1024 * 1024), // 50MB to 100MB
            ];
        });
    }

    /**
     * Create a small file.
     */
    public function small(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'size' => $this->faker->numberBetween(1024, 100 * 1024), // 1KB to 100KB
            ];
        });
    }

    /**
     * Create a recently accessed file.
     */
    public function recentlyAccessed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'last_accessed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Create a file with thumbnails metadata.
     */
    public function withThumbnails(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['thumbnails'] = [
                'small' => [
                    'path' => 'thumbnails/small_' . $this->faker->uuid() . '.jpg',
                    'width' => 150,
                    'height' => 150,
                    'url' => $this->faker->url(),
                ],
                'medium' => [
                    'path' => 'thumbnails/medium_' . $this->faker->uuid() . '.jpg',
                    'width' => 300,
                    'height' => 300,
                    'url' => $this->faker->url(),
                ],
                'large' => [
                    'path' => 'thumbnails/large_' . $this->faker->uuid() . '.jpg',
                    'width' => 800,
                    'height' => 600,
                    'url' => $this->faker->url(),
                ],
            ];

            return [
                'metadata' => $metadata,
            ];
        });
    }
}