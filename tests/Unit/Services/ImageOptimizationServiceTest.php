<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImageOptimizationService;
use App\Models\FileMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class ImageOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageOptimizationService $imageOptimizationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        $this->imageOptimizationService = new ImageOptimizationService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_optimize_image_files()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3000, 2000); // Large image

        $optimizedPath = $this->imageOptimizationService->optimizeImage($file);

        $this->assertFileExists($optimizedPath);
        
        // Check that the optimized image is smaller than the original
        $originalSize = $file->getSize();
        $optimizedSize = filesize($optimizedPath);
        
        // Clean up
        unlink($optimizedPath);
        
        $this->assertIsString($optimizedPath);
    }

    /** @test */
    public function it_throws_exception_for_non_image_files()
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not an image');

        $this->imageOptimizationService->optimizeImage($file);
    }

    /** @test */
    public function it_can_generate_thumbnails_for_image_file_metadata()
    {
        // Create a real image file in storage
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $path = $file->store('test');
        
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.jpg',
            'original_name' => 'test.jpg',
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $thumbnails = $this->imageOptimizationService->generateThumbnails($fileMetadata);

        $this->assertIsArray($thumbnails);
        $this->assertArrayHasKey('small', $thumbnails);
        $this->assertArrayHasKey('medium', $thumbnails);
        $this->assertArrayHasKey('large', $thumbnails);

        foreach ($thumbnails as $size => $thumbnail) {
            $this->assertArrayHasKey('path', $thumbnail);
            $this->assertArrayHasKey('width', $thumbnail);
            $this->assertArrayHasKey('height', $thumbnail);
            $this->assertArrayHasKey('url', $thumbnail);
        }

        // Check that metadata was updated
        $fileMetadata->refresh();
        $this->assertArrayHasKey('thumbnails', $fileMetadata->metadata);
    }

    /** @test */
    public function it_returns_empty_array_for_non_image_file_metadata()
    {
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.txt',
            'original_name' => 'test.txt',
            'path' => 'test/test.txt',
            'size' => 100,
            'mime_type' => 'text/plain',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $thumbnails = $this->imageOptimizationService->generateThumbnails($fileMetadata);

        $this->assertIsArray($thumbnails);
        $this->assertEmpty($thumbnails);
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_file()
    {
        $fileMetadata = FileMetadata::create([
            'filename' => 'nonexistent.jpg',
            'original_name' => 'nonexistent.jpg',
            'path' => 'test/nonexistent.jpg',
            'size' => 100,
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $thumbnails = $this->imageOptimizationService->generateThumbnails($fileMetadata);

        $this->assertIsArray($thumbnails);
        $this->assertEmpty($thumbnails);
    }

    /** @test */
    public function it_can_extract_image_metadata()
    {
        $file = UploadedFile::fake()->image('test.jpg', 400, 300);
        $tempPath = $file->getRealPath();

        $metadata = $this->imageOptimizationService->extractImageMetadata($tempPath);

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('width', $metadata);
        $this->assertArrayHasKey('height', $metadata);
        $this->assertArrayHasKey('aspect_ratio', $metadata);
        $this->assertEquals(400, $metadata['width']);
        $this->assertEquals(300, $metadata['height']);
        $this->assertEquals(1.33, $metadata['aspect_ratio']);
    }

    /** @test */
    public function it_handles_metadata_extraction_errors_gracefully()
    {
        $invalidPath = '/path/to/nonexistent/file.jpg';

        $metadata = $this->imageOptimizationService->extractImageMetadata($invalidPath);

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('error', $metadata);
        $this->assertStringContainsString('Failed to extract metadata', $metadata['error']);
    }

    /** @test */
    public function it_can_convert_image_format()
    {
        // Create a real image file in storage
        $file = UploadedFile::fake()->image('test.jpg', 200, 200);
        $path = $file->store('test');
        
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.jpg',
            'original_name' => 'test.jpg',
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $convertedFileMetadata = $this->imageOptimizationService->convertFormat($fileMetadata, 'png');

        $this->assertInstanceOf(FileMetadata::class, $convertedFileMetadata);
        $this->assertStringEndsWith('_converted.png', $convertedFileMetadata->filename);
        $this->assertEquals('image/png', $convertedFileMetadata->mime_type);
        $this->assertNotEquals($fileMetadata->id, $convertedFileMetadata->id);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_format_conversion()
    {
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.jpg',
            'original_name' => 'test.jpg',
            'path' => 'test/test.jpg',
            'size' => 100,
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported target format');

        $this->imageOptimizationService->convertFormat($fileMetadata, 'bmp');
    }

    /** @test */
    public function it_returns_null_for_non_image_format_conversion()
    {
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.txt',
            'original_name' => 'test.txt',
            'path' => 'test/test.txt',
            'size' => 100,
            'mime_type' => 'text/plain',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $result = $this->imageOptimizationService->convertFormat($fileMetadata, 'png');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_batch_optimize_images()
    {
        // Create multiple image files in storage
        $file1 = UploadedFile::fake()->image('test1.jpg', 200, 200);
        $file2 = UploadedFile::fake()->image('test2.jpg', 300, 300);
        $path1 = $file1->store('test');
        $path2 = $file2->store('test');
        
        $fileMetadata1 = FileMetadata::create([
            'filename' => 'test1.jpg',
            'original_name' => 'test1.jpg',
            'path' => $path1,
            'size' => $file1->getSize(),
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash_1',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $fileMetadata2 = FileMetadata::create([
            'filename' => 'test2.jpg',
            'original_name' => 'test2.jpg',
            'path' => $path2,
            'size' => $file2->getSize(),
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash_2',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $results = $this->imageOptimizationService->batchOptimizeImages([
            $fileMetadata1->id,
            $fileMetadata2->id
        ]);

        $this->assertIsArray($results);
        $this->assertArrayHasKey($fileMetadata1->id, $results);
        $this->assertArrayHasKey($fileMetadata2->id, $results);
        $this->assertTrue($results[$fileMetadata1->id]['success']);
        $this->assertTrue($results[$fileMetadata2->id]['success']);
    }

    /** @test */
    public function it_handles_batch_optimization_failures()
    {
        $nonExistentId = 999;
        $results = $this->imageOptimizationService->batchOptimizeImages([$nonExistentId]);

        $this->assertIsArray($results);
        $this->assertArrayHasKey($nonExistentId, $results);
        $this->assertFalse($results[$nonExistentId]['success']);
        $this->assertStringContainsString('File not found', $results[$nonExistentId]['message']);
    }

    /** @test */
    public function it_can_cleanup_thumbnails()
    {
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.jpg',
            'original_name' => 'test.jpg',
            'path' => 'test/test.jpg',
            'size' => 100,
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
            'metadata' => [
                'thumbnails' => [
                    'small' => ['path' => 'test/thumbnails/small.jpg'],
                    'medium' => ['path' => 'test/thumbnails/medium.jpg']
                ]
            ]
        ]);

        // Create fake thumbnail files
        Storage::put('test/thumbnails/small.jpg', 'fake content');
        Storage::put('test/thumbnails/medium.jpg', 'fake content');

        $result = $this->imageOptimizationService->cleanupThumbnails($fileMetadata);

        $this->assertTrue($result);
        
        // Check that thumbnails were removed from metadata
        $fileMetadata->refresh();
        $this->assertArrayNotHasKey('thumbnails', $fileMetadata->metadata ?? []);
    }

    /** @test */
    public function it_returns_true_for_cleanup_when_no_thumbnails_exist()
    {
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.jpg',
            'original_name' => 'test.jpg',
            'path' => 'test/test.jpg',
            'size' => 100,
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $result = $this->imageOptimizationService->cleanupThumbnails($fileMetadata);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_extracts_dominant_colors_from_image()
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $tempPath = $file->getRealPath();

        $metadata = $this->imageOptimizationService->extractImageMetadata($tempPath);

        $this->assertArrayHasKey('colors', $metadata);
        $this->assertIsArray($metadata['colors']);
        
        if (!isset($metadata['colors']['error'])) {
            $this->assertArrayHasKey('dominant', $metadata['colors']);
            $this->assertArrayHasKey('palette', $metadata['colors']);
        }
    }

    /** @test */
    public function it_optimizes_large_images_by_resizing()
    {
        $file = UploadedFile::fake()->image('large.jpg', 4000, 3000); // Very large image

        $optimizedPath = $this->imageOptimizationService->optimizeImage($file);

        $this->assertFileExists($optimizedPath);
        
        // Check image dimensions were reduced
        $imageInfo = getimagesize($optimizedPath);
        $this->assertLessThanOrEqual(2048, $imageInfo[0]); // Width
        $this->assertLessThanOrEqual(2048, $imageInfo[1]); // Height
        
        // Clean up
        unlink($optimizedPath);
    }

    /** @test */
    public function it_maintains_aspect_ratio_when_resizing()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3000, 1500); // 2:1 aspect ratio

        $optimizedPath = $this->imageOptimizationService->optimizeImage($file);

        $this->assertFileExists($optimizedPath);
        
        $imageInfo = getimagesize($optimizedPath);
        $aspectRatio = $imageInfo[0] / $imageInfo[1];
        $this->assertEquals(2.0, $aspectRatio, '', 0.1); // Allow small tolerance
        
        // Clean up
        unlink($optimizedPath);
    }

    /** @test */
    public function it_applies_quality_compression()
    {
        $file = UploadedFile::fake()->image('test.jpg', 500, 500);

        $highQualityPath = $this->imageOptimizationService->optimizeImage($file, 95);
        $lowQualityPath = $this->imageOptimizationService->optimizeImage($file, 50);

        $this->assertFileExists($highQualityPath);
        $this->assertFileExists($lowQualityPath);
        
        $highQualitySize = filesize($highQualityPath);
        $lowQualitySize = filesize($lowQualityPath);
        
        // Lower quality should result in smaller file size
        $this->assertLessThan($highQualitySize, $lowQualitySize);
        
        // Clean up
        unlink($highQualityPath);
        unlink($lowQualityPath);
    }

    /** @test */
    public function it_handles_thumbnail_generation_with_proper_sizing()
    {
        // Create a real image file in storage
        $file = UploadedFile::fake()->image('test.jpg', 1000, 800);
        $path = $file->store('test');
        
        $fileMetadata = FileMetadata::create([
            'filename' => 'test.jpg',
            'original_name' => 'test.jpg',
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => 'image/jpeg',
            'file_hash' => 'test_hash',
            'storage_disk' => 'local',
            'uploaded_by' => 1,
        ]);

        $thumbnails = $this->imageOptimizationService->generateThumbnails($fileMetadata);

        // Check that thumbnails respect maximum dimensions
        $this->assertLessThanOrEqual(150, $thumbnails['small']['width']);
        $this->assertLessThanOrEqual(150, $thumbnails['small']['height']);
        $this->assertLessThanOrEqual(300, $thumbnails['medium']['width']);
        $this->assertLessThanOrEqual(300, $thumbnails['medium']['height']);
        $this->assertLessThanOrEqual(800, $thumbnails['large']['width']);
        $this->assertLessThanOrEqual(600, $thumbnails['large']['height']);
    }
}