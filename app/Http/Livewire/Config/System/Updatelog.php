<?php

namespace App\Http\Livewire\Config\System;

use App\Models\UpdateExecutionLog;
use Livewire\Component;

class Updatelog extends Component
{
    public array $logs = [];
    public array $runningLogs = [];
    public array $tasks = [];
    public ?string $singleTask = null;

    public int $pageSize = 20;
    public int $skipMatched = 0;
    public bool $hasMore = true;

    protected $queryString = [
        'singleTask' => ['as' => 'task', 'except' => ''],
    ];

    public function mount(): void
    {
        $this->tasks = UpdateExecutionLog::query()
            ->select('task')
            ->distinct()
            ->orderBy('task')
            ->pluck('task')
            ->values()
            ->all();

        $this->refreshRunningLogs();
        $this->loadMore();
    }

    public function updatedSingleTask(): void
    {
        $this->refreshRunningLogs();
        $this->resetCursor();
        $this->loadMore();
    }

    public function resetCursor(): void
    {
        $this->logs = [];
        $this->skipMatched = 0;
        $this->hasMore = true;
    }

    public function loadMore(): void
    {
        if (!$this->hasMore) {
            return;
        }

        $query = UpdateExecutionLog::query()
            ->when($this->singleTask, fn ($q) => $q->where('task', $this->singleTask))
            ->orderByDesc('date_inicio')
            ->orderByDesc('id');

        $rows = $query
            ->skip($this->skipMatched)
            ->take($this->pageSize + 1)
            ->get();

        $this->hasMore = $rows->count() > $this->pageSize;
        $pageRows = $rows->take($this->pageSize);

        foreach ($pageRows as $row) {
            $this->logs[] = [
                'id' => (string) $row->id,
                'tarefa' => $row->task,
                'status' => $row->status,
                'date_inicio' => optional($row->date_inicio)->toDateTimeString(),
                'date_fim' => optional($row->date_fim)->toDateTimeString(),
                'created' => (int) $row->created,
                'updated' => (int) $row->updated,
                'total' => (int) $row->total,
                'erros' => (int) $row->erros,
                'errosMSGs' => $row->errosMSGs ?? [],
                'options' => $row->options ?? [],
                'noteupdated' => $row->noteupdated,
                'fail_reason' => $row->fail_reason,
            ];
        }

        $this->skipMatched += $pageRows->count();
    }

    public function refreshRunningLogs(): void
    {
        $rows = UpdateExecutionLog::query()
            ->where('status', UpdateExecutionLog::STATUS_RUNNING)
            ->when($this->singleTask, fn ($q) => $q->where('task', $this->singleTask))
            ->orderBy('date_inicio')
            ->limit(50)
            ->get();

        $this->runningLogs = $rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'tarefa' => $row->task,
                'date_inicio' => optional($row->date_inicio)->toDateTimeString(),
                'total' => (int) $row->total,
                'created' => (int) $row->created,
                'updated' => (int) $row->updated,
                'erros' => (int) $row->erros,
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.config.system.updatelog', [
            'logs' => $this->logs,
            'runningLogs' => $this->runningLogs,
            'hasMore' => $this->hasMore,
            'tasks' => $this->tasks,
        ]);
    }
}
