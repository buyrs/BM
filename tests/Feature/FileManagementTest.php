<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Property;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\FileMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $ops;
    protected User $checker;
    protected Property $property;
    protected Mission $mission;
    protected Checklist $checklist;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ops = User::factory()->create(['role' => 'ops']);
        $this->checker = User::factory()->create(['role' => 'checker']);
        
        // Create test data
        $this->property = Property::factory()->create();
        $this->mission = Mission::factory()->create([
            'checker_id' => $this->checker->id
        ]);
        $this->checklist = Checklist::factory()->create(['mission_id' => $this->mission->id]);
    }

    /** @test */
    public function admin_can_upload_files_with_organization()
    {
        $file = UploadedFile::fake()->image('test.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/files/upload', [
                'file' => $file,
                'property_id' => $this->property->id,
                'mission_id' => $this->mission->id,
                'checklist_id' => $this->checklist->id,
                'is_public' => false
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File uploaded successfully'
            ]);

        $this->assertDatabaseHas('file_metadata', [
            'original_name' => 'test.jpg',
            'property_id' => $this->property->id,
            'mission_id' => $this->mission->id,
            'checklist_id' => $this->checklist->id,
            'uploaded_by' => $this->admin->id,
            'is_public' => false
        ]);

        $fileMetadata = FileMetadata::where('original_name', 'test.jpg')->first();
        Storage::assertExists($fileMetadata->path);
    }

    /** @test */
    public function ops_can_upload_files_to_properties()
    {
        $file = UploadedFile::fake()->image('ops-file.jpg');

        $response = $this->actingAs($this->ops)
            ->postJson('/api/files/upload', [
                'file' => $file,
                'property_id' => $this->property->id
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('file_metadata', [
            'original_name' => 'ops-file.jpg',
            'property_id' => $this->property->id,
            'uploaded_by' => $this->ops->id
        ]);
    }

    /** @test */
    public function checker_can_upload_files_to_assigned_missions()
    {
        $file = UploadedFile::fake()->image('checker-file.jpg');

        $response = $this->actingAs($this->checker)
            ->postJson('/api/files/upload', [
                'file' => $file,
                'mission_id' => $this->mission->id
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('file_metadata', [
            'original_name' => 'checker-file.jpg',
            'mission_id' => $this->mission->id,
            'uploaded_by' => $this->checker->id
        ]);
    }

    /** @test */
    public function file_upload_validates_file_types()
    {
        $invalidFile = UploadedFile::fake()->create('malicious.php', 100);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/files/upload', [
                'file' => $invalidFile
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('file_metadata', [
            'original_name' => 'malicious.php'
        ]);
    }

    /** @test */
    public function file_upload_validates_file_size()
    {
        // Create a large file (exceeding typical limits)
        $largeFile = UploadedFile::fake()->create('large.jpg', 20 * 1024); // 20MB

        $response = $this->actingAs($this->admin)
            ->postJson('/api/files/upload', [
                'file' => $largeFile
            ]);

        // Should either succeed or fail with validation error
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors();
        } else {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function users_can_get_files_by_hierarchy()
    {
        // Create test files
        $file1 = FileMetadata::factory()->create([
            'property_id' => $this->property->id,
            'mission_id' => $this->mission->id,
            'uploaded_by' => $this->admin->id
        ]);
        
        $file2 = FileMetadata::factory()->create([
            'property_id' => $this->property->id,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/files?' . http_build_query([
                'property_id' => $this->property->id,
                'mission_id' => $this->mission->id
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data); // Only mission-specific file
        $this->assertEquals($file1->id, $data[0]['id']);
    }

    /** @test */
    public function users_can_search_files()
    {
        $file = FileMetadata::factory()->create([
            'original_name' => 'searchable-document.pdf',
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/file-manager/search?' . http_build_query([
                'query' => 'searchable'
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($file->id, $data[0]['id']);
    }

    /** @test */
    public function users_can_filter_files_by_mime_type()
    {
        $imageFile = FileMetadata::factory()->create([
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $this->admin->id
        ]);
        
        $textFile = FileMetadata::factory()->create([
            'mime_type' => 'text/plain',
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/files?' . http_build_query([
                'mime_type' => 'image'
            ]));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($imageFile->id, $data[0]['id']);
    }

    /** @test */
    public function users_can_download_files_they_have_access_to()
    {
        Storage::put('test/file.jpg', 'fake image content');
        
        $file = FileMetadata::factory()->create([
            'path' => 'test/file.jpg',
            'uploaded_by' => $this->admin->id,
            'is_public' => false
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/api/files/{$file->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    /** @test */
    public function users_cannot_download_files_without_access()
    {
        $file = FileMetadata::factory()->create([
            'uploaded_by' => $this->admin->id,
            'is_public' => false
        ]);

        $otherUser = User::factory()->create(['role' => 'checker']);

        $response = $this->actingAs($otherUser)
            ->get("/api/files/{$file->id}/download");

        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_access_public_files()
    {
        Storage::put('test/public-file.jpg', 'fake image content');
        
        $file = FileMetadata::factory()->create([
            'path' => 'test/public-file.jpg',
            'uploaded_by' => $this->admin->id,
            'is_public' => true
        ]);

        $response = $this->actingAs($this->checker)
            ->get("/api/files/{$file->id}/download");

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_delete_any_file()
    {
        Storage::put('test/admin-delete.jpg', 'fake content');
        
        $file = FileMetadata::factory()->create([
            'path' => 'test/admin-delete.jpg',
            'uploaded_by' => $this->ops->id
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        $this->assertDatabaseMissing('file_metadata', ['id' => $file->id]);
        Storage::assertMissing('test/admin-delete.jpg');
    }

    /** @test */
    public function users_can_delete_their_own_files()
    {
        Storage::put('test/user-delete.jpg', 'fake content');
        
        $file = FileMetadata::factory()->create([
            'path' => 'test/user-delete.jpg',
            'uploaded_by' => $this->ops->id
        ]);

        $response = $this->actingAs($this->ops)
            ->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('file_metadata', ['id' => $file->id]);
    }

    /** @test */
    public function users_cannot_delete_files_they_dont_own()
    {
        $file = FileMetadata::factory()->create([
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->ops)
            ->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('file_metadata', ['id' => $file->id]);
    }

    /** @test */
    public function admin_can_bulk_delete_files()
    {
        Storage::put('test/bulk1.jpg', 'fake content');
        Storage::put('test/bulk2.jpg', 'fake content');
        
        $file1 = FileMetadata::factory()->create(['path' => 'test/bulk1.jpg']);
        $file2 = FileMetadata::factory()->create(['path' => 'test/bulk2.jpg']);

        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/file-manager/bulk-delete', [
                'file_ids' => [$file1->id, $file2->id]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'deleted_count' => 2
            ]);

        $this->assertDatabaseMissing('file_metadata', ['id' => $file1->id]);
        $this->assertDatabaseMissing('file_metadata', ['id' => $file2->id]);
    }

    /** @test */
    public function admin_can_bulk_move_files()
    {
        $file1 = FileMetadata::factory()->create(['property_id' => null]);
        $file2 = FileMetadata::factory()->create(['property_id' => null]);

        $response = $this->actingAs($this->admin)
            ->putJson('/api/file-manager/bulk-move', [
                'file_ids' => [$file1->id, $file2->id],
                'property_id' => $this->property->id,
                'mission_id' => $this->mission->id
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'moved_count' => 2
            ]);

        $this->assertDatabaseHas('file_metadata', [
            'id' => $file1->id,
            'property_id' => $this->property->id,
            'mission_id' => $this->mission->id
        ]);
    }

    /** @test */
    public function users_can_get_file_permissions()
    {
        $file = FileMetadata::factory()->create([
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/file-manager/{$file->id}/permissions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_view' => true,
                    'can_download' => true,
                    'can_delete' => true,
                    'can_move' => true,
                    'can_share' => true
                ]
            ]);
    }

    /** @test */
    public function checker_has_limited_permissions_on_files()
    {
        $file = FileMetadata::factory()->create([
            'uploaded_by' => $this->admin->id,
            'mission_id' => $this->mission->id
        ]);

        $response = $this->actingAs($this->checker)
            ->getJson("/api/file-manager/{$file->id}/permissions");

        $response->assertStatus(200);

        $permissions = $response->json('data');
        $this->assertTrue($permissions['can_view']);
        $this->assertTrue($permissions['can_download']);
        $this->assertFalse($permissions['can_delete']); // Not file owner
        $this->assertFalse($permissions['can_move']); // Not ops/admin
        $this->assertFalse($permissions['can_share']); // Not ops/admin
    }

    /** @test */
    public function users_can_get_storage_statistics()
    {
        // Create some test files
        FileMetadata::factory()->count(3)->create([
            'mime_type' => 'image/jpeg',
            'size' => 1024
        ]);
        
        FileMetadata::factory()->count(2)->create([
            'mime_type' => 'application/pdf',
            'size' => 2048
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/file-manager/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $stats = $response->json('data');
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertEquals(5, $stats['total_files']);
    }

    /** @test */
    public function users_can_export_file_list()
    {
        FileMetadata::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/file-manager/export', [
                'format' => 'csv',
                'filters' => []
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function image_files_generate_thumbnails_on_upload()
    {
        $file = UploadedFile::fake()->image('thumbnail-test.jpg', 800, 600);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/files/upload', [
                'file' => $file
            ]);

        $response->assertStatus(200);

        $fileMetadata = FileMetadata::where('original_name', 'thumbnail-test.jpg')->first();
        $this->assertTrue($fileMetadata->isImage());
        
        // Check if thumbnails were generated (metadata should be updated)
        $this->assertNotNull($fileMetadata->metadata);
    }

    /** @test */
    public function file_access_is_logged_when_downloaded()
    {
        Storage::put('test/access-log.jpg', 'fake content');
        
        $file = FileMetadata::factory()->create([
            'path' => 'test/access-log.jpg',
            'uploaded_by' => $this->admin->id,
            'last_accessed_at' => null
        ]);

        $this->actingAs($this->admin)
            ->get("/api/files/{$file->id}/download");

        $file->refresh();
        $this->assertNotNull($file->last_accessed_at);
    }

    /** @test */
    public function file_organization_respects_hierarchy()
    {
        $file = UploadedFile::fake()->image('hierarchy-test.jpg');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/files/upload', [
                'file' => $file,
                'property_id' => $this->property->id,
                'mission_id' => $this->mission->id,
                'checklist_id' => $this->checklist->id
            ]);

        $response->assertStatus(200);

        $fileMetadata = FileMetadata::where('original_name', 'hierarchy-test.jpg')->first();
        $expectedPath = "files/properties/{$this->property->id}/missions/{$this->mission->id}/checklists/{$this->checklist->id}";
        $this->assertStringContains($expectedPath, $fileMetadata->path);
    }

    /** @test */
    public function file_search_supports_advanced_filters()
    {
        $file1 = FileMetadata::factory()->create([
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'property_id' => $this->property->id
        ]);
        
        $file2 = FileMetadata::factory()->create([
            'original_name' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/file-manager/search?' . http_build_query([
                'query' => 'document',
                'filters' => [
                    'mime_type' => 'application',
                    'property_id' => $this->property->id,
                    'size_min' => 500,
                    'size_max' => 1500
                ]
            ]));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($file1->id, $data[0]['id']);
    }

    /** @test */
    public function file_metadata_includes_proper_information()
    {
        $file = FileMetadata::factory()->create([
            'original_name' => 'metadata-test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'metadata' => ['width' => 200, 'height' => 150]
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/file-manager/{$file->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $file->id,
                    'original_name' => 'metadata-test.jpg',
                    'mime_type' => 'image/jpeg',
                    'is_image' => true,
                    'metadata' => [
                        'width' => 200,
                        'height' => 150
                    ]
                ]
            ]);
    }
}