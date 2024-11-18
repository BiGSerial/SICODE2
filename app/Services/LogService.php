<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class LogService
{
    protected $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    public function getLogs()
    {
        if (!File::exists($this->logPath)) {
            return [];
        }

        $logs = File::get($this->logPath);
        $lines = explode(PHP_EOL, $logs);

        return array_reverse($lines); // reverte para mostrar os logs mais recentes no topo
    }
}
