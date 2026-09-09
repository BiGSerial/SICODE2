<?php

namespace App\Console\Commands\Files;

use App\Models\File;
use App\Services\Files\FileThumbnailService;
use Illuminate\Console\Command;

class GenerateThumbnails extends Command
{
    protected $signature = 'files:generate-thumbnails
        {--limit=500 : Quantidade máxima de arquivos processados}
        {--missing : Processa apenas arquivos sem thumbnail registrado}';

    protected $description = 'Gera thumbnails WebP para arquivos de imagem.';

    public function handle(FileThumbnailService $thumbnails): int
    {
        if (!$thumbnails->supportsWebp()) {
            $this->error('GD está sem suporte a WebP neste ambiente. Refaça o build da imagem PHP com libwebp-dev e --with-webp.');

            return self::FAILURE;
        }

        $limit           = max(1, (int) $this->option('limit'));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

        $query = File::query()
            ->when($this->option('missing'), fn ($query) => $query->doesntHave('thumbnail'))
            ->whereIn('ext', $imageExtensions)
            ->orderBy('id');

        $processed = 0;
        $created   = 0;

        $query->chunkById(100, function ($files) use ($thumbnails, $limit, &$processed, &$created) {
            foreach ($files as $file) {
                if ($processed >= $limit) {
                    return false;
                }

                $processed++;
                $thumbnail = $thumbnails->ensure($file);

                if ($thumbnail) {
                    $created++;
                }
            }

            return true;
        });

        $this->info("Thumbnails processados: {$processed}. Disponíveis: {$created}.");

        return self::SUCCESS;
    }
}
