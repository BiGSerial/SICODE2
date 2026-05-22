<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Adsform;
use App\Models\SicodeSql\LogAdsInforms;
use Illuminate\Console\Command;

class InformsAdsLog extends Command
{
    use ShowsProgress;

    private const SQLSERVER_BIND_LIMIT = 2100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:informs-ads-log
                            {--days=1 : Quantidade de dias para buscar atualizações incrementais}
                            {--full : Força processamento completo de todos os registros}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia os dados do ADS para o log de informativos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $removedLogs = $this->syncDeletedAdsLogs();

        $daysOption = (int) $this->option('days');
        $days = $daysOption > 0 ? $daysOption : 1;
        $forceFull = (bool) $this->option('full');
        $full = $forceFull || !(LogAdsInforms::count() > 0);
        $now = now();
        $basePayload = [
            'adsform_id' => null,
            'work_report_id' => null,
            'note_id' => null,
            'note' => null,
            'user_name' => null,
            'name' => null,
            'obs' => null,
            'contract' => null,
            'center' => null,
            'deposit' => null,
            'amount' => null,
            'tacit' => null,
            'tacit_due_at' => null,
            'tacit_delivered_at' => null,
            'date' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
        $limitBatch = $this->safeBatchSizeForSqlServer(count($basePayload), 100);

        $query = Adsform::query()
            ->with(['note:id,note', 'user:id,name'])
            ->when(!$full, function ($query) use ($days) {
                return $query->where('updated_at', '>=', now()->subDays($days));
            });

        $totalSteps = (clone $query)->count();

        if ($totalSteps == 0) {
            $this->info('Nenhum registro de ADS encontrado para envio.');
            if ($removedLogs > 0) {
                $this->warn("Registros removidos do log SQL por exclusão local: {$removedLogs}");
            }
            return;
        }

        if ($full) {
            $this->info('Modo geral habilitado: enviando todos os registros de ADS.');
        } else {
            $this->info("Modo incremental: enviando atualizações dos últimos {$days} dia(s).");
        }

        // Configure o ProgressBar
        $bar = $this->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();


        $query
            ->chunk(1000, function ($adsforms) use ($bar, $full, $limitBatch, $now) {

                $dataBatch = [];
                foreach ($adsforms as $adsform) {
                    $dataBatch[] = [
                        'adsform_id' => $adsform->id,
                        'work_report_id' => $adsform->work_report_id,
                        'note_id' => $adsform->note_id,
                        'note' => $adsform->note?->note,
                        'user_name' => mb_strtoupper($adsform->user?->name ?? ''),
                        'name' => strtoupper($adsform->name ?? ''),
                        'obs' => $adsform->obs,
                        'contract' => $adsform->contract,
                        'center' => $adsform->center,
                        'deposit' => $adsform->deposit,
                        'amount' => $adsform->amount,
                        'tacit' => (bool) $adsform->tacit,
                        'tacit_due_at' => $adsform->tacit_due_at,
                        'tacit_delivered_at' => $adsform->tacit_delivered_at,
                        'date' => $adsform->created_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Atualiza a barra de progresso
                    $bar->setMessage('Processando...');
                    $bar->advance();
                }

                if ($full) {
                    foreach (array_chunk($dataBatch, $limitBatch) as $batch) {
                        LogAdsInforms::insert($batch);
                    }
                } else {
                    foreach (array_chunk($dataBatch, $limitBatch) as $batch) {
                        $upsertRows = array_map(function (array $row) {
                            unset($row['created_at']);
                            return $row;
                        }, $batch);

                        LogAdsInforms::upsert(
                            $upsertRows,
                            ['adsform_id', 'work_report_id', 'note_id'],
                            [
                                'note',
                                'user_name',
                                'name',
                                'obs',
                                'contract',
                                'center',
                                'deposit',
                                'amount',
                                'tacit',
                                'tacit_due_at',
                                'tacit_delivered_at',
                                'date',
                                'updated_at',
                            ]
                        );
                    }
                }
            });

        if ($removedLogs > 0) {
            $this->warn("Registros removidos do log SQL por exclusão local: {$removedLogs}");
        }
    }

    private function safeBatchSizeForSqlServer(int $columnsPerRow, int $defaultBatch): int
    {
        if ($columnsPerRow <= 0) {
            return 1;
        }

        $maxByBinds = (int) floor((self::SQLSERVER_BIND_LIMIT - 50) / $columnsPerRow);
        $maxByBinds = max(1, $maxByBinds);

        return min($defaultBatch, $maxByBinds);
    }

    private function syncDeletedAdsLogs(): int
    {
        $removed = 0;

        LogAdsInforms::query()
            ->select('id', 'adsform_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$removed) {
                $adsIds = $rows
                    ->pluck('adsform_id')
                    ->filter(fn ($id) => !is_null($id))
                    ->unique()
                    ->values()
                    ->all();

                if (empty($adsIds)) {
                    return;
                }

                $existingAdsIds = Adsform::query()
                    ->whereIn('id', $adsIds)
                    ->pluck('id')
                    ->all();

                $existingMap = array_fill_keys($existingAdsIds, true);

                $toDelete = $rows
                    ->filter(function ($row) use ($existingMap) {
                        return !isset($existingMap[$row->adsform_id]);
                    })
                    ->pluck('id')
                    ->all();

                if (!empty($toDelete)) {
                    $removed += LogAdsInforms::query()->whereIn('id', $toDelete)->delete();
                }
            });

        return $removed;
    }
}
