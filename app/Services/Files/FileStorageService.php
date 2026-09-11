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
        return $this->disk($this->resolvedDiskName($file));
    }

    public function exists(File $file): bool
    {
        return $this->resolveLocation($file) !== null;
    }

    public function get(File $file): string
    {
        [$disk, $path] = $this->requireLocation($file);

        return $this->disk($disk)->get($path);
    }

    public function stream(File $file)
    {
        [$disk, $path] = $this->requireLocation($file);

        return $this->disk($disk)->readStream($path);
    }

    public function download(File $file, ?string $name = null): StreamedResponse
    {
        [$disk, $path] = $this->requireLocation($file);

        return $this->disk($disk)->download($path, $name ?: $file->stored_name);
    }

    public function delete(File $file): bool
    {
        $location = $this->resolveLocation($file);

        if ($location === null) {
            return false;
        }

        [$disk, $path] = $location;

        return $this->disk($disk)->delete($path);
    }

    public function move(File $file, string $newPath): bool
    {
        [$diskName, $path] = $this->requireLocation($file);
        $disk = $this->disk($diskName);

        return $path === $newPath || $disk->move($path, $newPath);
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
        if ($file->mime) {
            return (string) $file->mime;
        }

        [$disk, $path] = $this->requireLocation($file);

        return (string) ($this->disk($disk)->mimeType($path) ?: 'application/octet-stream');
    }

    public function size(File $file): int
    {
        $storedSize = $file->getRawOriginal('size') ?? null;

        if ($storedSize !== null) {
            return (int) $storedSize;
        }

        [$disk, $path] = $this->requireLocation($file);

        return (int) $this->disk($disk)->size($path);
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

    public function resolvedDiskName(File $file): string
    {
        return $this->resolveLocation($file)[0] ?? $this->diskName($file->disk ?: null);
    }

    public function resolvedPath(File $file): ?string
    {
        return $this->resolveLocation($file)[1] ?? null;
    }

    private function requireLocation(File $file): array
    {
        $location = $this->resolveLocation($file);

        if ($location === null) {
            throw new \RuntimeException('Arquivo não encontrado no storage.');
        }

        return $location;
    }

    private function resolveLocation(File $file): ?array
    {
        foreach ($this->locationCandidates($file) as [$disk, $path]) {
            if ($path !== '' && $this->disk($disk)->exists($path)) {
                return [$disk, $path];
            }
        }

        return null;
    }

    private function locationCandidates(File $file): array
    {
        $rawPath = ltrim((string) $file->path, '/');

        if ($rawPath === '') {
            return [];
        }

        $paths = array_values(array_unique(array_filter([
            $rawPath,
            str_starts_with($rawPath, 'storage/') ? substr($rawPath, strlen('storage/')) : null,
            str_starts_with($rawPath, 'public/') ? substr($rawPath, strlen('public/')) : null,
        ])));

        $disks = array_values(array_unique(array_filter([
            $file->disk ?: null,
            'local',
            'public',
        ])));

        $candidates = [];

        foreach ($disks as $disk) {
            foreach ($paths as $path) {
                $candidates[] = [$disk, $path];
            }
        }

        return $candidates;
    }
}
