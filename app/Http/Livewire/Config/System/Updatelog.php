<?php

namespace App\Http\Livewire\Config\System;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Updatelog extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $logUpdates = null;
    public $tasks = null;
    public $singleTask;
    public $perPage = 10;
    public $seg = 0;

    protected $queryString = [
        'singleTask' => ['as' => 'task','except' => ''],
        'page' => ['except' => 1],
        ];

    public function mount()
    {
        $jsonPath = base_path('registroUpdate.json');
        $jsonData = file_get_contents($jsonPath);
        $data = json_decode($jsonData, true);
        $this->logUpdates = collect($data);

        $this->tasks = $this->logUpdates->pluck('tarefa')->unique();

        $this->selectTask();

    }

    public function selectTask()
    {

        if ($this->singleTask) {
            $this->logUpdates = $this->logUpdates->where('tarefa', $this->singleTask)->sortByDesc('date_inicio');
        } else {

            $jsonPath = base_path('registroUpdate.json');
            $jsonData = file_get_contents($jsonPath);
            $data = json_decode($jsonData, true);
            $this->logUpdates = collect($data);
            $this->logUpdates = $this->logUpdates->sortByDesc('date_inicio');
        }
    }


    public function getPaginatedLogsProperty()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = $this->logUpdates->forPage($currentPage, $this->perPage);
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
