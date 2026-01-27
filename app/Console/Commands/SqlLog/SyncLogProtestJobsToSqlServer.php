<?php



use App\Models\ProtestJob;
use App\Models\SicodeSql\LogProtestJobs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLogProtestJobsToSqlServer extends Command
{
    protected $signature = 'sicode:sync-log-protest-jobs
        {--hours=24 : Janela de horas olhando updated_at}
        {--all : Sobe tudo (ignora janela)}
        {--chunk=500 : Tamanho do lote por upsert}
        {--force-full : Força full sync}
        {--if-empty : Faz full sync se a tabela destino estiver vazia}
        {--dry-run : Só simula (não grava no SQL Server)}';

    protected $description = 'Sincroniza ProtestJobs para SQL Server (dbo.log_protest_jobs) via upsert (match id_sicode).';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $chunk = max(50, (int) $this->option('chunk'));
        $since = now()->subHours($hours);

        $dryRun = (bool) $this->option('dry-run');

        // conexão do model destino (sqlsrv2)
        $destConn = (new LogProtestJobs())->getConnectionName();

        // Condição para subir tudo quando necessário:
        // - --all
        // - --force-full
        // - --if-empty e destino vazio
        $destIsEmpty = false;
        if ($this->option('if-empty')) {
            try {
                $destIsEmpty = LogProtestJobs::query()->count() === 0;
            } catch (\Throwable $e) {
                // se a tabela estiver inacessível/inexistente, considere vazio
                $destIsEmpty = true;
            }
        }

        $full = (bool) $this->option('all')
            || (bool) $this->option('force-full')
            || $destIsEmpty;

        $this->info('🔄 Sync ProtestJobs → SQL Server');
        $this->line("Destino: {$destConn}.dbo.log_protest_jobs");
        $this->line('Modo: ' . ($full ? 'FULL (tudo)' : "INCREMENTAL (updated_at >= {$since->toDateTimeString()})"));
        $this->newLine();

        $q = ProtestJob::query()
            ->with([
                'protest:id,nota',
                'medProtest:id,med_id,result,protest_type',

                'creator:uuid,id,name,company_id',
                'creator.Company:id,name',

                'owner:uuid,id,name,company_id',
                'owner.Company:id,name',

                'closer:uuid,id,name,company_id',
                'closer.Company:id,name',
            ])
            ->when(!$full, fn ($qq) => $qq->where('updated_at', '>=', $since))
            ->orderBy('updated_at', 'asc');

        $total = (int) $q->count();

        if ($total === 0) {
            $this->info('Nada para sincronizar.');
            return self::SUCCESS;
        }

        $this->line("Registros para enviar: {$total}");

        // Progress bar moderno
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(" %current%/%max%  [%bar%]  %percent:3s%%  ⏱ %elapsed:6s%  💾 %memory:6s%  %message%");
        $bar->setBarCharacter('█');
        $bar->setEmptyBarCharacter('░');
        $bar->setProgressCharacter('▓');
        $bar->setMessage('Preparando...');
        $bar->start();

        $sent = 0;
        $written = 0;

        try {
            // Evita overhead de querylog em lote
            DB::disableQueryLog();

            $q->chunkById($chunk, function ($jobs) use (&$sent, &$written, $bar, $dryRun) {
                $rows = [];

                foreach ($jobs as $job) {
                    $rows[] = $this->mapJobToSqlRow($job);
                    $sent++;
                    $bar->setMessage("Preparando lote... (id_sicode={$job->id})");
                    $bar->advance();
                }

                if ($dryRun) {
                    return;
                }

                // Upsert por id_sicode
                // - uniqueBy: id_sicode
                // - update columns: tudo que pode mudar
                LogProtestJobs::query()->upsert(
                    $rows,
                    ['id_sicode'],
                    [
                        'protest_nota',
                        'med_id',
                        'result',
                        'protest_type',

                        'created_by_name',
                        'created_by_company',
                        'owner_name',
                        'owner_company',
                        'closed_by_name',
                        'closed_by_company',

                        'priority',
                        'status',

                        'sent_at',
                        'accepted_at',
                        'started_at',
                        'finished_at',
                        'closed_at',

                        'sla_due_at',
                        'sla_breached_at',
                        'escalated_at',
                        'escalation_level',

                        'outcome',
                        'close_reason',
                        'notes',

                        'need_evidence',
                        'is_advance',
                        'confirmed',
                        'confirmed_at',
                        'auto',

                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]
                );

                $written += count($rows);
            }, 'id');

            $bar->finish();
            $this->newLine(2);

            $this->info('✅ Sync finalizado');
            $this->line("Preparados: {$sent}" . ($dryRun ? ' (dry-run)' : ''));
            if (!$dryRun) {
                $this->line("Gravados (upsert): {$written}");
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $bar->clear();
            $this->newLine();
            $this->error('❌ Erro no sync: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function mapJobToSqlRow(ProtestJob $job): array
    {
        // protest_type pode ser Enum ou string
        $protestType = $job->medProtest?->protest_type;
        if (is_object($protestType)) {
            $protestType = $protestType->value ?? null;
        }

        // status/priority podem ser Enum ou string
        $status = $job->status;
        if (is_object($status)) {
            $status = $status->value ?? null;
        }

        $priority = $job->priority;
        if (is_object($priority)) {
            $priority = $priority->value ?? null;
        }

        return [
            'id_sicode' => (int) $job->id,

            'protest_nota' => $job->protest?->nota ? (int) $job->protest->nota : null,
            'med_id'       => $job->medProtest?->med_id ? (int) $job->medProtest->med_id : null,

            'result'       => $job->medProtest?->result,
            'protest_type' => $protestType,

            'created_by_name'    => $job->creator?->name,
            'created_by_company' => $job->creator?->Company?->name,

            'owner_name'    => $job->owner?->name,
            'owner_company' => $job->owner?->Company?->name,

            'closed_by_name'    => $job->closer?->name,
            'closed_by_company' => $job->closer?->Company?->name,

            'priority' => $priority,
            'status'   => $status,

            'sent_at'     => $job->sent_at,
            'accepted_at' => $job->accepted_at,
            'started_at'  => $job->started_at,
            'finished_at' => $job->finished_at,
            'closed_at'   => $job->closed_at,

            'sla_due_at'      => $job->sla_due_at,
            'sla_breached_at' => $job->sla_breached_at,

            'escalated_at'     => $job->escalated_at,
            'escalation_level' => $job->escalation_level,

            'outcome'      => $job->outcome ? json_encode($job->outcome, JSON_UNESCAPED_UNICODE) : null,
            'close_reason' => $job->close_reason,
            'notes'        => $job->notes,

            'need_evidence' => (bool) $job->need_evidence,
            'is_advance'    => (bool) $job->is_advance,
            'confirmed'     => (bool) $job->confirmed,
            'confirmed_at'  => $job->confirmed_at,
            'auto'          => (bool) $job->auto,

            // timestamps
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
            'deleted_at' => $job->deleted_at,
        ];
    }
}
