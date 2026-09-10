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
        return filled($file->path) && Storage::disk($this->diskName($file))->exists($file->path);
    }

    public function download(EvidenceFile $file): StreamedResponse
    {
        return Storage::disk($this->diskName($file))->download(
            (string) $file->path,
            $file->original_name ?: $file->stored_name
        );
    }

    public function deletePhysical(EvidenceFile $file): bool
    {
        return $this->exists($file) && Storage::disk($this->diskName($file))->delete((string) $file->path);
    }

    private function diskName(EvidenceFile $file): string
    {
        return $file->disk ?: 'public';
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
