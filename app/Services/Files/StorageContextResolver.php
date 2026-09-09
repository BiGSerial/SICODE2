<?php

namespace App\Services\Files;

class StorageContextResolver
{
    public function state(): string
    {
        $state = strtolower((string) config('sicode.storage.state', config('sicode.ruleset', 'es')));

        return in_array($state, ['es', 'sp'], true) ? $state : 'es';
    }

    public function filesDisk(): string
    {
        $state = $this->state();

        return (string) config("sicode.storage.files_disk_by_state.{$state}", 'local');
    }

    public function evidenceDisk(): string
    {
        $state = $this->state();

        return (string) config("sicode.storage.evidence_disk_by_state.{$state}", $this->filesDisk());
    }

    public function pathPrefix(): string
    {
        $state = $this->state();

        return trim((string) config("sicode.storage.path_prefix_by_state.{$state}", $state), '/');
    }

    public function scopedDirectory(string $directory): string
    {
        $directory = trim($directory, '/');
        $prefix    = $this->pathPrefix();

        if ($prefix === '' || str_starts_with($directory . '/', $prefix . '/')) {
            return $directory;
        }

        return trim($prefix . '/' . $directory, '/');
    }
}
