<?php

namespace App\Console\Commands\Files;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\File;
use App\Services\Files\{FileStorageService, FileThumbnailService};
use Illuminate\Console\Command;

class RetrofillStorageMetadata extends Command
{
    use ShowsProgress;

    protected $signature = 'files:retrofill-storage-metadata
        {--limit=0 : Quantidade máxima de registros processados; 0 processa todos}
        {--chunk=500 : Quantidade por lote}
        {--dry-run : Apenas simula as alterações}
        {--only-missing : Processa apenas registros com metadata ausente ou marcados como não localizados}
        {--no-checksum : Não calcula sha256}
        {--no-thumbnails : Não gera thumbnails}
        {--only-thumbnails : Não altera metadata, apenas gera thumbnails}
        {--from-id=0 : ID inicial}
        {--to-id=0 : ID final}';

    protected $description = 'Retrofill de metadata de arquivos legados e geração opcional de thumbnails.';

    public function handle(FileStorageService $storage, FileThumbnailService $thumbnails): int
    {
        $limit          = max(0, (int) $this->option('limit'));
        $chunk          = max(1, (int) $this->option('chunk'));
        $dryRun         = (bool) $this->option('dry-run');
        $onlyMissing    = (bool) $this->option('only-missing');
        $withChecksum   = !(bool) $this->option('no-checksum');
        $withThumbs     = !(bool) $this->option('no-thumbnails');
        $onlyThumbs     = (bool) $this->option('only-thumbnails');
        $fromId         = max(0, (int) $this->option('from-id'));
        $toId           = max(0, (int) $this->option('to-id'));
        $supportsWebp   = $thumbnails->supportsWebp();

        if ($onlyThumbs) {
            $withThumbs = true;
        }

        if ($withThumbs && !$supportsWebp) {
            $this->warn('GD está sem suporte a WebP. O retrofill seguirá sem gerar thumbnails.');
            $withThumbs = false;
        }

        $query = File::query()
            ->with('thumbnail')
            ->when($fromId > 0, fn ($q) => $q->where('id', '>=', $fromId))
            ->when($toId > 0, fn ($q) => $q->where('id', '<=', $toId))
            ->when($onlyMissing, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('mime')
                        ->orWhereNull('size')
                        ->orWhereNull('sha256')
                        ->orWhere('noexists', true)
                        ->orWhereDoesntHave('thumbnail');
                });
            })
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($limit > 0) {
            $total = min($total, $limit);
        }

        if ($total === 0) {
            $this->info('Nenhum arquivo elegível para retrofill.');

            return self::SUCCESS;
        }

        $stats = [
            'processed' => 0,
            'found' => 0,
            'missing' => 0,
            'metadata_updated' => 0,
            'thumbnails' => 0,
            'unchanged' => 0,
            'errors' => 0,
        ];

        $this->info(($dryRun ? '[dry-run] ' : '') . "Processando {$total} arquivo(s)...");
        $this->progressStart($total);

        $query->chunkById($chunk, function ($files) use (
            $storage,
            $thumbnails,
            $limit,
            $dryRun,
            $withChecksum,
            $withThumbs,
            $onlyThumbs,
            &$stats
        ) {
            foreach ($files as $file) {
                if ($limit > 0 && $stats['processed'] >= $limit) {
                    return false;
                }

                $stats['processed']++;

                try {
                    $changed = false;
                    $thumbed = false;
                    $exists  = $storage->exists($file);

                    if ($exists) {
                        $stats['found']++;

                        if (!$onlyThumbs) {
                            $changed = $this->fillMetadata($file, $storage, $withChecksum, $dryRun);

                            if ($changed) {
                                $stats['metadata_updated']++;
                            }
                        }

                        if ($withThumbs && $thumbnails->canThumbnail($file)) {
                            $before = $file->thumbnail?->updated_at?->timestamp;

                            if (!$dryRun) {
                                $thumb = $thumbnails->ensure($file->fresh('thumbnail') ?: $file);
                                $after = $thumb?->updated_at?->timestamp;

                                if ($thumb && $before !== $after) {
                                    $stats['thumbnails']++;
                                    $thumbed = true;
                                }
                            } elseif (!$file->thumbnail) {
                                $stats['thumbnails']++;
                                $thumbed = true;
                            }
                        }
                    } else {
                        $stats['missing']++;

                        if (!$onlyThumbs && !$file->noexists) {
                            $changed = true;

                            if (!$dryRun) {
                                $file->forceFill(['noexists' => true])->save();
                            }

                            $stats['metadata_updated']++;
                        }
                    }

                    if (!$changed && !$thumbed) {
                        $stats['unchanged']++;
                    }
                } catch (\Throwable $exception) {
                    $stats['errors']++;
                    $this->newLine();
                    $this->warn("Falha no arquivo {$file->id}: {$exception->getMessage()}");
                } finally {
                    $this->progressAdvance();
                }
            }

            return true;
        });

        $this->progressFinish();
        $this->newLine();
        $this->table(
            ['Métrica', 'Total'],
            collect($stats)->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fillMetadata(File $file, FileStorageService $storage, bool $withChecksum, bool $dryRun): bool
    {
        $updates = [
            'disk' => $storage->resolvedDiskName($file),
            'mime' => $storage->mimeType($file),
            'size' => $storage->size($file),
            'noexists' => false,
        ];

        if ($withChecksum) {
            $temp = $storage->temporaryLocalCopy($file);

            if ($temp && is_file($temp)) {
                try {
                    $updates['sha256'] = hash_file('sha256', $temp) ?: null;
                } finally {
                    @unlink($temp);
                }
            }
        }

        $dirty = false;

        foreach ($updates as $key => $value) {
            if ($this->isDifferent($file, $key, $value)) {
                $dirty = true;
                break;
            }
        }

        if ($dirty && !$dryRun) {
            $file->forceFill($updates)->save();
        }

        return $dirty;
    }

    private function isDifferent(File $file, string $key, mixed $value): bool
    {
        if ($key === 'noexists') {
            return (bool) $file->noexists !== (bool) $value;
        }

        $current = $file->getRawOriginal($key);

        if ($key === 'size') {
            return $current === null || (int) $current !== (int) $value;
        }

        if ($value === null) {
            return $current !== null;
        }

        return (string) $current !== (string) $value;
    }
}
