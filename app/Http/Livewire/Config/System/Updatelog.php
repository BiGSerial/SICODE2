<?php

namespace App\Http\Livewire\Config\System;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Updatelog extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public Collection $logUpdates;
    public Collection $tasks;
    public $singleTask;
    public $perPage = 10;
    public $seg = 0;

    protected $queryString = [
        'singleTask' => ['as' => 'task', 'except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $this->logUpdates = collect();
        $this->tasks = collect();
        $this->loadLogUpdates();
        $this->tasks = $this->logUpdates->pluck('tarefa')->unique();

        $this->selectTask();
    }

    public function updatedSingleTask()
    {
        $this->resetPage(); // Reset pagination when task changes
        $this->selectTask();
    }


    private function loadLogUpdates(): void
    {
        $jsonPath = base_path('registroUpdate.json');
        $stream = fopen($jsonPath, 'r');

        if ($stream) {
            while (($line = fgets($stream)) !== false) {
                try {
                    $data = json_decode($line, true);

                    if (is_array($data)) {
                        $this->logUpdates->push($data);
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao decodificar linha JSON: " . $e->getMessage());
                }
            }

            fclose($stream);
        } else {
            Log::error("Não foi possível abrir o arquivo JSON para leitura.");
        }
    }



    public function selectTask()
    {
        if ($this->singleTask) {
            $this->logUpdates = $this->logUpdates->filter(fn ($log) => $log['tarefa'] === $this->singleTask);
        }
    }

    public function getPaginatedLogsProperty()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = $this->logUpdates->sortByDesc('date_inicio')->forPage($currentPage, $this->perPage);
        return new LengthAwarePaginator($items, $this->logUpdates->count(), $this->perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }

    public function render()
    {
        $this->seg++;

        return view('livewire.config.system.updatelog', [
            'logs' => $this->paginatedLogs,
        ]);
    }
}
