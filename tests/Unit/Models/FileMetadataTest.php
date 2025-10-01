<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FileMetadata;
use App\Models\Property;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class FileMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'filename',
            'original_name',
            'path',
            'size',
            'mime_type',
            'file_hash',
            'metadata',
            'property_id',
            'mission_id',
            'checklist_id',
            'uploaded_by',
            'storage_disk',
            'is_public',
            'last_accessed_at',
        ];

        $fileMetadata = new FileMetadata();
        $this->assertEquals($fillable, $fileMetadata->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $fileMetadata = FileMetadata::factory()->create([
            'metadata' => ['width' => 200, 'height' => 150],
            'is_public' => true,
            'last_accessed_at' => now()
        ]);

        $this->assertIsArray($fileMetadata->metadata);
        $this->assertIsBool($fileMetadata->is_public);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fileMetadata->last_accessed_at);
    }

    /** @test */
    public function it_belongs_to_property()
    {
        $property = Property::factory()->create();
        $fileMetadata = FileMetadata::factory()->create(['property_id' => $property->id]);

        $this->assertInstanceOf(Property::class, $fileMetadata->property);
        $this->assertEquals($property->id, $fileMetadata->property->id);
    }

    /** @test */
    public function it_belongs_to_mission()
    {
        $mission = Mission::factory()->create();
        $fileMetadata = FileMetadata::factory()->create(['mission_id' => $mission->id]);

        $this->assertInstanceOf(Mission::class, $fileMetadata->mission);
        $this->assertEquals($mission->id, $fileMetadata->mission->id);
    }

    /** @test */
    public function it_belongs_to_checklist()
    {
        $checklist = Checklist::factory()->create();
        $fileMetadata = FileMetadata::factory()->create(['checklist_id' => $checklist->id]);

        $this->assertInstanceOf(Checklist::class, $fileMetadata->checklist);
        $this->assertEquals($checklist->id, $fileMetadata->checklist->id);
    }

    /** @test */
    public function it_belongs_to_uploaded_by_user()
    {
        $user = User::factory()->create();
        $fileMetadata = FileMetadata::factory()->create(['uploaded_by' => $user->id]);

        $this->assertInstanceOf(User::class, $fileMetadata->uploadedBy);
        $this->assertEquals($user->id, $fileMetadata->uploadedBy->id);
    }

    /** @test */
    public function it_generates_url_attribute()
    {
        $fileMetadata = FileMetadata::factory()->create([
            'path' => 'test/file.jpg',
            'storage_disk' => 'local'
        ]);

        $url = $fileMetadata->url;
        $this->assertIsString($url);
        $this->assertStringContainsString('test/file.jpg', $url);
    }

    /** @test */
    public function it_generates_human_readable_size()
    {
        $fileMetadata1 = FileMetadata::factory()->create(['size' => 1024]); // 1KB
        $fileMetadata2 = FileMetadata::factory()->create(['size' => 1024 * 1024]); // 1MB
        $fileMetadata3 = FileMetadata::factory()->create(['size' => 1024 * 1024 * 1024]); // 1GB

        $this->assertEquals('1 KB', $fileMetadata1->human_size);
        $this->assertEquals('1 MB', $fileMetadata2->human_size);
        $this->assertEquals('1 GB', $fileMetadata3->human_size);
    }

    /** @test */
    public function it_handles_zero_size_files()
    {
        $fileMetadata = FileMetadata::factory()->create(['size' => 0]);

        $this->assertEquals('0 B', $fileMetadata->human_size);
    }

    /** @test */
    public function it_detects_image_files()
    {
        $imageFile = FileMetadata::factory()->create(['mime_type' => 'image/jpeg']);
        $textFile = FileMetadata::factory()->create(['mime_type' => 'text/plain']);
        $pdfFile = FileMetadata::factory()->create(['mime_type' => 'application/pdf']);

        $this->assertTrue($imageFile->isImage());
        $this->assertFalse($textFile->isImage());
        $this->assertFalse($pdfFile->isImage());
    }

    /** @test */
    public function it_detects_various_image_mime_types()
    {
        $imageMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ];

        foreach ($imageMimeTypes as $mimeType) {
            $fileMetadata = FileMetadata::factory()->create(['mime_type' => $mimeType]);
            $this->assertTrue($fileMetadata->isImage(), "Failed for MIME type: {$mimeType}");
        }
    }

    /** @test */
    public function it_checks_if_file_exists_in_storage()
    {
        // Create a file in storage
        Storage::put('test/existing-file.jpg', 'fake content');
        
        $existingFile = FileMetadata::factory()->create([
            'path' => 'test/existing-file.jpg',
            'storage_disk' => 'local'
        ]);

        $nonExistingFile = FileMetadata::factory()->create([
            'path' => 'test/non-existing-file.jpg',
            'storage_disk' => 'local'
        ]);

        $this->assertTrue($existingFile->exists());
        $this->assertFalse($nonExistingFile->exists());
    }

    /** @test */
    public function it_can_delete_file_from_storage()
    {
        // Create a file in storage
        Storage::put('test/delete-me.jpg', 'fake content');
        
        $fileMetadata = FileMetadata::factory()->create([
            'path' => 'test/delete-me.jpg',
            'storage_disk' => 'local'
        ]);

        $this->assertTrue($fileMetadata->exists());
        
        $result = $fileMetadata->deleteFile();
        
        $this->assertTrue($result);
        $this->assertFalse($fileMetadata->exists());
        Storage::assertMissing('test/delete-me.jpg');
    }

    /** @test */
    public function it_returns_true_when_deleting_non_existent_file()
    {
        $fileMetadata = FileMetadata::factory()->create([
            'path' => 'test/non-existent.jpg',
            'storage_disk' => 'local'
        ]);

        $result = $fileMetadata->deleteFile();
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_mark_file_as_accessed()
    {
        $fileMetadata = FileMetadata::factory()->create(['last_accessed_at' => null]);

        $this->assertNull($fileMetadata->last_accessed_at);

        $fileMetadata->markAsAccessed();
        $fileMetadata->refresh();

        $this->assertNotNull($fileMetadata->last_accessed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fileMetadata->last_accessed_at);
    }

    /** @test */
    public function it_updates_last_accessed_timestamp_when_marked()
    {
        $oldTimestamp = now()->subHour();
        $fileMetadata = FileMetadata::factory()->create(['last_accessed_at' => $oldTimestamp]);

        $fileMetadata->markAsAccessed();
        $fileMetadata->refresh();

        $this->assertTrue($fileMetadata->last_accessed_at->isAfter($oldTimestamp));
    }

    /** @test */
    public function it_handles_null_relationships_gracefully()
    {
        $fileMetadata = FileMetadata::factory()->create([
            'property_id' => null,
            'mission_id' => null,
            'checklist_id' => null
        ]);

        $this->assertNull($fileMetadata->property);
        $this->assertNull($fileMetadata->mission);
        $this->assertNull($fileMetadata->checklist);
    }

    /** @test */
    public function it_stores_and_retrieves_complex_metadata()
    {
        $complexMetadata = [
            'width' => 1920,
            'height' => 1080,
            'exif' => [
                'camera_make' => 'Canon',
                'camera_model' => 'EOS 5D',
                'date_taken' => '2023-01-01 12:00:00'
            ],
            'thumbnails' => [
                'small' => ['path' => 'thumbnails/small.jpg', 'width' => 150, 'height' => 150],
                'medium' => ['path' => 'thumbnails/medium.jpg', 'width' => 300, 'height' => 300]
            ],
            'colors' => [
                'dominant' => '#ff0000',
                'palette' => ['#ff0000', '#00ff00', '#0000ff']
            ]
        ];

        $fileMetadata = FileMetadata::factory()->create(['metadata' => $complexMetadata]);

        $this->assertEquals($complexMetadata, $fileMetadata->metadata);
        $this->assertEquals('Canon', $fileMetadata->metadata['exif']['camera_make']);
        $this->assertEquals(150, $fileMetadata->metadata['thumbnails']['small']['width']);
        $this->assertCount(3, $fileMetadata->metadata['colors']['palette']);
    }

    /** @test */
    public function it_handles_empty_metadata()
    {
        $fileMetadata = FileMetadata::factory()->create(['metadata' => null]);

        $this->assertNull($fileMetadata->metadata);
    }

    /** @test */
    public function it_handles_empty_array_metadata()
    {
        $fileMetadata = FileMetadata::factory()->create(['metadata' => []]);

        $this->assertEquals([], $fileMetadata->metadata);
    }

    /** @test */
    public function it_formats_large_file_sizes_correctly()
    {
        $sizes = [
            500 => '500 B',
            1536 => '1.5 KB', // 1.5 * 1024
            2097152 => '2 MB', // 2 * 1024 * 1024
            3221225472 => '3 GB', // 3 * 1024 * 1024 * 1024
            1099511627776 => '1 TB' // 1 * 1024^4
        ];

        foreach ($sizes as $bytes => $expected) {
            $fileMetadata = FileMetadata::factory()->create(['size' => $bytes]);
            $this->assertEquals($expected, $fileMetadata->human_size, "Failed for size: {$bytes}");
        }
    }

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $fileMetadata = FileMetadata::factory()->create();

        $this->assertInstanceOf(FileMetadata::class, $fileMetadata);
        $this->assertNotNull($fileMetadata->filename);
        $this->assertNotNull($fileMetadata->original_name);
        $this->assertNotNull($fileMetadata->path);
        $this->assertGreaterThan(0, $fileMetadata->size);
        $this->assertNotNull($fileMetadata->mime_type);
    }

    /** @test */
    public function it_can_be_created_with_specific_attributes()
    {
        $attributes = [
            'filename' => 'test-file.jpg',
            'original_name' => 'Original Test File.jpg',
            'path' => 'uploads/test-file.jpg',
            'size' => 2048,
            'mime_type' => 'image/jpeg',
            'file_hash' => 'abc123def456',
            'is_public' => true
        ];

        $fileMetadata = FileMetadata::factory()->create($attributes);

        foreach ($attributes as $key => $value) {
            $this->assertEquals($value, $fileMetadata->$key);
        }
    }
}