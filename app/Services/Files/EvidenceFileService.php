<?php

namespace App\Services\Files;

use App\Models\EvidenceFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceFileService
{
    public function __construct(
        private readonly FileStorageService $storage,
        private readonly StorageContextResolver $context,
    ) {
    }

    public function store(
        Model $owner,
        UploadedFile $upload,
        string $directory,
        string $storedName,
        ?string $userId = null,
        ?string $origin = null,
        ?string $disk = null,
    ): EvidenceFile {
        $extension  = strtolower((string) $upload->getClientOriginalExtension());
        $storedName = $this->ensureExtension($storedName, $extension);
        $stored     = $this->storage->putUploadedAs(
            $upload,
            $this->context->scopedDirectory($directory),
            $storedName,
            $disk ?: $this->context->evidenceDisk()
        );

        return $owner->EvidenceFiles()->create([
            'user_id'       => $userId,
            'original_name' => $upload->getClientOriginalName(),
            'stored_name'   => pathinfo($storedName, PATHINFO_FILENAME),
            'disk'          => $stored['disk'],
            'path'          => $stored['path'],
            'mime'          => $stored['mime'],
            'extension'     => $extension,
            'size'          => $stored['size'],
            'sha256'        => $stored['sha256'],
            'uploaded_at'   => now(),
            'origin'        => $origin,
        ]);
    }

    public function exists(EvidenceFile $file): bool
    {
        return $this->resolveLocation($file) !== null;
    }

    public function location(EvidenceFile $file): ?array
    {
        return $this->resolveLocation($file);
    }

    public function get(EvidenceFile $file): string
    {
        [$disk, $path] = $this->requireLocation($file);

        return Storage::disk($disk)->get($path);
    }

    public function mimeType(EvidenceFile $file): string
    {
        if ($file->mime) {
            return (string) $file->mime;
        }

        [$disk, $path] = $this->requireLocation($file);

        return (string) (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream');
    }

    public function download(EvidenceFile $file): StreamedResponse
    {
        [$disk, $path] = $this->requireLocation($file);

        return Storage::disk($disk)->download(
            $path,
            $file->original_name ?: $file->stored_name
        );
    }

    public function deletePhysical(EvidenceFile $file): bool
    {
        $location = $this->resolveLocation($file);

        if ($location === null) {
            return false;
        }

        [$disk, $path] = $location;

        return Storage::disk($disk)->delete($path);
    }

    private function ensureExtension(string $name, string $extension): string
    {
        $name = trim($name);

        if ($extension !== '' && !str_ends_with(strtolower($name), '.' . $extension)) {
            return $name . '.' . $extension;
        }

        return $name;
    }

    private function requireLocation(EvidenceFile $file): array
    {
        $location = $this->resolveLocation($file);

        if ($location === null) {
            throw new \RuntimeException('Arquivo de evidencia nao encontrado no storage.');
        }

        return $location;
    }

    private function resolveLocation(EvidenceFile $file): ?array
    {
        foreach ($this->locationCandidates($file) as [$disk, $path]) {
            if ($path !== '' && Storage::disk($disk)->exists($path)) {
                return [$disk, $path];
            }
        }

        return null;
    }

    private function locationCandidates(EvidenceFile $file): array
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
            $this->context->evidenceDisk(),
            'public',
            'local',
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
