<?php

namespace App\Services\Files;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Centraliza os arquivos temporários de exportação em um disco configurável.
 *
 * O disco pode ser local hoje e um driver S3/Azure compatível amanhã, sem que
 * jobs, notificações e downloads precisem conhecer a implementação física.
 */
class ExportStorage
{
    public function diskName(): string
    {
        return (string) config('filesystems.exports_disk', config('filesystems.default'));
    }

    public function disk(): FilesystemAdapter
    {
        return Storage::disk($this->diskName());
    }

    public function ensureDirectory(): void
    {
        $this->disk()->makeDirectory('exports');
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    public function download(string $path, string $name): StreamedResponse
    {
        return $this->disk()->download($path, $name);
    }
}
