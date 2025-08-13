<?php

namespace App\Http\Livewire\Config\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Carbon\Carbon;

class JobsServices extends Component
{
    // Listas
    public $pendingJobs;   // aguardando
    public $runningJobs;   // em execução (reserved_at recente)
    public $delayedJobs;   // agendados (available_at > now)
    public $failedJobs;    // falhados
    public $succeeded;     // finalizados com sucesso (histórico)

    // Resumos
    public bool $workerActive = false;
    public string $workerSource = 'desconhecido';
    public array $queueCounts = []; // por fila

    // Limites
    public int $limitPerList = 25;

    // Threshold de execução recente (em segundos) — público para uso no Blade
    public int $runningThresholdSeconds = 120;

    public function mount()
    {
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.config.system.jobs-services');
    }

    public function refreshData()
    {
        $nowTs = now()->timestamp;

        // PENDENTES
        $this->pendingJobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $nowTs)
            ->orderBy('id', 'desc')
            ->limit($this->limitPerList)
            ->get()
            ->map(fn ($j) => $this->decorateJobRow($j));

        // EM EXECUÇÃO (reserved_at recente)
        $this->runningJobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '>=', $nowTs - $this->runningThresholdSeconds)
            ->orderBy('reserved_at', 'desc')
            ->limit($this->limitPerList)
            ->get()
            ->map(fn ($j) => $this->decorateJobRow($j, true));

        // ATRASADOS (agendados p/ futuro)
        $this->delayedJobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->where('available_at', '>', $nowTs)
            ->orderBy('available_at', 'asc')
            ->limit($this->limitPerList)
            ->get()
            ->map(fn ($j) => $this->decorateJobRow($j));

        // FALHADOS
        $this->failedJobs = DB::table('failed_jobs')
            ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
            ->orderBy('id', 'desc')
            ->limit($this->limitPerList)
            ->get()
            ->map(function ($j) {
                $p = json_decode($j->payload, true) ?: [];
                return (object)[
                    'id'        => $j->id,
                    'uuid'      => $j->uuid ?? ($p['uuid'] ?? null),
                    'queue'     => $j->queue,
                    'name'      => $p['displayName'] ?? ($p['data']['commandName'] ?? '—'),
                    'exception' => $this->shortException($j->exception),
                    'failed_at' => Carbon::parse($j->failed_at),
                    'payload'   => $p,
                ];
            });

        // SUCESSOS (histórico)
        if (Schema::hasTable('queue_job_history')) {
            $this->succeeded = DB::table('queue_job_history')
                ->select('id', 'uuid', 'queue', 'name', 'attempts', 'runtime_ms', 'finished_at')
                ->where('status', 'success')
                ->orderBy('id', 'desc')
                ->limit($this->limitPerList)
                ->get()
                ->map(function ($r) {
                    return (object)[
                        'id'          => $r->id,
                        'uuid'        => $r->uuid,
                        'queue'       => $r->queue,
                        'name'        => $r->name,
                        'attempts'    => $r->attempts,
                        'runtime_ms'  => $r->runtime_ms,
                        'finished_at' => Carbon::parse($r->finished_at),
                    ];
                });
        } else {
            $this->succeeded = collect();
        }

        // Status do worker
        [$this->workerActive, $this->workerSource] = $this->detectWorkerStatus();

        // Contagem por fila
        $this->queueCounts = $this->computeQueueCounts($nowTs);
    }

    /* ========== Ações ========== */

    public function restartJob($failedId)
    {
        $job = DB::table('failed_jobs')->find($failedId);
        if (!$job) {
            session()->flash('error', 'Job falhado não encontrado.');
            return;
        }

        DB::table('failed_jobs')->where('id', $failedId)->delete();

        DB::table('jobs')->insert([
            'queue'        => $job->queue,
            'payload'      => $job->payload,
            'attempts'     => 0,
            'reserved_at'  => null,
            'available_at' => now()->timestamp,
            'created_at'   => now()->timestamp,
        ]);

        session()->flash('message', "Job #{$failedId} reinserido na fila.");
        $this->refreshData();
    }

    public function deleteFailed($failedId)
    {
        DB::table('failed_jobs')->where('id', $failedId)->delete();
        session()->flash('message', "Job falhado #{$failedId} removido.");
        $this->refreshData();
    }

    public function retryAllFailed()
    {
        $failed = DB::table('failed_jobs')->select('id', 'queue', 'payload')->limit(1000)->get();
        foreach ($failed as $job) {
            DB::table('jobs')->insert([
                'queue'        => $job->queue,
                'payload'      => $job->payload,
                'attempts'     => 0,
                'reserved_at'  => null,
                'available_at' => now()->timestamp,
                'created_at'   => now()->timestamp,
            ]);
            DB::table('failed_jobs')->where('id', $job->id)->delete();
        }
        session()->flash('message', "Reenfileirados ".count($failed)." jobs falhados.");
        $this->refreshData();
    }

    /* ========== Accessors / Helpers ========== */

    // Accessor para usar na view sem variável solta
    public function getRunningThresholdMinutesProperty(): int
    {
        return (int) floor($this->runningThresholdSeconds / 60);
    }

    private function decorateJobRow($j, bool $isRunning = false)
    {
        $p = json_decode($j->payload, true) ?: [];
        $name = $p['displayName'] ?? ($p['data']['commandName'] ?? '—');

        $createdAt   = is_numeric($j->created_at) ? Carbon::createFromTimestamp($j->created_at) : Carbon::parse($j->created_at);
        $availableAt = is_numeric($j->available_at) ? Carbon::createFromTimestamp($j->available_at) : Carbon::parse($j->available_at);
        $reservedAt  = $j->reserved_at ? (is_numeric($j->reserved_at) ? Carbon::createFromTimestamp($j->reserved_at) : Carbon::parse($j->reserved_at)) : null;

        return (object)[
            'id'           => $j->id,
            'queue'        => $j->queue,
            'name'         => $name,
            'attempts'     => $j->attempts,
            'created_at'   => $createdAt,
            'available_at' => $availableAt,
            'reserved_at'  => $reservedAt,
            'is_running'   => $isRunning,
            'payload'      => $p,
        ];
    }

    private function shortException(string $ex, int $max = 240): string
    {
        $clean = preg_replace('/\s+/', ' ', $ex);
        return mb_strimwidth($clean, 0, $max, '…', 'UTF-8');
    }

    private function computeQueueCounts(int $nowTs): array
    {
        $rows = DB::table('jobs')
            ->select('queue', 'reserved_at', 'available_at', DB::raw('count(*) as qty'))
            ->groupBy('queue', 'reserved_at', 'available_at')
            ->get();

        $byQueue = [];

        foreach ($rows as $row) {
            $queue = $row->queue ?? 'default';
            $type = 'pending';
            if (!is_null($row->reserved_at) && $row->reserved_at >= ($nowTs - $this->runningThresholdSeconds)) {
                $type = 'running';
            } elseif ($row->available_at > $nowTs) {
                $type = 'delayed';
            }

            if (!isset($byQueue[$queue])) {
                $byQueue[$queue] = ['queue' => $queue, 'pending' => 0, 'running' => 0, 'delayed' => 0];
            }
            $byQueue[$queue][$type] += $row->qty;
        }

        return collect($byQueue)->sortBy('queue')->values()->all();
    }

    private function detectWorkerStatus(): array
    {
        // 1) supervisorctl
        $which = @shell_exec('command -v supervisorctl 2>/dev/null');
        if ($which) {
            $out = @shell_exec('supervisorctl status 2>/dev/null');
            if ($out && preg_match('/RUNNING/i', $out)) {
                return [true, 'supervisorctl'];
            }
        }

        // 2) pgrep
        $pg = @shell_exec('pgrep -af "php.*artisan.*queue:work" 2>/dev/null');
        if ($pg && trim($pg) !== '') {
            return [true, 'pgrep'];
        }

        // 3) Heurística por reservas recentes
        $recentReserved = DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '>=', now()->timestamp - $this->runningThresholdSeconds)
            ->exists();

        return [$recentReserved, 'heurística'];
    }
}
