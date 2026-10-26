<?php

namespace App\Support;

use App\Models\{CancellationRequest, EvidenceFile, User};
use App\Models\Closure\ClosureWorkMessage;
use App\Services\Files\{FileStorageService, StorageContextResolver};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EvidenceFileUploader
{
    public function storeCancellationEvidence(
        CancellationRequest $request,
        array $attachments,
        User $user,
        string $origin = 'CANCELLATION_REQUEST'
    ): void {
        if (empty($attachments)) {
            return;
        }

        $noteRef = $this->normalizeNoteRef($request->Note?->note ?? 'nota');

        foreach ($attachments as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $data = $this->storeSingleCancellationFile($file, 'evidences/CANCELLATION_REQUEST/' . $request->id, $noteRef);
            $this->attachEvidence($request, $user, $data, $origin);
        }
    }

    public function storeSharedCancellationFile(UploadedFile $file, string $noteRef = 'multi'): array
    {
        $noteRef = $this->normalizeNoteRef($noteRef ?: 'multi');

        return $this->storeSingleCancellationFile($file, 'evidences/CANCELLATION_SHARED', $noteRef);
    }

    /**
     * Anexa arquivos a uma mensagem do chat da obra (morph EvidenceFile.evidenciable).
     *
     * @param array<int, mixed> $attachments
     * @return array<int, EvidenceFile>
     */
    public function storeClosureWorkMessageEvidence(
        ClosureWorkMessage $message,
        array $attachments,
        User $user,
        string $noteRef,
        string $origin = 'CLOSURE_WORK_MESSAGE'
    ): array {
        $saved   = [];
        $noteRef = $this->normalizeNoteRef($noteRef);

        $stored = [];

        try {
            foreach ($attachments as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $data     = $this->storeSingleCancellationFile($file, 'evidences/CLOSURE_WORK_MESSAGE/' . $message->id, $noteRef);
                $stored[] = $data;
                $saved[]  = $this->attachEvidence($message, $user, $data, $origin);
            }
        } catch (\Throwable $exception) {
            // Falhou no meio do lote: não deixa arquivo físico órfão (o rollback do banco não alcança o storage).
            $storage = app(FileStorageService::class);

            foreach ($stored as $data) {
                $storage->delete(new \App\Models\File(['path' => $data['path'], 'disk' => $data['disk']]));
            }

            throw $exception;
        }

        return $saved;
    }

    public function attachEvidence(Model $request, User $user, array $data, string $origin): EvidenceFile
    {
        return $request->EvidenceFiles()->create([
            'user_id'       => $user->id,
            'original_name' => $data['original_name'],
            'stored_name'   => $data['stored_name'],
            'disk'          => $data['disk'],
            'path'          => $data['path'],
            'mime'          => $data['mime'],
            'extension'     => $data['extension'],
            'size'          => $data['size'],
            'sha256'        => $data['sha256'],
            'uploaded_at'   => now(),
            'origin'        => $origin,
        ]);
    }

    private function storeSingleCancellationFile(UploadedFile $file, string $dir, string $noteRef): array
    {
        $context    = app(StorageContextResolver::class);
        $storage    = app(FileStorageService::class);
        $extension  = strtolower($file->getClientOriginalExtension());
        $storedName = $this->buildStoredName($noteRef);
        $stored     = $storage->putUploadedAs(
            $file,
            $context->scopedDirectory($dir),
            $storedName . '.' . $extension,
            $context->evidenceDisk(),
        );

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'disk'          => $stored['disk'],
            'path'          => $stored['path'],
            'mime'          => $stored['mime'],
            'extension'     => $extension,
            'size'          => $stored['size'],
            'sha256'        => $stored['sha256'],
        ];
    }

    private function normalizeNoteRef(string $note): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $note) ?: 'nota';

        return Str::lower(Str::limit($clean, 24, ''));
    }

    private function buildStoredName(string $noteRef): string
    {
        $hash = Str::lower(Str::random(6));

        return "evidencia-{$noteRef}-{$hash}";
    }
}
