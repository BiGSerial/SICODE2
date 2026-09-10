<?php

namespace App\Services\Files;

use App\Models\File;
use Illuminate\Http\UploadedFile;

/**
 * Camada fina para imagens de entidade que vivem como uma simples coluna de
 * caminho (ex.: users.avatar, companies.img_*_path) em vez de um registro na
 * tabela `files`. Delega a operação física ao FileStorageService para não
 * depender de disco/caminho local diretamente nos componentes.
 */
class EntityImageService
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    public function store(UploadedFile $upload, string $directory, string $filename, string $disk = 'public'): string
    {
        return $this->storage->putUploadedAs($upload, $directory, $filename, $disk)['path'];
    }

    public function exists(?string $path, string $disk = 'public'): bool
    {
        return $path !== null && $path !== '' && $this->storage->exists($this->toStorageFile($path, $disk));
    }

    public function delete(?string $path, string $disk = 'public'): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return $this->storage->delete($this->toStorageFile($path, $disk));
    }

    public function url(?string $path, string $disk = 'public'): ?string
    {
        if (!$this->exists($path, $disk)) {
            return null;
        }

        return $this->storage->disk($disk)->url($path);
    }

    private function toStorageFile(string $path, string $disk): File
    {
        return new File(['path' => $path, 'disk' => $disk]);
    }
}
