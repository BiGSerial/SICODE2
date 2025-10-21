<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class FilesController extends Controller
{
    public function main()
    {
        return view('files.managerfiles');
    }

    public function download(File $file)
    {
        // Autorização (ajuste Gate/policy conforme seu app)
        // abort_if(Gate::denies('view-file', $file), 403);

        if (!Storage::exists($file->path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $name = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;

        return Storage::download($file->path, $name);
    }

    public function zipSelected(Request $request)
    {
        $ids  = collect(explode(',', (string) $request->query('ids', '')))->filter()->map('intval')->all();
        $note = (string) $request->query('note', 'Arquivos');

        if (empty($ids)) {
            return back()->with('error', 'Nenhum arquivo selecionado.');
        }

        $files = File::whereIn('id', $ids)->get();
        if ($files->isEmpty()) {
            abort(404, 'Arquivos não encontrados.');
        }

        $zipFile = 'Arquivos-' . $note . '-' . hash('crc32', microtime(true)) . '.zip';
        $zip     = new ZipArchive();

        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            if (Storage::exists($file->path)) {
                $content = Storage::get($file->path);
                $name    = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;
                $zip->addFromString($name, $content);
            }
        }

        $zip->close();

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }
}
