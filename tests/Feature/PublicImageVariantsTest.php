<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\Work;
use App\Support\PublicImageVariants;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_save_keeps_single_stored_file_for_legacy_original_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('works/sample.png', $this->pngImage(24, 24));

        Work::query()->create([
            'title' => 'Работа',
            'image' => 'works/sample.png',
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->assertTrue(Storage::disk('public')->exists('works/sample.png'));
        $this->assertFalse(Storage::disk('public')->exists('works/sample.webp'));
    }

    public function test_work_delete_removes_legacy_original_and_webp_variant(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('works/sample.png', $this->pngImage(24, 24));
        Storage::disk('public')->put('works/sample.webp', 'webp');

        $work = Work::query()->create([
            'title' => 'Работа',
            'image' => 'works/sample.png',
            'sort' => 1,
            'is_published' => true,
        ]);

        $work->delete();

        $this->assertFalse(Storage::disk('public')->exists('works/sample.png'));
        $this->assertFalse(Storage::disk('public')->exists('works/sample.webp'));
    }

    public function test_uploaded_image_is_stored_only_as_webp(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('sample.jpg', 240, 180);
        $path = PublicImageVariants::storeUploadedOptimized(
            $file,
            'works',
        );

        $expectedExtension = $this->expectedOptimizedExtension($file->getRealPath(), 'jpg');

        $this->assertStringEndsWith('.' . $expectedExtension, $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertCount(1, Storage::disk('public')->allFiles('works'));
    }

    public function test_uploaded_jpeg_keeps_jpeg_when_webp_is_larger_than_original_upload(): void
    {
        Storage::fake('public');

        $file = $this->jpegUploadedFile(1200, 900, 35);
        $path = PublicImageVariants::storeUploadedOptimized($file, 'works');

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertCount(1, Storage::disk('public')->allFiles('works'));
    }

    public function test_uploaded_png_is_saved_in_smaller_of_png_and_webp(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('sample.png', 240, 180);
        $path = PublicImageVariants::storeUploadedOptimized(
            $file,
            'works',
        );

        $expectedExtension = $this->expectedOptimizedExtension($file->getRealPath(), 'png');

        $this->assertStringEndsWith('.' . $expectedExtension, $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertCount(1, Storage::disk('public')->allFiles('works'));
    }

    public function test_uploaded_image_is_resized_to_maximum_dimension(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('large.jpg', 3200, 2400);
        $path = PublicImageVariants::storeUploadedOptimized($file, 'works');

        $imageSize = getimagesizefromstring((string) Storage::disk('public')->get($path));

        $this->assertSame(1600, max($imageSize[0], $imageSize[1]));
    }

    public function test_work_delete_removes_stored_webp_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('works/sample.webp', 'webp');

        $work = Work::query()->create([
            'title' => 'Работа',
            'image' => 'works/sample.webp',
            'sort' => 1,
            'is_published' => true,
        ]);

        $work->delete();

        $this->assertFalse(Storage::disk('public')->exists('works/sample.webp'));
    }

    public function test_product_image_delete_removes_stored_webp_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/sample.webp', 'webp');

        $category = Category::query()->create([
            'name' => 'Категория',
            'slug' => 'category',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Товар',
            'slug' => 'product',
            'is_active' => true,
        ]);

        $image = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/sample.webp',
            'sort' => 1,
            'is_main' => true,
        ]);

        $image->delete();

        $this->assertFalse(Storage::disk('public')->exists('products/sample.webp'));
    }

    public function test_resolve_media_path_prefers_webp_variant_when_present(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('works/sample.png', 'png');
        Storage::disk('public')->put('works/sample.webp', 'webp');

        $this->assertSame(
            asset('storage/works/sample.webp'),
            SiteSetting::resolveMediaPath('works/sample.png', 'images/back.webp')
        );
    }

    public function test_resolve_media_path_keeps_original_when_webp_variant_is_missing(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('works/sample.png', 'png');

        $this->assertSame(
            asset('storage/works/sample.png'),
            SiteSetting::resolveMediaPath('works/sample.png', 'images/back.webp')
        );
    }

    private function pngImage(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 249, 115, 22);

        imagefill($image, 0, 0, $background);

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();

        imagedestroy($image);

        return $contents;
    }

    private function expectedOptimizedExtension(string $sourcePath, string $originalExtension): string
    {
        $sourceContents = (string) file_get_contents($sourcePath);
        $image = imagecreatefromstring($sourceContents);
        $normalized = $this->resizeForTest($image);

        ob_start();
        imagewebp($normalized, null, 82);
        $webpContents = (string) ob_get_clean();

        ob_start();
        match ($originalExtension) {
            'jpg' => imagejpeg($normalized, null, 82),
            'png' => imagepng($normalized),
            default => false,
        };
        $reencodedOriginalContents = (string) ob_get_clean();

        if ($normalized !== $image) {
            imagedestroy($normalized);
        }

        imagedestroy($image);

        $originalContents = $normalized === $image
            ? $sourceContents
            : $reencodedOriginalContents;

        return strlen($webpContents) < strlen($originalContents) ? 'webp' : $originalExtension;
    }

    private function jpegUploadedFile(int $width, int $height, int $quality): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorallocate(
                    $image,
                    ($x * 7 + $y * 3) % 256,
                    ($x * 11 + $y * 5) % 256,
                    ($x * 13 + $y * 17) % 256,
                );

                imagesetpixel($image, $x, $y, $color);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'jpeg-test-');

        ob_start();
        imagejpeg($image, null, $quality);
        $contents = (string) ob_get_clean();

        file_put_contents($path, $contents);
        imagedestroy($image);

        return new UploadedFile($path, 'optimized-source.jpg', 'image/jpeg', null, true);
    }

    private function resizeForTest(mixed $image): mixed
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $largestSide = max($width, $height);

        if ($largestSide <= 1600) {
            return $image;
        }

        $scale = 1600 / $largestSide;
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $resized;
    }
}
