<?php

namespace App\Services\System;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Dados do Monitor de Fila (Administração → Sistema → Jobs).
 *
 * Classificação dos jobs da tabela `jobs` (driver database):
 *  - Em execução: `reserved_at` preenchido. Passou de `retry_after` sem terminar = "longo" (o worker pode devolvê-lo à fila).
 *  - Pendente: sem reserva e `available_at` <= agora (o tempo de espera é agora - available_at).
 *  - Agendado: sem reserva e `available_at` > agora.
 */
class QueueMonitorService
{
    public const LIST_LIMIT = 25;

    /** Quantos falhados recentes são lidos para agrupar por tipo de job. */
    private const FAILED_GROUP_SAMPLE = 500;

    public function retryAfterSeconds(): int
    {
        return (int) config('queue.connections.' . config('queue.default') . '.retry_after', 90);
    }

    public function snapshot(?int $nowTs = null): array
    {
        $nowTs ??= now()->timestamp;
        $retryAfter = $this->retryAfterSeconds();

        $workers = $this->supervisorWorkers();
        $queues  = $this->queues($nowTs, $retryAfter);

        $totals = [
            'pending'     => array_sum(array_column($queues, 'pending')),
            'running'     => array_sum(array_column($queues, 'running')),
            'delayed'     => array_sum(array_column($queues, 'delayed')),
            'failed'      => array_sum(array_column($queues, 'failed')),
            'long'        => array_sum(array_column($queues, 'long')),
            'oldest_wait' => collect($queues)->max('oldest_wait'),
        ];

        return [
            'now'            => Carbon::createFromTimestamp($nowTs),
            'retry_after'    => $retryAfter,
            'totals'         => $totals,
            'queues'         => $queues,
            'workers'        => $workers,
            'worker_summary' => $this->workerSummary($workers),
            'pending'        => $this->jobList('pending', $nowTs, $retryAfter),
            'running'        => $this->jobList('running', $nowTs, $retryAfter),
            'delayed'        => $this->jobList('delayed', $nowTs, $retryAfter),
            'failed'         => $this->failedList(),
            'failed_by_type' => $this->failedByType(),
            'succeeded'      => $this->succeededList(),
        ];
    }

    /* ========== Filas ========== */

    private function queues(int $nowTs, int $retryAfter): array
    {
        $rows = DB::table('jobs')
            ->select('queue', 'reserved_at', 'available_at', DB::raw('count(*) as qty'))
            ->groupBy('queue', 'reserved_at', 'available_at')
            ->get();

        $by    = [];
        $blank = fn (string $q) => ['queue' => $q, 'pending' => 0, 'running' => 0, 'delayed' => 0, 'failed' => 0, 'long' => 0, 'oldest_wait' => 0];

        foreach ($rows as $row) {
            $q = $row->queue ?: 'default';
            $by[$q] ??= $blank($q);

            if (!is_null($row->reserved_at)) {
                $by[$q]['running'] += $row->qty;

                if ($row->reserved_at < $nowTs - $retryAfter) {
                    $by[$q]['long'] += $row->qty;
                }
            } elseif ($row->available_at > $nowTs) {
                $by[$q]['delayed'] += $row->qty;
            } else {
                $by[$q]['pending'] += $row->qty;
                $by[$q]['oldest_wait'] = max($by[$q]['oldest_wait'], $nowTs - $row->available_at);
            }
        }

        foreach (DB::table('failed_jobs')->select('queue', DB::raw('count(*) as qty'))->groupBy('queue')->get() as $row) {
            $q = $row->queue ?: 'default';
            $by[$q] ??= $blank($q);
            $by[$q]['failed'] += $row->qty;
        }

        return collect($by)->sortBy('queue')->values()->all();
    }

    /* ========== Listas ========== */

    /** @return array{total:int, rows:Collection} */
    private function jobList(string $kind, int $nowTs, int $retryAfter): array
    {
        $query = DB::table('jobs')->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at');

        match ($kind) {
            'running' => $query->whereNotNull('reserved_at'),
            'delayed' => $query->whereNull('reserved_at')->where('available_at', '>', $nowTs),
            default   => $query->whereNull('reserved_at')->where('available_at', '<=', $nowTs),
        };

        $total = (clone $query)->count();

        $query->orderBy($kind === 'running' ? 'reserved_at' : 'available_at');

        $rows = $query->limit(self::LIST_LIMIT)->get()->map(function ($j) use ($nowTs, $retryAfter, $kind) {
            $payload = json_decode($j->payload, true) ?: [];
            $since   = $kind === 'running' ? $j->reserved_at : $j->available_at;

            return (object) [
                'id'       => $j->id,
                'queue'    => $j->queue ?: 'default',
                'name'     => $this->jobName($payload),
                'short'    => class_basename($this->jobName($payload)),
                'attempts' => (int) $j->attempts,
                'at'       => Carbon::createFromTimestamp($since),
                'seconds'  => $kind === 'delayed' ? $since - $nowTs : $nowTs - $since,
                'long'     => $kind === 'running' && $j->reserved_at < $nowTs - $retryAfter,
                'payload'  => $payload,
            ];
        });

        return ['total' => $total, 'rows' => $rows];
    }

    /** @return array{total:int, rows:Collection} */
    private function failedList(): array
    {
        $total = DB::table('failed_jobs')->count();

        $rows = DB::table('failed_jobs')
            ->select('id', 'uuid', 'queue', 'payload', 'exception', 'failed_at')
            ->orderByDesc('id')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(function ($j) {
                $payload           = json_decode($j->payload, true) ?: [];
                [$class, $message] = $this->splitException((string) $j->exception);

                return (object) [
                    'id'          => $j->id,
                    'queue'       => $j->queue ?: 'default',
                    'name'        => $this->jobName($payload),
                    'short'       => class_basename($this->jobName($payload)),
                    'error_class' => $class,
                    'error'       => $message,
                    'failed_at'   => Carbon::parse($j->failed_at),
                    'payload'     => $payload,
                ];
            });

        return ['total' => $total, 'rows' => $rows];
    }

