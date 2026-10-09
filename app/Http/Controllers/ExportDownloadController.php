<?php

namespace App\Http\Controllers;

use App\Services\Files\ExportStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDownloadController extends Controller
{
    public function __invoke(Request $request, string $user, string $file, ExportStorage $exports): StreamedResponse
    {
        abort_unless((string) $request->user()->id === $user, 403);

        $path = 'exports/' . $file;

        abort_unless(basename($file) === $file && $exports->exists($path), 404);

        return $exports->download($path, $file);
    }
}
