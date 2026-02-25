<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class FilesController extends Controller
{
    private function ensureTacitDownloadPermission(File $file): void
    {
        $user = auth()->user();
        abort_if(!$user, 403, 'Não autorizado.');

        if ($file->isTacitAdsRestricted() && !$user->superadm) {
            abort(403, 'Download bloqueado: ADS tácita disponível apenas para SUPERADM.');
        }
    }

    public function main()
    {
        return view('files.managerfiles');
    }

    public function download(File $file)
    {
        // Autorização (ajuste Gate/policy conforme seu app)
        // abort_if(Gate::denies('view-file', $file), 403);

        $this->ensureTacitDownloadPermission($file);

        if (!Storage::exists($file->path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $name = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;

        return Storage::download($file->path, $name);
    }

    public function preview(File $file)
    {
        $this->ensureTacitDownloadPermission($file);

        $rawPath = ltrim((string) $file->path, '/');
        $name = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;

        $storageCandidates = array_values(array_unique(array_filter([
            $rawPath,
            Str::startsWith($rawPath, 'storage/') ? Str::after($rawPath, 'storage/') : null,
        ])));

        foreach ($storageCandidates as $candidate) {
            if (Storage::exists($candidate)) {
                $mime = Storage::mimeType($candidate) ?: 'application/octet-stream';
                return response(Storage::get($candidate), 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
                    'Cache-Control' => 'private, max-age=300',
                ]);
            }

            if (Storage::disk('public')->exists($candidate)) {
                $mime = Storage::disk('public')->mimeType($candidate) ?: 'application/octet-stream';
                return response(Storage::disk('public')->get($candidate), 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
                    'Cache-Control' => 'private, max-age=300',
                ]);
            }
        }

        $fsCandidates = array_values(array_unique(array_filter([
            public_path($rawPath),
            public_path('storage/' . $rawPath),
            storage_path('app/public/' . $rawPath),
            storage_path('app/' . $rawPath),
        ])));

        foreach ($fsCandidates as $candidate) {
            if (is_file($candidate)) {
                $mime = @mime_content_type($candidate) ?: 'application/octet-stream';
                return response()->file($candidate, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
                    'Cache-Control' => 'private, max-age=300',
                ]);
            }
        }

        abort(404, 'Arquivo não encontrado para visualização.');
    }

    public function zipSelected(Request $request)
    {
        $ids  = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $note = (string) $request->query('note', 'Arquivos');
        $noteId = (int) $request->query('note_id', 0);

        if (empty($ids)) {
            return back()->with('error', 'Nenhum arquivo selecionado.');
        }
        if ($noteId <= 0) {
            abort(422, 'Contexto da nota inválido para gerar ZIP.');
        }

        $files = File::where('note_id', $noteId)
            ->whereIn('id', $ids)
            ->get();
        if ($files->isEmpty()) {
            abort(404, 'Arquivos não encontrados.');
        }

        $user = auth()->user();
        abort_if(!$user, 403, 'Não autorizado.');
        if (!$user->superadm && $files->contains(fn (File $file) => $file->isTacitAdsRestricted())) {
            abort(403, 'Download ZIP bloqueado: contém ADS tácita (apenas SUPERADM).');
        }

        $safeNote = preg_replace('/[^A-Za-z0-9_\-]/', '_', $note) ?: 'Arquivos';
        $zipFile = storage_path('app/tmp/Arquivos-' . $safeNote . '-' . hash('crc32', microtime(true)) . '.zip');
        if (!Storage::exists('tmp')) {
            Storage::makeDirectory('tmp');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Não foi possível criar o arquivo ZIP.');
        }

        $usedNames = [];
        $added = 0;

        foreach ($files as $file) {
            if (Storage::exists($file->path)) {
                $content = Storage::get($file->path);
                $baseName = pathinfo($file->file_name, PATHINFO_FILENAME);
                $ext = $file->ext ? '.' . $file->ext : '';
                $name = $baseName . $ext;

                // Evita sobrescrever arquivos com o mesmo nome dentro do ZIP.
                if (isset($usedNames[$name])) {
                    $usedNames[$name]++;
                    $name = $baseName . ' (' . $usedNames[$name] . ')' . $ext;
                } else {
                    $usedNames[$name] = 1;
                }

                $zip->addFromString($name, $content);
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipFile);
            abort(404, 'Nenhum arquivo disponível para compactação.');
        }

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }
}
