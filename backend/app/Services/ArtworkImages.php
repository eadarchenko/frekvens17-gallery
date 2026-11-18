<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArtworkImages
{
    public const MAX_EDGE = 960;

    public function store(UploadedFile $file): string
    {
        abort_unless(extension_loaded('gd') && function_exists('imagewebp'), 503, 'GD with WebP support is required. No original will be published.');
        $size = @getimagesize($file->getRealPath());
        if (! $size || $size[0] * $size[1] > 16000000) {
            throw ValidationException::withMessages(['image' => 'Use an image with at most 16 million pixels.']);
        }
        $memory = trim((string) ini_get('memory_limit'));
        $unit = strtolower(substr($memory, -1));
        $limit = (int) $memory * match ($unit) {
            'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1
        };
        if ($limit > 0 && memory_get_usage(true) + $size[0] * $size[1] * 8 + 16777216 > $limit) {
            throw ValidationException::withMessages(['image' => 'This server cannot safely process an image this large. Use a smaller file.']);
        }
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'This image could not be decoded.']);
        }
        try {
            // Apply camera orientation before re-encoding. No source EXIF/ICC/comments are copied.
            if ($size[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getRealPath());
                $orientation = (int) ($exif['Orientation'] ?? 1);
                if (in_array($orientation, [2, 4, 5, 7])) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                $angle = match ($orientation) {
                    3, 4 => 180, 5, 6 => -90, 7, 8 => 90, default => 0
                };
                if ($angle) {
                    $rotated = imagerotate($image, $angle, 0);
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
            $w = imagesx($image);
            $h = imagesy($image);
            $ratio = min(1, self::MAX_EDGE / max($w, $h));
            $copy = imagecreatetruecolor(max(1, (int) round($w * $ratio)), max(1, (int) round($h * $ratio)));
            imagealphablending($copy, false);
            imagesavealpha($copy, true);
            imagecopyresampled($copy, $image, 0, 0, 0, 0, imagesx($copy), imagesy($copy), $w, $h);
            ob_start();
            try {
                $ok = imagewebp($copy, null, 82);
                $web = ob_get_contents();
            } finally {
                ob_end_clean();
                imagedestroy($copy);
            }
            if (! $ok || ! $web) {
                throw new \RuntimeException('Could not generate web image.');
            }
            $id = (string) Str::uuid();
            // Originals and public derivatives are different disks. Neither has a public symlink.
            Storage::disk('originals')->put($id.'.'.$file->extension(), file_get_contents($file->getRealPath()));
            Storage::disk('web_images')->put($id.'.webp', $web);

            return '/media/products/'.$id.'.webp';
        } finally {
            imagedestroy($image);
        }
    }
}
