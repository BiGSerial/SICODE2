<?php

namespace App\Services\Files;

use App\Models\File;
use App\Models\Files\FileDerivative;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileThumbnailService
{
    public const KIND = 'thumbnail';

    public function __construct(private readonly FileStorageService $storage)
    {
    }

    public function supportsWebp(): bool
    {
        return function_exists('imagewebp') && (bool) (gd_info()['WebP Support'] ?? false);
    }

    public function canThumbnail(File $file): bool
    {
        $extension = strtolower((string) ($file->ext ?: pathinfo((string) $file->file_name, PATHINFO_EXTENSION)));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'], true);
    }

    public function ensure(File $file, int $maxWidth = 480, int $maxHeight = 480): ?FileDerivative
    {
        if (!$this->supportsWebp() || !$this->canThumbnail($file) || !$this->storage->exists($file)) {
            return $file->thumbnail;
        }

        if ($file->thumbnail && $this->storage->disk($file->thumbnail->disk)->exists($file->thumbnail->path)) {
            return $file->thumbnail;
        }

        $source = null;
        $target = null;

        try {
            $source           = $this->copyToTemp($file);
            [$width, $height] = getimagesize($source) ?: [0, 0];

            if ($width <= 0 || $height <= 0) {
                return null;
            }

            $image = $this->createImage($source, strtolower((string) $file->ext));

            if (!$image) {
                return null;
            }

            [$thumbWidth, $thumbHeight] = $this->fit($width, $height, $maxWidth, $maxHeight);
            $thumb                      = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            $target = tempnam(sys_get_temp_dir(), 'sicode-thumb-');
            imagewebp($thumb, $target, 82);
            imagedestroy($image);
            imagedestroy($thumb);

            $disk   = $file->disk ?: 'local';
            $path   = 'thumbnails/files/' . $file->id . '/' . Str::slug(pathinfo((string) $file->file_name, PATHINFO_FILENAME) ?: 'arquivo') . '.webp';
            $stream = fopen($target, 'rb');

            try {
                $this->storage->disk($disk)->put($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return FileDerivative::updateOrCreate(
                ['file_id' => $file->id, 'kind' => self::KIND],
                [
                    'disk'   => $disk,
                    'path'   => $path,
                    'mime'   => 'image/webp',
                    'size'   => filesize($target) ?: null,
                    'sha256' => hash_file('sha256', $target),
                    'width'  => $thumbWidth,
                    'height' => $thumbHeight,
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('Falha ao gerar thumbnail do arquivo.', [
                'file_id' => $file->id,
                'message' => $exception->getMessage(),
            ]);

            return $file->thumbnail;
        } finally {
            if ($source && is_file($source)) {
                @unlink($source);
            }

            if ($target && is_file($target)) {
                @unlink($target);
            }
        }
    }

    private function copyToTemp(File $file): string
    {
        $temp  = tempnam(sys_get_temp_dir(), 'sicode-file-');
        $read  = $this->storage->stream($file);
        $write = fopen($temp, 'wb');

        try {
            stream_copy_to_stream($read, $write);
        } finally {
            if (is_resource($read)) {
                fclose($read);
            }

            if (is_resource($write)) {
                fclose($write);
            }
        }

        return $temp;
    }

    private function createImage(string $path, string $extension)
    {
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png'   => @imagecreatefrompng($path),
            'gif'   => @imagecreatefromgif($path),
            'bmp'   => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            'webp'  => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function fit(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        $scale = min(1, $maxWidth / $width, $maxHeight / $height);

        return [
            max(1, (int) floor($width * $scale)),
            max(1, (int) floor($height * $scale)),
        ];
    }
}
