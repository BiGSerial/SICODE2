<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearOldExports extends Command
{
    protected $signature = 'exports:clear-old';
    protected $description = 'Remove arquivos da pasta exports/ com mais de 24h';

    public function handle()
    {
        $files = Storage::disk('local')->files('exports');

        $now = now();
        $count = 0;

        foreach ($files as $file) {
            $lastModified = \Carbon\Carbon::createFromTimestamp(
                Storage::disk('local')->lastModified($file)
            );

            if ($lastModified->lt($now->copy()->subHours(24))) {
                Storage::disk('local')->delete($file);
                $count++;
            }
        }

        $this->info("Removidos {$count} arquivos antigos de exports/");
        return Command::SUCCESS;
    }
}
