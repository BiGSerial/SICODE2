<?php

namespace App\Http\Livewire\Files\Manager\Concerns;

use App\Models\{File, Note};
use App\Services\Files\FileUploadService;

trait PersistsManagedFileUploads
{
    protected function persistManagedFileUpload(array $pendingFile, Note $note, string $directory, string $storedBaseName, array $attributes = []): File
    {
        return app(FileUploadService::class)->create(
            $pendingFile['file'],
            $note,
            $directory,
            $storedBaseName,
            $pendingFile['ext'],
            array_merge([
                'user_id'       => auth()->id(),
                'service_id'    => $pendingFile['service_id'] ?? null,
                'original_name' => $pendingFile['original_name'] ?? $pendingFile['file']->getClientOriginalName(),
                'suspicious'    => (bool) ($pendingFile['suspicious'] ?? false),
                'noexists'      => false,
            ], $attributes),
        );
    }
}
