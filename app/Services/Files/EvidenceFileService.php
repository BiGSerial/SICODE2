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

    public function download(EvidenceFile $file): StreamedResponse
    {
        $location = $this->resolveLocation($file);

        if ($location === null) {
            throw new \RuntimeException('Arquivo não encontrado no storage.');
        }

        [$disk, $path] = $location;

        return Storage::disk($disk)->download(
            $path,
            $file->original_name ?: $file->stored_name
        );
    }

    public function deletePhysical(EvidenceFile $file): bool
    {
        $location = $this->resolveLocation($file);

        return $location !== null && Storage::disk($location[0])->delete($location[1]);
    }

    private function resolveLocation(EvidenceFile $file): ?array
    {
        $rawPath = ltrim((string) $file->path, '/');

        if ($rawPath === '') {
            return null;
        }

        $paths = array_values(array_unique(array_filter([
            $rawPath,
            str_starts_with($rawPath, 'storage/') ? substr($rawPath, 8) : null,
            str_starts_with($rawPath, 'public/') ? substr($rawPath, 7) : null,
        ])));

        $disks = array_values(array_unique(array_filter([
            $file->disk ?: null,
            $this->context->evidenceDisk(),
            'local',
            'public',
        ])));

        foreach ($disks as $disk) {
            foreach ($paths as $path) {
                if (Storage::disk($disk)->exists($path)) {
                    return [$disk, $path];
                }
            }
        }

        return null;
    }

    private function ensureExtension(string $name, string $extension): string
    {
        $name = trim($name);

        if ($extension !== '' && !str_ends_with(strtolower($name), '.' . $extension)) {
            return $name . '.' . $extension;
        }

        return $name;
    }
}
