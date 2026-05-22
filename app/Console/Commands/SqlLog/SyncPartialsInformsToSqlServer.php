<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Partial;
use App\Models\SicodeSql\LogPartialsInforms;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPartialsInformsToSqlServer extends Command
{
    use ShowsProgress;

    private array $columnLimits = [];

    protected $signature = 'sicode:sync-log-partials-informs
        {--hours=2 : Janela de horas olhando updated_at}
        {--all : Sincroniza todos os registros}
        {--chunk=500 : Tamanho do lote lido do MySQL}
        {--if-empty : Faz sync completo se a tabela destino estiver vazia}
        {--dry-run : Só simula, sem gravar no SQL Server}
        {--force : Permite executar fora de produção}';

    protected $description = 'Sincroniza Partial para SQL Server (dbo.log_partials_informs).';

    public function handle(): int
    {
        if ((env('APP_QA') || env('APP_ENV') === 'local') && !$this->option('force')) {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NÃO É AMBIENTE DE PRODUÇÃO, ABORTANDO LOG PARA SQL SERVER </>');
            $this->line('Use --dry-run --force para validar a leitura local sem gravar, ou --force se este ambiente tiver acesso ao SQL Server.');

            return self::SUCCESS;
        }

        $hours = max(1, (int) $this->option('hours'));
        $chunk = max(50, (int) $this->option('chunk'));
        $since = now()->subHours($hours);
        $dryRun = (bool) $this->option('dry-run');

        $destConn = (new LogPartialsInforms())->getConnectionName();
        $destIsEmpty = false;

        if ($this->option('if-empty')) {
            try {
                $destIsEmpty = LogPartialsInforms::query()->count() === 0;
            } catch (\Throwable $e) {
                $destIsEmpty = true;
            }
        }

        $full = (bool) $this->option('all') || $destIsEmpty;

        $this->info('Sync Partials/Informs -> SQL Server');
        $this->line("Destino: {$destConn}.dbo.log_partials_informs");
        $this->line('Modo: ' . ($full ? 'FULL' : "INCREMENTAL (updated_at >= {$since->toDateTimeString()})"));
        $this->newLine();

        $this->loadColumnLimits();

        $query = Partial::query()
            ->with([
                'Note:id,note',
                'company:id,name',
                'user:id,name,company_id',
                'user.Company:id,name',
                'engineer:id,name,company_id',
                'engineer.Company:id,name',
                'supervisor:id,name,company_id',
                'supervisor.Company:id,name',
                'payer:id,name,company_id',
                'payer.Company:id,name',
            ])
            ->when(!$full, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id');

        $total = (int) $query->count();

        if ($total === 0) {
            $this->info('Nada para sincronizar.');
            return self::SUCCESS;
        }

        $this->line("Registros para enviar: {$total}");

        $bar = $this->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %message%');
        $bar->setMessage('Preparando...');
        $bar->start();

        $sent = 0;
        $written = 0;

        try {
            DB::disableQueryLog();

            $query->chunkById($chunk, function ($partials) use (&$sent, &$written, $bar, $dryRun) {
                $rows = [];
                $columnsPerRow = 0;

                foreach ($partials as $partial) {
                    $row = $this->mapPartialToSqlRow($partial);
                    $columnsPerRow = $columnsPerRow ?: count($row);
                    $rows[] = $row;
                    $sent++;

                    $bar->setMessage("partial_id={$partial->id}");
                    $bar->advance();
                }

                if ($dryRun || !$rows) {
                    return;
                }

                foreach (array_chunk($rows, $this->maxRowsPerBatch($columnsPerRow)) as $batch) {
                    LogPartialsInforms::query()->upsert(
                        $batch,
                        ['partial_id'],
                        [
                            'event_type',
                            'note',
                            'company_name',
                            'user_name',
                            'user_company_name',
                            'engineer_name',
                            'engineer_company_name',
                            'supervision_name',
                            'supervision_company_name',
                            'payment_name',
                            'payment_company_name',
                            'observation',
                            'engineer_info',
                            'allow',
                            'deny',
                            'payment',
                            'supervision',
                            'complete',
                            'responsible',
                            'value',
                            'decision_at',
                            'payment_at',
                            'supervision_at',
                            'partial_created_at',
                            'partial_updated_at',
                            'updated_at',
                        ]
                    );

                    $written += count($batch);
                }
            }, 'id');

            $bar->finish();
            $this->newLine(2);
            $this->info('Sync finalizado.');
            $this->line("Preparados: {$sent}" . ($dryRun ? ' (dry-run)' : ''));
            $this->line("Gravados (upsert): {$written}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $bar->clear();
            $this->newLine();
            $this->error('Erro no sync: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function mapPartialToSqlRow(Partial $partial): array
    {
        $now = Carbon::now();

        return $this->truncateRow([
            'partial_id' => (int) $partial->id,
            'event_type' => LogPartialsInforms::EVENT_SYNC,

            'note' => $partial->Note?->note,
            'company_name' => $partial->company?->name,

            'user_name' => $partial->user?->name,
            'user_company_name' => $partial->user?->Company?->name,

            'engineer_name' => $partial->engineer?->name,
            'engineer_company_name' => $partial->engineer?->Company?->name,

            'supervision_name' => $partial->supervisor?->name,
            'supervision_company_name' => $partial->supervisor?->Company?->name,

            'payment_name' => $partial->payer?->name,
            'payment_company_name' => $partial->payer?->Company?->name,

            'observation' => $partial->observation,
            'engineer_info' => $partial->engineer_info,

            'allow' => (bool) $partial->allow,
            'deny' => (bool) $partial->deny,
            'payment' => (bool) $partial->payment,
            'supervision' => (bool) $partial->supervision,
            'complete' => (bool) $partial->complete,

            'responsible' => $partial->responsible,
            'value' => $partial->value,

            'decision_at' => $partial->decision_at,
            'payment_at' => $partial->payment_at,
            'supervision_at' => $partial->supervision_at,

            'partial_created_at' => $partial->created_at,
            'partial_updated_at' => $partial->updated_at,

            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function maxRowsPerBatch(int $columnsPerRow): int
    {
        if ($columnsPerRow <= 0) {
            return 1;
        }

        return max(1, (int) floor(2000 / $columnsPerRow));
    }

    private function loadColumnLimits(): void
    {
        try {
            $rows = DB::connection('sqlsrv2')
                ->table('INFORMATION_SCHEMA.COLUMNS')
                ->select([
                    'COLUMN_NAME as column',
                    'CHARACTER_MAXIMUM_LENGTH as max_length',
                ])
                ->where('TABLE_SCHEMA', 'dbo')
                ->where('TABLE_NAME', 'log_partials_informs')
                ->get();

            foreach ($rows as $row) {
                $limit = (int) ($row->max_length ?? 0);
                if ($limit > 0) {
                    $this->columnLimits[(string) $row->column] = $limit;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel carregar metadados de tamanho de log_partials_informs.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function truncateRow(array $row): array
    {
        if (!$this->columnLimits) {
            return $row;
        }

        foreach ($row as $key => $value) {
            if (!isset($this->columnLimits[$key]) || !is_string($value)) {
                continue;
            }

            $limit = $this->columnLimits[$key];
            if ($this->stringLength($value) > $limit) {
                $row[$key] = $this->stringSubstr($value, 0, $limit);
            }
        }

        return $row;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function stringSubstr(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
    }
}
