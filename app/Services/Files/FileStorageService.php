<?php

namespace App\Services\Files;

use App\Models\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    public function diskName(?string $disk = null): string
    {
        return $disk ?: 'local';
    }

    public function disk(?string $disk = null): FilesystemAdapter
    {
        return Storage::disk($this->diskName($disk));
    }

    public function fileDisk(File $file): FilesystemAdapter
    {
        return $this->disk($file->disk ?: 'local');
    }

    public function exists(File $file): bool
    {
        return $file->path !== null && $this->fileDisk($file)->exists($file->path);
    }

    public function get(File $file): string
    {
        return $this->fileDisk($file)->get((string) $file->path);
    }

    public function stream(File $file)
    {
        return $this->fileDisk($file)->readStream((string) $file->path);
    }

    public function download(File $file, ?string $name = null): StreamedResponse
    {
        return $this->fileDisk($file)->download((string) $file->path, $name ?: $file->stored_name);
    }

    public function delete(File $file): bool
    {
        return $this->exists($file) && $this->fileDisk($file)->delete((string) $file->path);
    }

    public function move(File $file, string $newPath): bool
    {
        $disk = $this->fileDisk($file);

        if (!$this->exists($file)) {
            return false;
        }

        return (string) $file->path === $newPath || $disk->move((string) $file->path, $newPath);
    }

    /**
     * Copia o conteúdo do arquivo para um arquivo temporário local.
     *
     * Necessário para operações que exigem caminho físico (ex.: ZipArchive::addFile)
     * quando o disco de origem pode ser remoto (Blob/S3). O chamador é responsável
     * por remover o arquivo retornado após o uso.
     */
    public function temporaryLocalCopy(File $file): ?string
    {
        if (!$this->exists($file)) {
            return null;
        }

        $temp  = tempnam(sys_get_temp_dir(), 'sicode-file-');
        $read  = $this->stream($file);
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

    public function matchesStoredChecksum(File $file, ?string $localPath = null): bool
    {
        $expected = trim((string) $file->sha256);

        if ($expected === '') {
            return true;
        }

        $path = $localPath;
        $removeTemp = false;

        if (!$path) {
            $path = $this->temporaryLocalCopy($file);
            $removeTemp = true;
        }

        if (!$path || !is_file($path)) {
            return false;
        }

        try {
            $actual = hash_file('sha256', $path);

            return is_string($actual) && hash_equals($expected, $actual);
        } finally {
            if ($removeTemp && is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function mimeType(File $file): string
    {
        return (string) ($file->mime ?: $this->fileDisk($file)->mimeType((string) $file->path) ?: 'application/octet-stream');
    }

    public function putUploadedAs(UploadedFile $upload, string $directory, string $name, ?string $disk = null): array
    {
        $diskName = $this->diskName($disk);
        $path     = trim($directory, '/') . '/' . $name;
        $stream   = fopen($upload->getRealPath(), 'rb');

        try {
            $this->disk($diskName)->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return [
            'disk'   => $diskName,
            'path'   => $path,
            'mime'   => $upload->getMimeType(),
            'size'   => $upload->getSize(),
            'sha256' => hash_file('sha256', $upload->getRealPath()),
        ];
    }
}
