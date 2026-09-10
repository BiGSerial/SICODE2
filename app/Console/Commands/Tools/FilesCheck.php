<?php

namespace App\Console\Commands\Tools;

use App\Models\File;
use App\Services\Files\FileStorageService;
use Illuminate\Console\Command;

class FilesCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:files-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = File::all();
        $missingFiles = [];
        $storage = app(FileStorageService::class);

        foreach ($files as $file) {
            if (!$storage->exists($file)) {

                $missingFiles[] = $file->path;
                $this->warn("Arquivo inexistente: {$file->file_name} - Caminho: {$file->path}");
            }
        }

        if (empty($missingFiles)) {
            $this->info('Todos os arquivos da base de dados existem no diretório.');
        } else {
            $this->info('Existem '.count($missingFiles).'/'.File::count().' Arquivos inexistentes listados acima.');
        }

        return 0;
    }
}
