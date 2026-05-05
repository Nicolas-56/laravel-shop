<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicImageVariants
{
    private const int MAX_DIMENSION = 1600;

    private const int WEBP_QUALITY = 82;

    private const int JPEG_QUALITY = 82;

    public static function ensureWebpVariant(?string $path, string $disk = 'public'): ?string
    {
        $normalizedPath = self::normalizeDiskRelativePath($path);

        if ($normalizedPath === null || self::shouldSkipVariantGeneration($normalizedPath)) {
            return null;
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($normalizedPath)) {
            return null;
        }

        $variantPath = self::webpVariantPath($normalizedPath);

        try {
            self::convertToWebp($storage->path($normalizedPath), $storage->path($variantPath));
        } catch (\Throwable) {
            return null;
        }

        return $variantPath;
    }

    public static function storeUploadedOptimized(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $directory = trim($directory, '/');
        $extension = self::normalizedExtension($file->getClientOriginalExtension());

        if (self::isSvg($extension)) {
            return self::storeUploadedOriginal($file, $directory, $disk, $extension);
        }

        $sourceContents = @file_get_contents($file->getRealPath());

        if ($sourceContents === false) {
            throw new \RuntimeException('Unable to read uploaded image.');
        }

        $image = self::imageFromPath($file->getRealPath());

        if ($image === null) {
            return self::storeUploadedOriginal($file, $directory, $disk, $extension);
        }

        try {
            $needsResize = self::needsResize($image);
            $normalized = $needsResize ? self::resizeIfNeeded($image) : $image;
            $originalContents = self::resolveOriginalContents(
                $normalized,
                $extension,
                $sourceContents,
                $needsResize,
            );

            if (self::isWebp($extension)) {
                if ($needsResize && $originalContents === null) {
                    return self::storeUploadedOriginal($file, $directory, $disk, $extension);
                }

                return self::storeContents($originalContents, $directory, $disk, $extension);
            }

            if ($originalContents === null) {
                return self::storeUploadedOriginal($file, $directory, $disk, $extension);
            }

            $webpContents = self::encodeImage($normalized, 'webp');

            if ($webpContents !== null && strlen($webpContents) < strlen($originalContents)) {
                return self::storeContents($webpContents, $directory, $disk, 'webp');
            }

            return self::storeContents($originalContents, $directory, $disk, $extension);
        } finally {
            if (isset($normalized) && $normalized !== $image) {
                imagedestroy($normalized);
            }

            imagedestroy($image);
        }
    }

    public static function deleteStoredImage(?string $path, string $disk = 'public'): void
    {
        $normalizedPath = self::normalizeDiskRelativePath($path);

        if ($normalizedPath === null) {
            return;
        }

        Storage::disk($disk)->delete($normalizedPath);

        if (! self::shouldSkipVariantGeneration($normalizedPath)) {
            Storage::disk($disk)->delete(self::webpVariantPath($normalizedPath));
        }
    }

    public static function deleteWebpVariant(?string $path, string $disk = 'public'): void
    {
        $normalizedPath = self::normalizeDiskRelativePath($path);

        if ($normalizedPath === null || self::shouldSkipVariantGeneration($normalizedPath)) {
            return;
        }

        Storage::disk($disk)->delete(self::webpVariantPath($normalizedPath));
    }

    public static function preferredPublicPath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (self::isWebp($normalizedPath) || self::isSvg($normalizedPath)) {
            return $normalizedPath;
        }

        if (Str::startsWith($normalizedPath, 'storage/')) {
            $diskPath = substr($normalizedPath, strlen('storage/'));
            $variantPath = self::webpVariantPath($diskPath);

            return Storage::disk('public')->exists($variantPath)
                ? 'storage/' . $variantPath
                : $normalizedPath;
        }

        if (self::isPublicFilesystemPath($normalizedPath)) {
            return is_file(public_path(self::webpVariantPath($normalizedPath)))
                ? self::webpVariantPath($normalizedPath)
                : $normalizedPath;
        }

        $variantPath = self::webpVariantPath($normalizedPath);

        return Storage::disk('public')->exists($variantPath)
            ? $variantPath
            : $normalizedPath;
    }

    private static function convertToWebp(string $sourcePath, string $targetPath): void
    {
        $webpContents = self::convertToWebpContents($sourcePath);

        if ($webpContents === null) {
            return;
        }

        $targetDirectory = dirname($targetPath);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        file_put_contents($targetPath, $webpContents);
    }

    private static function convertToWebpContents(string $sourcePath): ?string
    {
        $image = self::imageFromPath($sourcePath);

        if ($image === false) {
            return null;
        }

        try {
            $normalized = self::resizeIfNeeded($image);

            return self::encodeImage($normalized, 'webp');
        } finally {
            if (isset($normalized) && $normalized !== $image) {
                imagedestroy($normalized);
            }

            imagedestroy($image);
        }
    }

    private static function imageFromPath(string $sourcePath): mixed
    {
        $contents = @file_get_contents($sourcePath);

        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private static function resizeIfNeeded(mixed $image): mixed
    {
        if (! self::needsResize($image)) {
            return $image;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $largestSide = max($width, $height);

        $scale = self::MAX_DIMENSION / $largestSide;
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

    private static function needsResize(mixed $image): bool
    {
        return max(imagesx($image), imagesy($image)) > self::MAX_DIMENSION;
    }

    private static function resolveOriginalContents(
        mixed $image,
        string $extension,
        string $sourceContents,
        bool $needsResize,
    ): ?string {
        if (! $needsResize) {
            return $sourceContents;
        }

        return self::encodeImage($image, $extension);
    }

    private static function encodeImage(mixed $image, string $extension): ?string
    {
        ob_start();

        $written = match ($extension) {
            'jpg' => imagejpeg($image, null, self::JPEG_QUALITY),
            'png' => imagepng($image),
            'webp' => imagewebp($image, null, self::WEBP_QUALITY),
            default => false,
        };

        $contents = $written ? (string) ob_get_clean() : null;

        if (! $written) {
            ob_end_clean();
        }

        return $contents !== '' ? $contents : null;
    }

    private static function storeUploadedOriginal(UploadedFile $file, string $directory, string $disk, string $extension): string
    {
        $contents = @file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new \RuntimeException('Unable to read uploaded image.');
        }

        return self::storeContents($contents, $directory, $disk, $extension);
    }

    private static function storeContents(string $contents, string $directory, string $disk, string $extension): string
    {
        $relativePath = ltrim($directory . '/' . Str::ulid() . '.' . $extension, '/');

        Storage::disk($disk)->put($relativePath, $contents);

        return $relativePath;
    }

    private static function normalizedExtension(?string $extension): string
    {
        $extension = strtolower(trim((string) $extension));

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    private static function normalizeDiskRelativePath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (Str::startsWith($normalizedPath, 'storage/')) {
            return substr($normalizedPath, strlen('storage/'));
        }

        return self::isPublicFilesystemPath($normalizedPath) ? null : $normalizedPath;
    }

    private static function webpVariantPath(string $path): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $path) ?? $path . '.webp';
    }

    private static function shouldSkipVariantGeneration(string $path): bool
    {
        return self::isWebp($path) || self::isSvg($path);
    }

    private static function isWebp(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === '') {
            $extension = strtolower(trim($path));
        }

        return $extension === 'webp';
    }

    private static function isSvg(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === '') {
            $extension = strtolower(trim($path));
        }

        return $extension === 'svg';
    }

    private static function isPublicFilesystemPath(string $path): bool
    {
        return Str::startsWith($path, ['data/', 'images/']);
    }
}
