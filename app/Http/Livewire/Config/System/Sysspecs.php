<?php

namespace App\Http\Livewire\Config\System;

use Livewire\Component;

class Sysspecs extends Component
{

    public $freeSpace;
    public $totalSpace;
    public $memoryUsed;
    public $memoryPeak;
    public $memoryTotal;
    public $memoryFree;
    public $load;

    public function mount()
    {
        $this->updateSystemStatus();
    }

    public function updateSystemStatus()
    {
        // Detectar o sistema operacional
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $directory = 'C:/'; // Diretório raiz no Windows
            $memoryInfo = shell_exec('systeminfo');
            $totalMatches = [];
            $freeMatches = [];
            preg_match('/Total Physical Memory:\s*([0-9,]+)/', $memoryInfo, $totalMatches);
            preg_match('/Available Physical Memory:\s*([0-9,]+)/', $memoryInfo, $freeMatches);

            if (isset($totalMatches[1])) {
                $this->memoryTotal = str_replace(',', '', $totalMatches[1]) / 1024;
            } else {
                $this->memoryTotal = 0;
            }

            if (isset($freeMatches[1])) {
                $this->memoryFree = str_replace(',', '', $freeMatches[1]) / 1024;
            } else {
                $this->memoryFree = 0;
            }
        } else {
            $directory = '/dados/sites'; // Diretório específico no Linux
            $memoryInfo = shell_exec('free -m');
            $lines = explode("\n", $memoryInfo);
            $memoryData = explode(" ", preg_replace('!\s+!', ' ', $lines[1]));

            if (isset($memoryData[1])) {
                $this->memoryTotal = $memoryData[1];
            } else {
                $this->memoryTotal = 0;
            }

            if (isset($memoryData[3])) {
                $this->memoryFree = $memoryData[3] + $memoryData[5] + $memoryData[6];
            } else {
                $this->memoryFree = 0;
            }
        }

        // Espaço disponível no drive
        $this->freeSpace = round(disk_free_space($directory) / 1024 / 1024 / 1024, 2);
        $this->totalSpace = round(disk_total_space($directory) / 1024 / 1024 / 1024, 2);

        // Uso de memória
        $this->memoryUsed = round(memory_get_usage() / 1024 / 1024, 2);
        $this->memoryPeak = round(memory_get_peak_usage() / 1024 / 1024, 2);

        // Carga do sistema
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
        } else {
            $output = shell_exec('cat /proc/loadavg');
            $load = explode(' ', $output);
        }

        $this->load = [
            '1min' => isset($load[0]) ? $load[0] : 0,
            '5min' => isset($load[1]) ? $load[1] : 0,
            '15min' => isset($load[2]) ? $load[2] : 0
        ];
    }

    public function render()
    {
        return view('livewire.config.system.sysspecs');
    }
}
