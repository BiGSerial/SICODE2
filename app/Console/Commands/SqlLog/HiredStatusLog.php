<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\HiringStatus\HiringStatusBuilder;
use App\Models\SicodeSql\HiringStatus;
use App\Models\Note;

class HiredStatusLog extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:log_hired_status {--full}';
    protected $description = 'Reprocessa e atualiza o status de contratação para registros já existentes';

    private HiringStatusBuilder $builder;

    public function __construct(HiringStatusBuilder $builder)
    {
        parent::__construct();
        $this->builder = $builder;
    }

    public function handle(): void
    {
        // pega só os notes que ainda não estão marcados como CONTRATADO
        $query = HiringStatus::query();
        if ($this->option('full') !== true) {
            $query->where('position', '!=', 'CONTRATADO');
        }
        $noteIds = $query->pluck('note_id')->toArray();

        $total = count($noteIds);
        $bar = $this->createProgressBar($total);
        $bar->start();

        // essas são as 10 colunas que o MERGE/VALUES sempre vai usar
        $insertCols = [
            'created_at',
            'dt_status',
            'last_date',
            'note',
            'note_id',
            'position',
            'register',
            'responsible',
            'tacit',
            'local',
            'rubrica',
            'updated_at',
        ];

        foreach (array_chunk($noteIds, 100) as $chunk) {
            $notes = Note::with([
                'approval.reclaims.service',
                'approval.reclaims.production.user',
                'waitings.reclaim.service',
                'waitings.reclaim.production.user',
                'viabilities' => fn ($q) => $q->with([
                    'reclaims.service',
                    'reclaims.production.user',
                    'company',
                    'user',
                    'orders.operations' => fn ($q2) => $q2
                        ->where('operacao', '0010')
                        ->where('status', 'like', 'CONF%'),
                ]),
            ])
            ->whereIn('id', $chunk)
            ->get();

            DB::transaction(function () use ($notes, $insertCols) {
                $batch = $this->builder->batchBuild($notes);
                if (empty($batch)) {
                    return;
                }

                // normaliza cada linha para ter sempre as mesmas 10 colunas
                $normalized = array_map(function (array $row) use ($insertCols) {
                    $fixed = [];
                    foreach ($insertCols as $col) {
                        $fixed[$col] = $row[$col] ?? null;
                    }
                    return $fixed;
                }, $batch);

                // SQL Server só aceita até 2100 parâmetros por vez:
                $numCols = count($insertCols);
                $maxRows = intdiv(2100, $numCols);

                foreach (array_chunk($normalized, $maxRows) as $subBatch) {
                    HiringStatus::upsert(
                        $subBatch,
                        ['note_id'], // chave única
                        ['note', 'dt_status', 'last_date', 'position', 'register', 'responsible', 'tacit', 'local', 'rubrica'] // colunas a serem atualizadas
                    );
                }
            });

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->info("\n\nReprocessamento concluído com sucesso!");
    }
}
