<?php

namespace App\Http\Controllers;

use App\Models\{File, QualityProcess};
use App\Services\Files\{FileStorageService, FileThumbnailService};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Arquivos da obra para N1/N2/Gestão. Toda operação física passa pelo FileStorageService (local, Azure ou S3);
 * aqui só se valida que o usuário enxerga o processo e que o arquivo pertence à Nota dele.
 */
class QualityFilesController extends Controller
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    private function resolve(QualityProcess $process, File $file): void
    {
        $this->authorize('view', $process);
        abort_unless((int) $file->note_id === (int) $process->note_id, 404);

        if ($file->isTacitAdsRestricted() && !auth()->user()->superadm) {
            abort(403, 'Download bloqueado: ADS tácita disponível apenas para SUPERADM.');
        }
    }

    public function download(QualityProcess $qualityProcess, File $file)
    {
        $this->resolve($qualityProcess, $file);
        abort_unless($this->storage->exists($file), 404, 'Arquivo não encontrado.');

        return $this->storage->download($file);
    }

    public function preview(Request $request, QualityProcess $qualityProcess, File $file, FileThumbnailService $thumbnails)
    {
        $this->resolve($qualityProcess, $file);
        $name = pathinfo((string) $file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;

        if ($request->boolean('thumbnail')) {
            $thumbnail = $thumbnails->ensure($file);

            if ($thumbnail && $this->storage->disk($thumbnail->disk)->exists($thumbnail->path)) {
                return response($this->storage->disk($thumbnail->disk)->get($thumbnail->path), 200, [
                    'Content-Type'        => $thumbnail->mime ?: 'image/webp', 'Cache-Control' => 'private, max-age=86400',
                    'Content-Disposition' => 'inline; filename="' . addslashes(pathinfo($name, PATHINFO_FILENAME) . '.webp') . '"',
                ]);
            }
        }

        abort_unless($this->storage->exists($file), 404, 'Arquivo não encontrado.');

        return response($this->storage->get($file), 200, [
            'Content-Type'        => $this->storage->mimeType($file), 'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
        ]);
    }

    /** Todos os arquivos da Nota em um único .zip (limite de 300 arquivos). */
    public function zip(QualityProcess $qualityProcess)
    {
        $this->authorize('view', $qualityProcess);

        $files = File::query()->where('note_id', $qualityProcess->note_id)->orderBy('created_at')->limit(300)->get()
            ->reject(fn (File $file) => $file->isTacitAdsRestricted() && !auth()->user()->superadm);
        abort_if($files->isEmpty(), 404, 'Nenhum arquivo para baixar.');

        $zipPath = tempnam(sys_get_temp_dir(), 'qzip');
        $zip     = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Não foi possível gerar o arquivo .zip.');

        $temporary = [];
        $used      = [];

        foreach ($files as $file) {
            if (!$this->storage->exists($file) || !($local = $this->storage->temporaryLocalCopy($file))) {
                continue;
            }
            $temporary[] = $local;
            $name        = Str::slug(pathinfo((string) ($file->original_name ?: $file->file_name), PATHINFO_FILENAME)) ?: 'arquivo';
            $entry       = $name . '.' . ($file->ext ?: 'bin');

            if (isset($used[$entry])) {
                $entry = $name . '-' . $file->id . '.' . ($file->ext ?: 'bin');
            }
            $used[$entry] = true;
            $zip->addFile($local, $entry);
        }
        $zip->close();

        // o chamador remove os temporários depois do envio
        register_shutdown_function(fn () => collect($temporary)->each(fn ($path) => @unlink($path)));

        return response()->download($zipPath, 'nota-' . Str::slug((string) $qualityProcess->Note?->note) . '-arquivos.zip')->deleteFileAfterSend(true);
    }
}
