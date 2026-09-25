<?php

namespace App\Jobs\Files;

use App\Models\{File, FileDownloadBatch, User};
use App\Notifications\SystemNotification;
use App\Services\Files\FileStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Storage, URL};
use Illuminate\Support\Str;
use ZipArchive;

class GenerateFileDownloadBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 2;
    public array $fileIds;
    public int $batchId;

    public function __construct(array $fileIds, int $batchId)
    {
        $this->fileIds = array_values(array_unique(array_map('intval', $fileIds)));
        $this->batchId = $batchId;
        $this->onQueue('exports');
    }

    public function handle(FileStorageService $storage): void
    {
        $batch = FileDownloadBatch::findOrFail($this->batchId);
        $zipPath = null;
        $tempCopies = [];
        $zip = null;

        try {
            $batch->update(['status' => 'processing']);
            $files = File::with(['Note.Orders'])->whereIn('id', $this->fileIds)->get();
            $disk = Storage::disk('local');
            $disk->makeDirectory('downloads/batches');
            $zipPath = 'downloads/batches/' . Str::uuid() . '.zip';
            $localZipPath = $disk->path($zipPath);
            $zip = new ZipArchive();

            if ($zip->open($localZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Não foi possível criar o arquivo ZIP.');
            }

            $usedNames = [];
            $added = 0;
            foreach ($files as $index => $file) {
                if (!$storage->exists($file)) {
                    continue;
                }

                $sourcePath = null;
                if ($storage->resolvedDiskName($file) === 'local') {
                    $candidate = $storage->disk('local')->path((string) $storage->resolvedPath($file));
                    $sourcePath = is_file($candidate) ? $candidate : null;
                }

                $localPath = $sourcePath ?: $storage->temporaryLocalCopy($file);
                if (!$localPath || !$storage->matchesStoredChecksum($file, $localPath)) {
                    if (!$sourcePath && $localPath && is_file($localPath)) {
                        @unlink($localPath);
                    }
                    throw new \RuntimeException('Checksum divergente ou cópia inválida para ' . ($file->original_name ?: $file->file_name));
                }

                if (!$sourcePath) {
                    $tempCopies[] = $localPath;
                }
                $zip->addFile($localPath, $this->outputName($file, $index + 1, $usedNames, $batch->output_pattern));
                $added++;
            }

            $zip->close();
            $zip = null;
            if ($added === 0) {
                throw new \RuntimeException('Nenhum arquivo disponível para compactação.');
            }

            $expiresAt = now()->addHours((int) config('filesystems.download_batch_ttl_hours', 24));
            $downloadName = $this->downloadName($batch, $files);
            $batch->update([
                'status' => 'ready', 'disk' => 'local', 'path' => $zipPath,
                'download_name' => $downloadName, 'expires_at' => $expiresAt,
                'completed_at' => now(), 'error_message' => null,
            ]);

            if ($user = User::find($batch->user_id)) {
                $link = URL::temporarySignedRoute('files.batch.download', $expiresAt, ['batch' => $batch->id]);
                $user->notify(new SystemNotification(
                    'Download de arquivos pronto',
                    "{$added} arquivo(s) foram compactados. O link expira em {$expiresAt->format('d/m/Y H:i')}.",
                    $link,
                    4,
                    ['action_type' => 'download', 'action_label' => 'Baixar ZIP']
                ));
            }
        } catch (\Throwable $exception) {
            if ($zip instanceof ZipArchive) {
                @$zip->close();
            }
            if ($zipPath) {
                Storage::disk('local')->delete($zipPath);
            }
            $batch->update(['status' => 'failed', 'error_message' => Str::limit($exception->getMessage(), 1000)]);
            Log::error('GenerateFileDownloadBatchJob falhou', ['batch_id' => $this->batchId, 'error' => $exception->getMessage()]);
            throw $exception;
        } finally {
            foreach ($tempCopies as $copy) {
                if (is_file($copy)) {
                    @unlink($copy);
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        FileDownloadBatch::whereKey($this->batchId)->update([
            'status' => 'failed',
            'error_message' => Str::limit($exception->getMessage(), 1000),
        ]);
    }

    private function outputName(File $file, int $sequence, array &$usedNames, ?string $pattern): string
    {
        $extension = Str::lower($file->ext ?: pathinfo($file->file_name ?: '', PATHINFO_EXTENSION));
        $extension = $extension ? '.' . ltrim($extension, '.') : '';
        $name = trim((string) $pattern) === ''
            ? pathinfo($file->file_name ?: 'arquivo', PATHINFO_FILENAME)
            : str_replace(
                ['<nota>', '<ordem>', '<sequencia>'],
                [$file->Note?->note ?: 'sem-nota', $this->order($file), str_pad((string) $sequence, 3, '0', STR_PAD_LEFT)],
                $pattern
            );
        $name = trim(preg_replace('/[\\\\\/:*?"<>|]+/', '-', Str::ascii($name)) ?: 'arquivo', '-_. ');
        $candidate = ($name ?: 'arquivo') . $extension;
        $suffix = 2;
        while (in_array($candidate, $usedNames, true)) {
            $candidate = ($name ?: 'arquivo') . '-' . $suffix++ . $extension;
        }
        $usedNames[] = $candidate;
        return $candidate;
    }

    private function order(File $file): string
    {
        foreach ($file->Note?->Orders?->pluck('ordem')->filter()->map(fn ($value) => (string) $value) ?? [] as $order) {
            if (str_starts_with($order, '170') || str_starts_with($order, '190') || str_starts_with($order, '150')) {
                return $order;
            }
        }
        return (string) ($file->Note?->Orders?->pluck('ordem')->filter()->first() ?: 'sem-ordem');
    }

    private function downloadName(FileDownloadBatch $batch, $files): string
    {
        $base = trim((string) $batch->output_pattern) !== '' ? $batch->output_pattern : 'arquivos-' . now()->format('Ymd-His');
        $base = trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', Str::ascii($base)), '-_') ?: 'arquivos';
        return $base . '.zip';
    }
}
