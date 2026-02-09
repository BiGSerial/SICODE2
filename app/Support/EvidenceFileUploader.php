<?php

namespace App\Support;

use App\Models\CancellationRequest;
use App\Models\EvidenceFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function attachEvidence(CancellationRequest $request, User $user, array $data, string $origin): EvidenceFile
    {
        return $request->EvidenceFiles()->create([
            'user_id' => $user->id,
            'original_name' => $data['original_name'],
            'stored_name' => $data['stored_name'],
            'disk' => $data['disk'],
            'path' => $data['path'],
            'mime' => $data['mime'],
            'extension' => $data['extension'],
            'size' => $data['size'],
            'sha256' => $data['sha256'],
            'uploaded_at' => now(),
            'origin' => $origin,
        ]);
    }

    private function storeSingleCancellationFile(UploadedFile $file, string $dir, string $noteRef): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = $this->buildStoredName($noteRef);
        $path = $file->storeAs($dir, $storedName . '.' . $extension, 'public');

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize(),
            'sha256' => hash('sha256', Storage::disk('public')->get($path)),
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
