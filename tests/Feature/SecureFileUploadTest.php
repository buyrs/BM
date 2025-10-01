<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Property;
use App\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SecureFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $ops;
    protected User $checker;
    protected Property $property;
    protected Mission $mission;

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
    }

    /** @test */
    public function users_can_upload_secure_files()
    {
        $file = UploadedFile::fake()->image('secure-test.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images',
                'property_id' => $this->property->id,
                'description' => 'Test secure upload'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Files uploaded successfully.'
            ]);

        $uploadedFiles = $response->json('uploaded_files');
        $this->assertCount(1, $uploadedFiles);
        $this->assertEquals('secure-test.jpg', $uploadedFiles[0]['original_name']);
        $this->assertEquals('images', $uploadedFiles[0]['category']);
        $this->assertEquals($this->admin->id, $uploadedFiles[0]['uploaded_by']);
    }

    /** @test */
    public function secure_upload_validates_file_types()
    {
        $maliciousFile = UploadedFile::fake()->create('malicious.php', 100);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$maliciousFile],
                'category' => 'images'
            ]);

        $response->assertStatus(422);
        $this->assertEmpty($response->json('uploaded_files'));
        $this->assertNotEmpty($response->json('errors'));
    }

    /** @test */
    public function secure_upload_enforces_file_size_limits()
    {
        // Create a file that exceeds the image category limit (10MB)
        $largeFile = UploadedFile::fake()->create('large.jpg', 11 * 1024); // 11MB

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$largeFile],
                'category' => 'images'
            ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function secure_upload_generates_secure_filenames()
    {
        $file = UploadedFile::fake()->image('original filename with spaces.jpg');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $response->assertStatus(200);

        $uploadedFiles = $response->json('uploaded_files');
        $secureFilename = $uploadedFiles[0]['filename'];
        
        // Should not contain original filename
        $this->assertStringNotContainsString('original filename with spaces', $secureFilename);
        $this->assertStringEndsWith('.jpg', $secureFilename);
        $this->assertMatchesRegularExpression('/^images_[a-f0-9]+\.jpg$/', $secureFilename);
    }

    /** @test */
    public function secure_upload_organizes_files_by_hierarchy()
    {
        $file = UploadedFile::fake()->image('hierarchy-test.jpg');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images',
                'property_id' => $this->property->id,
                'mission_id' => $this->mission->id
            ]);

        $response->assertStatus(200);

        $uploadedFiles = $response->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];
        
        $this->assertStringContains('secure/images', $filePath);
        $this->assertStringContains("properties/{$this->property->id}", $filePath);
        $this->assertStringContains("missions/{$this->mission->id}", $filePath);
        $this->assertStringContains("users/{$this->admin->id}", $filePath);
        $this->assertStringContains(date('Y/m/d'), $filePath);
    }

    /** @test */
    public function users_can_download_secure_files_they_have_access_to()
    {
        // Upload a file first
        $file = UploadedFile::fake()->image('download-test.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Try to download the file
        $response = $this->actingAs($this->admin)
            ->get("/api/secure-files/download/{$filePath}");

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    /** @test */
    public function users_cannot_download_secure_files_without_access()
    {
        // Upload a file as admin
        $file = UploadedFile::fake()->image('restricted-test.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Try to download as different user
        $otherUser = User::factory()->create(['role' => 'checker']);
        
        $response = $this->actingAs($otherUser)
            ->get("/api/secure-files/download/{$filePath}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'You do not have permission to access this file.'
                ]
            ]);
    }

    /** @test */
    public function admin_can_access_all_secure_files()
    {
        // Upload a file as ops user
        $file = UploadedFile::fake()->image('ops-file.jpg');
        
        $uploadResponse = $this->actingAs($this->ops)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Admin should be able to download it
        $response = $this->actingAs($this->admin)
            ->get("/api/secure-files/download/{$filePath}");

        $response->assertStatus(200);
    }

    /** @test */
    public function ops_can_access_property_and_mission_files()
    {
        // Upload a file to a property
        $file = UploadedFile::fake()->image('property-file.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images',
                'property_id' => $this->property->id
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Ops user should be able to download it
        $response = $this->actingAs($this->ops)
            ->get("/api/secure-files/download/{$filePath}");

        $response->assertStatus(200);
    }

    /** @test */
    public function checker_can_access_mission_files()
    {
        // Upload a file to a mission
        $file = UploadedFile::fake()->image('mission-file.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images',
                'mission_id' => $this->mission->id
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Checker should be able to download it
        $response = $this->actingAs($this->checker)
            ->get("/api/secure-files/download/{$filePath}");

        $response->assertStatus(200);
    }

    /** @test */
    public function users_can_access_their_own_files()
    {
        // Upload a file as checker
        $file = UploadedFile::fake()->image('user-file.jpg');
        
        $uploadResponse = $this->actingAs($this->checker)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Same user should be able to download it
        $response = $this->actingAs($this->checker)
            ->get("/api/secure-files/download/{$filePath}");

        $response->assertStatus(200);
    }

    /** @test */
    public function users_can_get_secure_file_information()
    {
        // Upload a file first
        $file = UploadedFile::fake()->image('info-test.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Get file info
        $response = $this->actingAs($this->admin)
            ->get("/api/secure-files/info/{$filePath}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $fileInfo = $response->json('file_info');
        $this->assertArrayHasKey('path', $fileInfo);
        $this->assertArrayHasKey('size', $fileInfo);
        $this->assertArrayHasKey('mime_type', $fileInfo);
        $this->assertEquals($filePath, $fileInfo['path']);
    }

    /** @test */
    public function users_can_delete_secure_files_they_have_access_to()
    {
        // Upload a file first
        $file = UploadedFile::fake()->image('delete-test.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Delete the file
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/secure-files/delete/{$filePath}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File deleted successfully.'
            ]);

        Storage::assertMissing($filePath);
    }

    /** @test */
    public function users_cannot_delete_secure_files_without_access()
    {
        // Upload a file as admin
        $file = UploadedFile::fake()->image('no-delete-test.jpg');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $uploadedFiles = $uploadResponse->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];

        // Try to delete as different user
        $otherUser = User::factory()->create(['role' => 'checker']);
        
        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/secure-files/delete/{$filePath}");

        $response->assertStatus(403);
        Storage::assertExists($filePath);
    }

    /** @test */
    public function users_can_list_secure_files_they_have_access_to()
    {
        // Upload files in different categories
        $imageFile = UploadedFile::fake()->image('list-image.jpg');
        $documentFile = UploadedFile::fake()->create('list-document.pdf', 100, 'application/pdf');
        
        $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$imageFile],
                'category' => 'images'
            ]);

        $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$documentFile],
                'category' => 'documents'
            ]);

        // List all files
        $response = $this->actingAs($this->admin)
            ->get('/api/secure-files/list');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $files = $response->json('files');
        $this->assertCount(2, $files);
    }

    /** @test */
    public function users_can_filter_secure_file_list_by_category()
    {
        // Upload files in different categories
        $imageFile = UploadedFile::fake()->image('filter-image.jpg');
        $documentFile = UploadedFile::fake()->create('filter-document.pdf', 100, 'application/pdf');
        
        $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$imageFile],
                'category' => 'images'
            ]);

        $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$documentFile],
                'category' => 'documents'
            ]);

        // List only image files
        $response = $this->actingAs($this->admin)
            ->get('/api/secure-files/list?category=images');

        $response->assertStatus(200);

        $files = $response->json('files');
        $this->assertCount(1, $files);
        $this->assertStringContains('filter-image.jpg', $files[0]['name']);
    }

    /** @test */
    public function users_can_get_allowed_file_types_for_categories()
    {
        $response = $this->actingAs($this->admin)
            ->get('/api/secure-files/allowed-types/images');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'category' => 'images'
            ]);

        $allowedTypes = $response->json('allowed_types');
        $this->assertContains('image/jpeg', $allowedTypes);
        $this->assertContains('image/png', $allowedTypes);

        $categories = $response->json('available_categories');
        $this->assertContains('images', $categories);
        $this->assertContains('documents', $categories);
    }

    /** @test */
    public function secure_upload_handles_multiple_files()
    {
        $file1 = UploadedFile::fake()->image('multi1.jpg');
        $file2 = UploadedFile::fake()->image('multi2.jpg');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file1, $file2],
                'category' => 'images'
            ]);

        $response->assertStatus(200);

        $uploadedFiles = $response->json('uploaded_files');
        $this->assertCount(2, $uploadedFiles);
        $this->assertEquals('multi1.jpg', $uploadedFiles[0]['original_name']);
        $this->assertEquals('multi2.jpg', $uploadedFiles[1]['original_name']);
    }

    /** @test */
    public function secure_upload_handles_partial_failures()
    {
        $validFile = UploadedFile::fake()->image('valid.jpg');
        $invalidFile = UploadedFile::fake()->create('invalid.php', 100);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$validFile, $invalidFile],
                'category' => 'images'
            ]);

        $response->assertStatus(422);

        $uploadedFiles = $response->json('uploaded_files');
        $errors = $response->json('errors');
        
        $this->assertCount(1, $uploadedFiles); // Only valid file uploaded
        $this->assertCount(1, $errors); // One error for invalid file
        $this->assertEquals('valid.jpg', $uploadedFiles[0]['original_name']);
        $this->assertEquals('invalid.php', $errors[0]['file']);
    }

    /** @test */
    public function secure_file_paths_include_date_organization()
    {
        $file = UploadedFile::fake()->image('date-test.jpg');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/secure-files/upload', [
                'files' => [$file],
                'category' => 'images'
            ]);

        $response->assertStatus(200);

        $uploadedFiles = $response->json('uploaded_files');
        $filePath = $uploadedFiles[0]['path'];
        
        $this->assertStringContains(date('Y/m/d'), $filePath);
    }

    /** @test */
    public function file_not_found_returns_proper_error()
    {
        $response = $this->actingAs($this->admin)
            ->get('/api/secure-files/download/nonexistent/path/file.jpg');

        $response->assertStatus(404)
            ->assertJson([
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'The requested file was not found.'
                ]
            ]);
    }
}