<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\SicodeSql\LogNoteInformFlows;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncNoteInformFlowsToSqlServer extends Command
{
    use ShowsProgress;

    private const SQLSERVER_BIND_LIMIT = 2000;
    private const SQLSERVER_BIND_BUFFER = 50;

    private array $columnLimits = [];

    protected $signature = 'sicode:sync-log-note-inform-flows
        {--hours=2 : Janela de horas olhando updated_at}
        {--all : Sincroniza todos os registros}
        {--chunk=500 : Tamanho do lote lido do MySQL}
        {--if-empty : Faz sync completo se a tabela destino estiver vazia}
        {--dry-run : Só simula, sem gravar no SQL Server}
        {--force : Permite executar fora de produção}';

    protected $description = 'Sincroniza note_inform_flows para SQL Server (dbo.log_note_inform_flows_sync).';

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

        $destConn = (new LogNoteInformFlows())->getConnectionName();
        $destIsEmpty = false;

        if ($this->option('if-empty')) {
            try {
                $destIsEmpty = LogNoteInformFlows::query()->count() === 0;
            } catch (\Throwable) {
                $destIsEmpty = true;
            }
        }

        $full = (bool) $this->option('all') || $destIsEmpty;

        $this->info('Sync Note Inform Flows -> SQL Server');
        $this->line("Destino: {$destConn}.dbo.log_note_inform_flows_sync");
        $this->line('Modo: ' . ($full ? 'FULL' : "INCREMENTAL (updated_at >= {$since->toDateTimeString()})"));
        $this->newLine();

        $this->loadColumnLimits();

        $query = DB::table('note_inform_flows as nif')
            ->leftJoin('companies as c', 'c.id', '=', 'nif.company_id')
            ->leftJoin('users as fu', 'fu.id', '=', 'nif.fiscal_user_id')
            ->leftJoin('companies as fuc', 'fuc.id', '=', 'fu.company_id')
            ->leftJoin('productions as mp', 'mp.id', '=', 'nif.measurement_production_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'mp.user_id')
            ->leftJoin('companies as puc', 'puc.id', '=', 'pu.company_id')
            ->when(!$full, fn ($q) => $q->where('nif.updated_at', '>=', $since))
            ->orderBy('nif.id')
            ->select([
                'nif.*',
                'c.name as company_informe_name',
                'fuc.name as fiscal_user_company_name',
                'pu.name as payment_user_name',
                'puc.name as payment_user_company_name',
            ]);

        $total = (clone $query)->count();

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

            $query->chunkById($chunk, function (Collection $rows) use (&$sent, &$written, $bar, $dryRun) {
                $payload = [];
                $columnsPerRow = 0;

                foreach ($rows as $row) {
                    $mapped = $this->mapRow($row);
                    $columnsPerRow = $columnsPerRow ?: count($mapped);
                    $payload[] = $mapped;
                    $sent++;

                    $bar->setMessage("id_local={$row->id}");
                    $bar->advance();
                }

                if ($dryRun || empty($payload)) {
                    return;
                }

                foreach (array_chunk($payload, $this->safeBatchSizeForSqlServer($columnsPerRow)) as $batch) {
                    LogNoteInformFlows::query()->upsert(
                        $batch,
                        ['id_local'],
                        $this->upsertColumns()
                    );

                    $written += count($batch);
                }
            }, 'nif.id', 'id');

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
            report($e);

            return self::FAILURE;
        }
    }

    private function mapRow(object $row): array
    {
        return $this->truncateRow([
            'id_local' => (int) $row->id,
            'flow_key' => $row->flow_key,
            'flow_type' => $row->flow_type,
            'note_number' => $row->note_number,
            'ovi' => $row->ovi,
            'order_number' => $row->order_number,
            'company_informe_name' => $row->company_informe_name,
            'informed_at' => $row->informed_at,
            'inform_type' => $row->inform_type,
            'is_validated_by_publication' => (bool) $row->is_validated_by_publication,
            'publication_validated_at' => $row->publication_validated_at,
            'has_ads' => (bool) $row->has_ads,
            'ads_sent_at' => $row->ads_sent_at,
            'ads_type' => $row->ads_type,
            'ads_is_tacit' => (bool) $row->ads_is_tacit,
            'fiscalization_entered_at' => $row->fiscalization_entered_at,
            'fiscalization_type' => $row->fiscalization_type,
            'fiscal_assigned_at' => $row->fiscal_assigned_at,
            'fiscal_user_name' => $row->fiscal_user_name,
            'fiscal_user_company_name' => $row->fiscal_user_company_name,
            'fiscalization_completed_at' => $row->fiscalization_completed_at,
            'fiscalization_closed_in_sicode' => (bool) $row->fiscalization_closed_in_sicode,
            'fiscalization_closed_in_sicode_at' => $row->fiscalization_closed_in_sicode_at,
            'fiscalization_closed_in_sap' => (bool) $row->fiscalization_closed_in_sap,
            'fiscalization_closed_in_sap_at' => $row->fiscalization_closed_in_sap_at,
            'baixa_fiscal_status' => $row->baixa_fiscal_status,
            'has_d5' => (bool) $row->has_d5,
            'five_note_number' => $row->five_note_number,
            'five_note_created_at' => $row->five_note_created_at,
            'measurement_entered_at' => $row->measurement_entered_at,
            'measurement_type' => $row->measurement_type,
            'measurement_completed_at' => $row->measurement_completed_at,
            'measurement_exited_at' => $row->measurement_exited_at,
            'baixa_measurement_status' => $row->baixa_measurement_status,
            'payment_user_name' => $row->payment_user_name,
            'payment_user_company_name' => $row->payment_user_company_name,
            'final_cycle_started_at' => $row->final_cycle_started_at,
            'final_cycle_ended_at' => $row->final_cycle_ended_at,
            'current_stage' => $row->current_stage,
            'blocking_reason' => $row->blocking_reason,
            'active' => (bool) $row->active,
            'source_created_at' => $row->source_created_at,
            'source_updated_at' => $row->source_updated_at,
            'calculated_at' => $row->calculated_at,
            'resolver_payload' => $row->resolver_payload,
            'synced_at' => now(),
        ]);
    }

    private function upsertColumns(): array
    {
        return [
            'flow_key',
            'flow_type',
            'note_number',
            'ovi',
            'order_number',
            'company_informe_name',
            'informed_at',
            'inform_type',
            'is_validated_by_publication',
            'publication_validated_at',
            'has_ads',
            'ads_sent_at',
            'ads_type',
            'ads_is_tacit',
            'fiscalization_entered_at',
            'fiscalization_type',
            'fiscal_assigned_at',
            'fiscal_user_name',
            'fiscal_user_company_name',
            'fiscalization_completed_at',
            'fiscalization_closed_in_sicode',
            'fiscalization_closed_in_sicode_at',
            'fiscalization_closed_in_sap',
            'fiscalization_closed_in_sap_at',
            'baixa_fiscal_status',
            'has_d5',
            'five_note_number',
            'five_note_created_at',
            'measurement_entered_at',
            'measurement_type',
            'measurement_completed_at',
            'measurement_exited_at',
            'baixa_measurement_status',
            'payment_user_name',
            'payment_user_company_name',
            'final_cycle_started_at',
            'final_cycle_ended_at',
            'current_stage',
            'blocking_reason',
            'active',
            'source_created_at',
            'source_updated_at',
            'calculated_at',
            'resolver_payload',
            'synced_at',
        ];
    }

    private function safeBatchSizeForSqlServer(int $columnsPerRow): int
    {
        if ($columnsPerRow <= 0) {
            return 1;
        }

        $maxByBinds = (int) floor((self::SQLSERVER_BIND_LIMIT - self::SQLSERVER_BIND_BUFFER) / $columnsPerRow);
        return max(1, $maxByBinds);
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
                ->where('TABLE_NAME', 'log_note_inform_flows_sync')
                ->get();

            foreach ($rows as $row) {
                $limit = (int) ($row->max_length ?? 0);
                if ($limit > 0) {
                    $this->columnLimits[(string) $row->column] = $limit;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel carregar metadados de tamanho de log_note_inform_flows_sync.', [
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
            if ($limit > 0 && mb_strlen($value) > $limit) {
                $row[$key] = mb_substr($value, 0, $limit);
            }
        }

        return $row;
    }
}

