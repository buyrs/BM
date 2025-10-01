<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileOrganizationService;
use App\Services\ImageOptimizationService;
use App\Models\FileMetadata;
use App\Models\Property;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Mockery;

class FileOrganizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FileOrganizationService $fileOrganizationService;
    protected $mockImageOptimizationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        // Mock the image optimization service
        $this->mockImageOptimizationService = Mockery::mock(ImageOptimizationService::class);
        
        // Create service instance with mocked dependency
        $this->fileOrganizationService = new FileOrganizationService($this->mockImageOptimizationService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_upload_file_with_basic_organization()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $fileMetadata = $this->fileOrganizationService->uploadFile($file);

        $this->assertInstanceOf(FileMetadata::class, $fileMetadata);
        $this->assertEquals('test.jpg', $fileMetadata->original_name);
        $this->assertEquals($user->id, $fileMetadata->uploaded_by);
        $this->assertStringContainsString('files/system', $fileMetadata->path);
        Storage::assertExists($fileMetadata->path);
    }

    /** @test */
    public function it_organizes_files_by_property_hierarchy()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $property = Property::factory()->create();
        $mission = Mission::factory()->create(['property_id' => $property->id]);
        $checklist = Checklist::factory()->create(['mission_id' => $mission->id]);

        $file = UploadedFile::fake()->image('property-file.jpg');

        $fileMetadata = $this->fileOrganizationService->uploadFile(
            $file,
            $property->id,
            $mission->id,
            $checklist->id
        );

        $this->assertEquals($property->id, $fileMetadata->property_id);
        $this->assertEquals($mission->id, $fileMetadata->mission_id);
        $this->assertEquals($checklist->id, $fileMetadata->checklist_id);
        $this->assertStringContainsString("files/properties/{$property->id}/missions/{$mission->id}/checklists/{$checklist->id}", $fileMetadata->path);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $file1 = UploadedFile::fake()->image('test.jpg');
        $file2 = UploadedFile::fake()->image('test.jpg');

        $fileMetadata1 = $this->fileOrganizationService->uploadFile($file1);
        $fileMetadata2 = $this->fileOrganizationService->uploadFile($file2);

        $this->assertNotEquals($fileMetadata1->filename, $fileMetadata2->filename);
        $this->assertStringEndsWith('.jpg', $fileMetadata1->filename);
        $this->assertStringEndsWith('.jpg', $fileMetadata2->filename);
    }

    /** @test */
    public function it_calculates_file_hash_for_deduplication()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $fileMetadata = $this->fileOrganizationService->uploadFile($file);

        $this->assertNotNull($fileMetadata->file_hash);
        $this->assertEquals(64, strlen($fileMetadata->file_hash)); // SHA256 hash length
    }

    /** @test */
    public function it_extracts_image_metadata()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $file = UploadedFile::fake()->image('test.jpg', 200, 150);

        $fileMetadata = $this->fileOrganizationService->uploadFile($file);

        $this->assertArrayHasKey('width', $fileMetadata->metadata);
        $this->assertArrayHasKey('height', $fileMetadata->metadata);
        $this->assertEquals(200, $fileMetadata->metadata['width']);
        $this->assertEquals(150, $fileMetadata->metadata['height']);
    }

    /** @test */
    public function it_generates_thumbnails_for_images()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        // Mock the image optimization service to expect thumbnail generation
        $this->mockImageOptimizationService
            ->shouldReceive('generateThumbnails')
            ->once()
            ->andReturn(['small' => ['path' => 'thumbnails/small.jpg']]);

        $fileMetadata = $this->fileOrganizationService->uploadFile($file);

        $this->assertTrue($fileMetadata->isImage());
    }

    /** @test */
    public function it_handles_thumbnail_generation_failure_gracefully()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        // Mock the image optimization service to throw an exception
        $this->mockImageOptimizationService
            ->shouldReceive('generateThumbnails')
            ->once()
            ->andThrow(new \Exception('Thumbnail generation failed'));

        // Should not throw exception, just log warning
        $fileMetadata = $this->fileOrganizationService->uploadFile($file);

        $this->assertInstanceOf(FileMetadata::class, $fileMetadata);
    }

    /** @test */
    public function it_can_move_files_between_organizations()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $property1 = Property::factory()->create();
        $property2 = Property::factory()->create();
        $mission = Mission::factory()->create(['property_id' => $property2->id]);

        $file = UploadedFile::fake()->image('test.jpg');
        $fileMetadata = $this->fileOrganizationService->uploadFile($file, $property1->id);

        $originalPath = $fileMetadata->path;

        $success = $this->fileOrganizationService->moveFile($fileMetadata, $property2->id, $mission->id);

        $this->assertTrue($success);
        $fileMetadata->refresh();
        $this->assertEquals($property2->id, $fileMetadata->property_id);
        $this->assertEquals($mission->id, $fileMetadata->mission_id);
        $this->assertNotEquals($originalPath, $fileMetadata->path);
        $this->assertStringContainsString("properties/{$property2->id}/missions/{$mission->id}", $fileMetadata->path);
    }

    /** @test */
    public function it_can_get_files_by_hierarchy()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $property = Property::factory()->create();
        $mission = Mission::factory()->create(['property_id' => $property->id]);

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');
        $file3 = UploadedFile::fake()->image('test3.jpg');

        $this->fileOrganizationService->uploadFile($file1, $property->id, $mission->id);
        $this->fileOrganizationService->uploadFile($file2, $property->id, $mission->id);
        $this->fileOrganizationService->uploadFile($file3, $property->id); // Different mission

        $missionFiles = $this->fileOrganizationService->getFilesByHierarchy($property->id, $mission->id);
        $propertyFiles = $this->fileOrganizationService->getFilesByHierarchy($property->id);

        $this->assertCount(2, $missionFiles);
        $this->assertCount(3, $propertyFiles);
    }

    /** @test */
    public function it_can_filter_files_by_mime_type()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $imageFile = UploadedFile::fake()->image('test.jpg');
        $textFile = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $this->fileOrganizationService->uploadFile($imageFile);
        $this->fileOrganizationService->uploadFile($textFile);

        $imageFiles = $this->fileOrganizationService->getFilesByHierarchy(null, null, null, 'image');
        $textFiles = $this->fileOrganizationService->getFilesByHierarchy(null, null, null, 'text');

        $this->assertCount(1, $imageFiles);
        $this->assertCount(1, $textFiles);
        $this->assertTrue($imageFiles->first()->isImage());
        $this->assertFalse($textFiles->first()->isImage());
    }

    /** @test */
    public function it_checks_file_access_permissions_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fileMetadata = FileMetadata::factory()->create();

        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $admin);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_checks_file_access_permissions_for_file_owner()
    {
        $user = User::factory()->create(['role' => 'checker']);
        $fileMetadata = FileMetadata::factory()->create(['uploaded_by' => $user->id]);

        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $user);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_checks_file_access_permissions_for_public_files()
    {
        $user = User::factory()->create(['role' => 'checker']);
        $fileMetadata = FileMetadata::factory()->create(['is_public' => true]);

        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $user);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_checks_file_access_permissions_for_ops_users()
    {
        $ops = User::factory()->create(['role' => 'ops']);
        $property = Property::factory()->create();
        $fileMetadata = FileMetadata::factory()->create(['property_id' => $property->id]);

        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $ops);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_checks_file_access_permissions_for_checker_assigned_missions()
    {
        $checker = User::factory()->create(['role' => 'checker']);
        $mission = Mission::factory()->create(['checker_id' => $checker->id]);
        $fileMetadata = FileMetadata::factory()->create(['mission_id' => $mission->id]);

        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $checker);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_denies_file_access_for_unauthorized_users()
    {
        $user1 = User::factory()->create(['role' => 'checker']);
        $user2 = User::factory()->create(['role' => 'checker']);
        $mission = Mission::factory()->create(['assigned_to' => $user1->id]);
        $fileMetadata = FileMetadata::factory()->create([
            'mission_id' => $mission->id,
            'uploaded_by' => $user1->id,
            'is_public' => false
        ]);

        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $user2);

        $this->assertFalse($hasAccess);
    }

    /** @test */
    public function it_gets_storage_statistics()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $property = Property::factory()->create();

        // Create different types of files
        $imageFile = UploadedFile::fake()->image('test.jpg');
        $textFile = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        $pdfFile = UploadedFile::fake()->create('test.pdf', 200, 'application/pdf');

        $this->fileOrganizationService->uploadFile($imageFile, $property->id);
        $this->fileOrganizationService->uploadFile($textFile, $property->id);
        $this->fileOrganizationService->uploadFile($pdfFile, $property->id);

        $stats = $this->fileOrganizationService->getStorageStats();

        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_property', $stats);
        $this->assertEquals(3, $stats['total_files']);
        $this->assertGreaterThan(0, $stats['total_size']);
    }

    /** @test */
    public function it_can_find_duplicate_files()
    {
        $user = User::factory()->create();
        Auth::login($user);

        // Create two identical files
        $file1 = UploadedFile::fake()->image('test.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('test.jpg', 100, 100);

        $fileMetadata1 = $this->fileOrganizationService->uploadFile($file1);
        $fileMetadata2 = $this->fileOrganizationService->uploadFile($file2);

        // Manually set the same hash to simulate duplicate files
        $fileMetadata2->update(['file_hash' => $fileMetadata1->file_hash]);

        $duplicates = $this->fileOrganizationService->findDuplicateFiles();

        $this->assertCount(1, $duplicates);
        $this->assertEquals(2, $duplicates[0]['count']);
        $this->assertEquals($fileMetadata1->file_hash, $duplicates[0]['hash']);
    }

    /** @test */
    public function it_can_cleanup_orphaned_files()
    {
        // Create a file in storage without metadata record
        Storage::put('files/orphaned/test.jpg', 'fake content');

        $cleaned = $this->fileOrganizationService->cleanupOrphanedFiles();

        $this->assertContains('files/orphaned/test.jpg', $cleaned);
        Storage::assertMissing('files/orphaned/test.jpg');
    }

    /** @test */
    public function it_generates_organized_path_correctly()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        
        $reflection = new \ReflectionClass($this->fileOrganizationService);
        $method = $reflection->getMethod('generateOrganizedPath');
        $method->setAccessible(true);

        // Test system path (no property)
        $systemPath = $method->invoke($this->fileOrganizationService, $file);
        $this->assertStringContainsString('files/system', $systemPath);
        $this->assertStringContainsString(date('Y/m'), $systemPath);

        // Test property path
        $propertyPath = $method->invoke($this->fileOrganizationService, $file, 1);
        $this->assertStringContainsString('files/properties/1/general', $propertyPath);

        // Test mission path
        $missionPath = $method->invoke($this->fileOrganizationService, $file, 1, 2);
        $this->assertStringContainsString('files/properties/1/missions/2', $missionPath);

        // Test checklist path
        $checklistPath = $method->invoke($this->fileOrganizationService, $file, 1, 2, 3);
        $this->assertStringContainsString('files/properties/1/missions/2/checklists/3', $checklistPath);
    }

    /** @test */
    public function it_generates_unique_filename_with_proper_format()
    {
        $file = UploadedFile::fake()->image('My Test File.jpg');
        
        $reflection = new \ReflectionClass($this->fileOrganizationService);
        $method = $reflection->getMethod('generateUniqueFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->fileOrganizationService, $file);

        $this->assertStringEndsWith('.jpg', $filename);
        $this->assertStringContainsString('my-test-file', $filename);
        $this->assertMatchesRegularExpression('/my-test-file_\d+_[a-zA-Z0-9]{8}\.jpg/', $filename);
    }

    /** @test */
    public function it_extracts_file_metadata_for_images()
    {
        $file = UploadedFile::fake()->image('test.jpg', 300, 200);
        
        $reflection = new \ReflectionClass($this->fileOrganizationService);
        $method = $reflection->getMethod('extractFileMetadata');
        $method->setAccessible(true);

        $metadata = $method->invoke($this->fileOrganizationService, $file);

        $this->assertArrayHasKey('width', $metadata);
        $this->assertArrayHasKey('height', $metadata);
        $this->assertEquals(300, $metadata['width']);
        $this->assertEquals(200, $metadata['height']);
    }

    /** @test */
    public function it_handles_non_image_files_metadata_extraction()
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        
        $reflection = new \ReflectionClass($this->fileOrganizationService);
        $method = $reflection->getMethod('extractFileMetadata');
        $method->setAccessible(true);

        $metadata = $method->invoke($this->fileOrganizationService, $file);

        $this->assertIsArray($metadata);
        $this->assertArrayNotHasKey('width', $metadata);
        $this->assertArrayNotHasKey('height', $metadata);
    }
}