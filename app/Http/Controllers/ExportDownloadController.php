<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDownloadController extends Controller
{
    public function __invoke(Request $request, string $user, string $file): StreamedResponse
    {
        abort_unless((string) $request->user()->id === $user, 403);

        $path = 'exports/' . $file;

        abort_unless(basename($file) === $file && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $file);
    }
}
