<?php

namespace App\Services\Files;

use App\Models\EvidenceFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceThumbnailService
{
    public function __construct(private readonly EvidenceFileService $evidence)
    {
    }

    public function ensure(EvidenceFile $file, int $maxWidth = 480, int $maxHeight = 480): ?array
    {
        if (!function_exists('imagewebp') || !(gd_info()['WebP Support'] ?? false)) {
            return null;
        }

        $extension = strtolower((string) ($file->extension ?: pathinfo((string) $file->stored_name, PATHINFO_EXTENSION)));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'], true)) {
            return null;
        }

        $location = $this->evidence->location($file);
        if (!$location) {
            return null;
        }

        [$disk, $sourcePath] = $location;
        $path = 'thumbnails/evidence/' . $file->id . '/'
            . Str::slug(pathinfo((string) ($file->original_name ?: $file->stored_name), PATHINFO_FILENAME) ?: 'evidencia')
            . '.webp';

        $storage = Storage::disk($disk);
        if ($storage->exists($path)) {
            return ['disk' => $disk, 'path' => $path];
        }

        $source = @imagecreatefromstring($storage->get($sourcePath));
        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $thumbWidth = max(1, (int) floor($width * $scale));
        $thumbHeight = max(1, (int) floor($height * $scale));
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        $temporary = tempnam(sys_get_temp_dir(), 'sicode-evidence-thumb-');
        try {
            imagewebp($thumb, $temporary, 82);
            $stream = fopen($temporary, 'rb');
            try {
                $storage->put($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } finally {
            imagedestroy($source);
            imagedestroy($thumb);
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }

        return ['disk' => $disk, 'path' => $path];
    }
}
