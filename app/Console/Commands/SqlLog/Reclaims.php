<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\{Reclaim, ScheduleExecutionLog};
use App\Models\SicodeSql\ReclaimLog;
use App\Services\Reclaims\ReclaimRowPresenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Reclaims extends Command
{
    use ShowsProgress;

    private const CHUNK_SIZE                 = 100;
    private const EXTRA_COLUMNS              = ['rubrica', 'municipality', 'group5', 'material', 'text', 'status', 'completed'];
    private const FALLBACK_HOURS             = 2;
    private const WATERMARK_LOOKBACK_MINUTES = 5;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:reclaims {--full} {--hours=} {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia para Log os reclaims de retrabalho para o SQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $date = $this->syncSince();

        // Reclaims ativos (os exibidos na tela) são sempre ressincronizados: mudanças em
        // Production/Approvals/etc. não atualizam reclaims.updated_at.
        $scope = fn ($query) => $query->when($date, function ($query) use ($date) {
            return $query->where(function ($q) use ($date) {
                $q->where('reclaims.updated_at', '>=', $date)
                    ->orWhere('reclaims.completed', false);
            });
        });

        $totalSteps = $scope(Reclaim::query())->count();

        $extraColumns = $this->availableExtraColumns();

        $bar = $this->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>');
        $bar->setEmptyBarCharacter('<fg=red>░</>');
        $bar->setProgressCharacter('<fg=green>█</>');
        $bar->setMessage('Iniciando...');
        $bar->start();

        $scope(Reclaim::query())
            ->with(ReclaimRowPresenter::RELATIONS)
            ->chunkById(self::CHUNK_SIZE, function ($reclaims) use (&$bar, $extraColumns) {
                $rows    = [];
                $message = '';

                foreach ($reclaims as $reclaim) {
                    $data = ReclaimRowPresenter::row($reclaim);

                    $row = [
                        'reclaim_id'      => $reclaim->id,
                        'note'            => $data['note'],
                        'origin'          => $data['origin'],
                        'service'         => $reclaim->Service?->service,
                        'category'        => $reclaim->category,
                        'emissor'         => $data['requester'] ?: 'Desconhecido',
                        'company_emissor' => $data['requester_company'] ?: 'Desconhecido',
                        'received_at'     => $reclaim->created_at,
                        'att_at'          => $data['att_at'],
                        'completed_at'    => $reclaim->completed_at,
                        'user'            => $data['responsible'] ?: 'Desconhecido',
                        'company_user'    => $data['responsible_company'] ?: 'Desconhecido',
                    ];

                    $extra = [
                        'rubrica'      => $data['rubrica'],
                        'municipality' => $data['municipality'],
                        'group5'       => $data['group5'],
                        'material'     => $data['material'],
                        'text'         => $data['text'],
                        'status'       => $data['status'],
                        'completed'    => $reclaim->completed ? 1 : 0,
                    ];

                    $rows[]  = $row + array_intersect_key($extra, array_flip($extraColumns));
                    $message = $data['note'] . ' - ' . $reclaim->Service?->service;
                }

                if ($rows) {
                    ReclaimLog::upsert(
                        $rows,
                        ['reclaim_id'],
                        array_values(array_diff(array_keys($rows[0]), ['reclaim_id']))
                    );

                    $bar->setMessage($message);
                    $bar->advance(count($rows));
                }
            }, 'reclaims.id', 'id');

        if ($this->option('full')) {
            $this->purgeDeleted();
        }

        $bar->setMessage('<info>Concluído!</info>');
        $bar->finish();
        $this->output->writeln('');

        if ($missing = array_diff(self::EXTRA_COLUMNS, $extraColumns)) {
            $this->warn('Colunas ausentes em log_reclaims (não sincronizadas): ' . implode(', ', $missing));
            $this->line('Veja docs/sql/log_reclaims_extra_columns.sql');
        }
    }

    /** Colunas opcionais: gravadas apenas se já existirem no SQL Server. */
    private function availableExtraColumns(): array
    {
        try {
            $existing = array_map('strtolower', Schema::connection((new ReclaimLog())->getConnectionName())
                ->getColumnListing((new ReclaimLog())->getTable()));
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter(self::EXTRA_COLUMNS, fn ($c) => in_array($c, $existing, true)));
    }

    /** Remove do SQL Server reclaims que não existem mais no MySQL (só no --full). */
    private function purgeDeleted(): void
    {
        $existing = Reclaim::query()->pluck('id')->all();

        ReclaimLog::query()->pluck('reclaim_id')->chunk(1000)->each(function ($ids) use ($existing) {
            $gone = $ids->diff($existing)->values()->all();

            if ($gone) {
                ReclaimLog::whereIn('reclaim_id', $gone)->delete();
            }
        });
    }

    private function syncSince(): ?string
    {
        if ($this->option('full')) {
            return null;
        }

        if ($this->option('days')) {
            return now()->subDays((int) $this->option('days'))->startOfDay()->format('Y-m-d H:i:s');
        }

        if ($this->option('hours')) {
            return now()->subHours(max(1, (int) $this->option('hours')))->format('Y-m-d H:i:s');
        }

        return $this->lastSuccessfulScheduleStart()
            ?? now()->subHours(self::FALLBACK_HOURS)->format('Y-m-d H:i:s');
    }

    private function lastSuccessfulScheduleStart(): ?string
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return null;
        }

        $lastRun = ScheduleExecutionLog::query()
            ->where('command', 'like', '%sicode:reclaims%')
            ->where('status', ScheduleExecutionLog::STATUS_DONE)
            ->where(function ($query) {
                $query->where('exit_code', 0)
                    ->orWhereNull('exit_code');
            })
            ->whereNotNull('started_at')
            ->orderByDesc('started_at')
            ->first(['started_at']);

        return $lastRun?->started_at
            ? $lastRun->started_at->copy()->subMinutes(self::WATERMARK_LOOKBACK_MINUTES)->format('Y-m-d H:i:s')
            : null;
    }
}
