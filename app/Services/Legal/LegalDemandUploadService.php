<?php

namespace App\Services\Legal;

use App\Models\{File, User};
use App\Models\Legal\{LegalDemand, LegalDemandEvent, LegalDemandFile};
use App\Services\Files\{FileStorageService, StorageContextResolver};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalDemandUploadService
{
    public function __construct(
        private readonly FileStorageService $storage,
        private readonly StorageContextResolver $context,
    ) {
    }

    public function upload(LegalDemand $demand, UploadedFile $upload, ?User $actor, array $payload = []): LegalDemandFile
    {
        $directory = $payload['directory'] ?? "legal/demands/{$demand->id}";
        $name      = $this->normalizeName(
            (string) ($payload['name'] ?? ''),
            (string) $upload->getClientOriginalName(),
            strtolower((string) $upload->getClientOriginalExtension())
        );

        $disk    = (string) ($payload['disk'] ?? $this->context->filesDisk());
        $storage = $this->storage->putUploadedAs(
            $upload,
            $this->context->scopedDirectory($directory),
            $name,
            $disk
        );

        return DB::transaction(function () use ($demand, $upload, $actor, $payload, $storage, $name) {
            $file = LegalDemandFile::create([
                'legal_demand_id'           => $demand->id,
                'assignment_id'             => $payload['assignment_id'] ?? null,
                'legal_demand_subdemand_id' => $payload['legal_demand_subdemand_id'] ?? null,
                'uploaded_by'               => $actor?->id,
                'file_name'                 => basename($storage['path']),
                'original_name'             => $name,
                'path'                      => $storage['path'],
                'disk'                      => $storage['disk'],
                'mime_type'                 => $storage['mime'] ?: $upload->getMimeType(),
                'size'                      => $storage['size'] ?: $upload->getSize(),
                'sha256'                    => $storage['sha256'] ?? null,
                'visibility'                => $payload['visibility'] ?? 'internal_all',
            ]);

            LegalDemandEvent::create([
                'legal_demand_id' => $demand->id,
                'event_type'      => 'file_uploaded',
                'actor_user_id'   => $actor?->id,
                'metadata'        => [
                    'legal_demand_file_id'      => $file->id,
                    'assignment_id'             => $file->assignment_id,
                    'legal_demand_subdemand_id' => $file->legal_demand_subdemand_id,
                    'visibility'                => $file->visibility,
                    'disk'                      => $file->disk,
                    'path'                      => $file->path,
                ],
                'occurred_at' => now(),
            ]);

            return $file;
        });
    }

    public function exists(LegalDemandFile $file): bool
    {
        return $this->storage->exists($this->toStorageFile($file));
    }

    public function download(LegalDemandFile $file, ?string $name = null): StreamedResponse
    {
        return $this->storage->download($this->toStorageFile($file), $name ?: $file->downloadName());
    }

    public function content(LegalDemandFile $file): string
    {
        return $this->storage->get($this->toStorageFile($file));
    }

    public function mimeType(LegalDemandFile $file): string
    {
        return $this->storage->mimeType($this->toStorageFile($file));
    }

    private function toStorageFile(LegalDemandFile $file): File
    {
        return new File(['path' => $file->path, 'disk' => $file->storageDisk()]);
    }

    private function normalizeName(string $customName, string $originalName, string $extension): string
    {
        $name = trim($customName) !== '' ? trim($customName) : $originalName;
        $name = preg_replace('/[\\\\\\/]+/', '-', $name) ?: $originalName;

        if ($extension !== '' && !str_ends_with(strtolower($name), '.' . $extension)) {
            $name .= '.' . $extension;
        }

        return $name;
    }
}