    /** Falhados agrupados por tipo de job (amostra dos mais recentes). */
    private function failedByType(): array
    {
        return DB::table('failed_jobs')
            ->select('payload')
            ->orderByDesc('id')
            ->limit(self::FAILED_GROUP_SAMPLE)
            ->get()
            ->map(fn ($j) => class_basename($this->jobName(json_decode($j->payload, true) ?: [])))
            ->countBy()
            ->sortDesc()
            ->take(6)
            ->map(fn (int $qty, string $name) => ['name' => $name, 'qty' => $qty])
            ->values()
            ->all();
    }

    private function succeededList(): array
    {
        if (!Schema::hasTable('queue_job_history')) {
            return ['enabled' => false, 'rows' => collect()];
        }

        $rows = DB::table('queue_job_history')
            ->select('id', 'uuid', 'queue', 'name', 'attempts', 'runtime_ms', 'finished_at')
            ->where('status', 'success')
            ->orderByDesc('id')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn ($r) => (object) [
                'id'          => $r->id,
                'queue'       => $r->queue ?: 'default',
                'name'        => $r->name,
                'short'       => class_basename((string) $r->name),
                'attempts'    => $r->attempts,
                'runtime_ms'  => $r->runtime_ms,
                'finished_at' => Carbon::parse($r->finished_at),
            ]);

        return ['enabled' => true, 'rows' => $rows];
    }

    /* ========== Workers (supervisor) ========== */

    public function supervisorWorkers(): array
    {
        return collect($this->supervisorStatusLines())
            ->map(fn (string $line) => $this->parseSupervisorStatusLine($line))
            ->filter()
            ->filter(fn (array $row) => str_contains(strtolower($row['name']), 'worker'))
            ->values()
            ->all();
    }

    private function workerSummary(array $workers): array
    {
        $running = collect($workers)->where('running', true)->count();

        if ($workers) {
            return ['source' => 'supervisorctl', 'active' => $running > 0, 'running' => $running, 'total' => count($workers)];
        }

        // Sem supervisor (ex.: dev local): pgrep, depois reservas recentes.
        $pg = @shell_exec('pgrep -af "php.*artisan.*queue:work" 2>/dev/null');

        if ($pg && trim($pg) !== '') {
            return ['source' => 'pgrep', 'active' => true, 'running' => null, 'total' => null];
        }

        $recent = DB::table('jobs')->whereNotNull('reserved_at')->where('reserved_at', '>=', now()->timestamp - $this->retryAfterSeconds())->exists();

        return ['source' => 'heurística', 'active' => $recent, 'running' => null, 'total' => null];
    }

    private function supervisorStatusLines(): array
    {
        $process = new Process(['supervisorctl', 'status']);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return [];
        }

        // `supervisorctl status` devolve código != 0 quando há processo parado, mas a saída continua válida.
        $output = trim($process->getOutput());

        return $output === '' ? [] : array_values(array_filter(array_map('trim', explode("\n", $output))));
    }

    public function parseSupervisorStatusLine(string $line): ?array
    {
        if (!preg_match('/^(\S+)\s+([A-Z]+)\s*(.*)$/', trim($line), $m)) {
            return null;
        }

        $description = trim($m[3] ?? '');
        preg_match('/\bpid\s+(\d+)/i', $description, $pid);
        preg_match('/\buptime\s+(.+)$/i', $description, $uptime);

        $name    = $m[1];
        $process = str_contains($name, ':') ? substr($name, strpos($name, ':') + 1) : $name;
        preg_match('/worker-([a-z0-9]+)/i', $process, $queue);

        return [
            'name'        => $name,
            'process'     => $process,
            'queue'       => $queue[1] ?? null,
            'status'      => $m[2],
            'description' => $description,
            'pid'         => $pid[1] ?? null,
            'uptime'      => $uptime[1] ?? null,
            'running'     => $m[2] === 'RUNNING',
        ];
    }

    /* ========== Helpers ========== */

    private function jobName(array $payload): string
    {
        return (string) ($payload['displayName'] ?? data_get($payload, 'data.commandName', '—'));
    }

    /** @return array{0:string,1:string} classe da exceção e mensagem (1ª linha, sem stack trace) */
    private function splitException(string $exception): array
    {
        $first = trim(strtok($exception, "\n") ?: '');
        $first = preg_replace('/\s+/', ' ', $first);

        if (preg_match('/^([\w\\\\]+(?:Exception|Error)):\s*(.*)$/s', $first, $m)) {
            return [class_basename($m[1]), mb_strimwidth($m[2], 0, 300, '…', 'UTF-8')];
        }

        return ['', mb_strimwidth($first, 0, 300, '…', 'UTF-8')];
    }

    /** Duração legível em PT-BR (ex.: "2 min", "1 h 05 min"). */
    public static function human(int $seconds): string
    {
        $seconds = abs($seconds);

        return match (true) {
            $seconds < 60    => $seconds . ' s',
            $seconds < 3600  => intdiv($seconds, 60) . ' min',
            $seconds < 86400 => intdiv($seconds, 3600) . ' h ' . str_pad((string) intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT) . ' min',
            default          => intdiv($seconds, 86400) . ' d ' . intdiv($seconds % 86400, 3600) . ' h',
        };
    }
}
