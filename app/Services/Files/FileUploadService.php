<?php

namespace App\Services\Files;

use App\Models\{File, Note};
use Illuminate\Http\UploadedFile;
use RuntimeException;

class FileUploadService
{
    public function __construct(
        private readonly FileStorageService $storage,
        private readonly FileThumbnailService $thumbnails,
        private readonly StorageContextResolver $context,
    ) {
    }

    public function nextRevision(string $baseName): int
    {
        return File::where('file_name', 'like', $baseName . '%')->count();
    }

    public function create(
        UploadedFile $upload,
        Note $note,
        string $directory,
        string $baseName,
        string $extension,
        array $attributes = [],
        ?string $disk = null,
    ): File {
        $extension  = strtolower(trim($extension, '.'));
        $disk       = $disk ?: $this->context->filesDisk();
        $directory  = $this->context->scopedDirectory($directory);
        $storedName = $baseName . '.' . $extension;

        $stored = $this->storage->putUploadedAs($upload, $directory, $storedName, $disk);

        $file = File::create(array_merge([
            'note_id'       => $note->id,
            'user_id'       => auth()->id(),
            'service_id'    => null,
            'file_name'     => $baseName,
            'original_name' => $upload->getClientOriginalName(),
            'path'          => $stored['path'],
            'disk'          => $stored['disk'],
            'ext'           => $extension,
            'mime'          => $stored['mime'],
            'size'          => $stored['size'],
            'sha256'        => $stored['sha256'],
            'suspicious'    => false,
            'noexists'      => false,
        ], $attributes));

        if (!$this->storage->exists($file)) {
            $file->delete();

            throw new RuntimeException('Arquivo não encontrado após gravação.');
        }

        $this->thumbnails->ensure($file);

        return $file;
    }

    public function replace(File $file, UploadedFile $upload, string $directory, string $baseName, string $extension): File
    {
        $oldPath   = $file->path;
        $oldDisk   = $file->disk ?: 'local';
        $extension = strtolower(trim($extension, '.'));
        $disk      = $file->disk ?: $this->context->filesDisk();
        $directory = $this->context->scopedDirectory($directory);
        $stored    = $this->storage->putUploadedAs($upload, $directory, $baseName . '.' . $extension, $disk);

        if ($oldPath && ($oldPath !== $stored['path'] || $oldDisk !== $stored['disk'])) {
            $oldFile = new File(['path' => $oldPath, 'disk' => $oldDisk]);
            $this->storage->delete($oldFile);
        }

        $file->forceFill([
            'path'          => $stored['path'],
            'disk'          => $stored['disk'],
            'ext'           => $extension,
            'mime'          => $stored['mime'],
            'size'          => $stored['size'],
            'sha256'        => $stored['sha256'],
            'suspicious'    => false,
            'original_name' => $upload->getClientOriginalName(),
            'noexists'      => false,
        ]);

        $this->thumbnails->ensure($file);

        return $file;
    }

    public function delete(File $file): bool
    {
        return $this->storage->delete($file);
    }

    public function renameStoredFile(File $file, string $baseName): File
    {
        $extension = strtolower((string) $file->ext);
        $directory = trim((string) dirname((string) $file->path), '.');
        $newPath   = ltrim($directory ? $directory . '/' : '', '/') . $baseName . ($extension !== '' ? '.' . $extension : '');

        if (!$this->storage->move($file, $newPath)) {
            throw new RuntimeException('Falha ao renomear arquivo no disco.');
        }

        $file->forceFill([
            'file_name' => $baseName,
            'path'      => $newPath,
        ])->save();

        $this->thumbnails->ensure($file);

        return $file;
    }
}
