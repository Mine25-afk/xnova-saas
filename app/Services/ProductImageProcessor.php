<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageProcessor
{
    public function store(UploadedFile $uploadedFile, int $xnovaId): string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('El servidor no soporta la conversion de imagenes a WebP.');
        }

        $contents = file_get_contents($uploadedFile->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('No fue posible leer la imagen subida.');
        }

        $sourceImage = @imagecreatefromstring($contents);

        if (! $sourceImage) {
            throw new RuntimeException('La imagen enviada no pudo procesarse.');
        }

        [$width, $height, $mime] = $this->imageMetadata($uploadedFile, $sourceImage);
        $sourceImage = $this->applyExifOrientation($uploadedFile, $sourceImage, $mime);

        $targetWidth = $width;
        $targetHeight = $height;
        $maxSize = 1000;

        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $canvas) {
            imagedestroy($sourceImage);
            throw new RuntimeException('No fue posible preparar el lienzo de la imagen.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled(
            $canvas,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($sourceImage),
            imagesy($sourceImage)
        );

        $disk = Storage::disk('public');
        $directory = 'productos/'.$xnovaId;
        $disk->makeDirectory($directory);

        $relativePath = $directory.'/'.Str::uuid().'.webp';
        $absolutePath = $disk->path($relativePath);
        $saved = imagewebp($canvas, $absolutePath, 82);

        imagedestroy($canvas);
        imagedestroy($sourceImage);

        if (! $saved) {
            throw new RuntimeException('No fue posible guardar la imagen convertida.');
        }

        return $relativePath;
    }

    public function delete(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    protected function imageMetadata(UploadedFile $uploadedFile, \GdImage $fallbackImage): array
    {
        $info = @getimagesize($uploadedFile->getRealPath());

        if (is_array($info)) {
            return [
                (int) ($info[0] ?? imagesx($fallbackImage)),
                (int) ($info[1] ?? imagesy($fallbackImage)),
                (string) ($info['mime'] ?? $uploadedFile->getMimeType()),
            ];
        }

        return [
            imagesx($fallbackImage),
            imagesy($fallbackImage),
            (string) $uploadedFile->getMimeType(),
        ];
    }

    protected function applyExifOrientation(UploadedFile $uploadedFile, \GdImage $image, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($uploadedFile->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated instanceof \GdImage) {
            if ($rotated !== $image) {
                imagedestroy($image);
            }

            return $rotated;
        }

        return $image;
    }
}
