<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\HiringRepository;
use App\Services\HiringStatus\HiringStatusBuilder;
use App\Models\SicodeSql\HiringStatus;

class HiringStatusLog extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_hiring_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o log de status da contratação';

    private HiringRepository $hiringRepository;
    private HiringStatusBuilder $builder;

    public function __construct(HiringRepository $hiringRepository, HiringStatusBuilder $builder)
    {
        parent::__construct();
        $this->hiringRepository = $hiringRepository;
        $this->builder = $builder;
    }

    public function handle(): void
    {
        // Prepara a query com eager-loading das relações necessárias
        $query = $this->hiringRepository->getBaseQuery()
            ->with([
                'approval.reclaims.service',
                'approval.reclaims.production.user',
                'waitings.reclaim.service',
                'waitings.reclaim.production.user',
                'viabilities' => function ($q) {
                    $q->with([
                        'reclaims.service',
                        'reclaims.production.user',
                        'company',
                        'user',
                        'orders.operations' => function ($q2) {
                            $q2->where('operacao', '0010')
                               ->where('status', 'like', 'CONF%');
                        },
                    ]);
                },
            ]);

        $total = $query->count();
        $bar = $this->createProgressBar($total);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        // Processa em lotes de 200 notas
        $query->chunkById(100, function ($notes) use ($bar) {
            DB::transaction(function () use ($notes) {
                // Recalcula o batch via builder
                $batch = $this->builder->batchBuild($notes);
                if (empty($batch)) {
                    return;
                }

                // Normaliza cada linha para ter mesmas colunas
                $columns = array_keys($batch[0]);
                foreach ($batch as &$row) {
                    $row = array_merge(array_fill_keys($columns, null), $row);
                }
                unset($row);

                // Define tamanho máximo de sub-lote para não exceder 2100 parâmetros
                $numCols = count($columns);
                $maxRows = intdiv(2100, $numCols);

                // Executa upsert em sub-lotes
                foreach (array_chunk($batch, $maxRows) as $subBatch) {

                    // Logar o subBatch antes do upsert
                    Log::debug('SubBatch a ser inserido/atualizado:', $subBatch);

                    // Capturar a query SQL gerada
                    DB::listen(function ($query) {
                        Log::info("SQL Query: {$query->sql}");
                        Log::info("SQL Bindings: " . json_encode($query->bindings));
                        Log::info("SQL Time: {$query->time}ms");
                    });

                    HiringStatus::upsert(
                        $subBatch,
                        ['note_id'],
                        ['note', 'dt_status', 'last_date', 'position', 'register', 'responsible', 'tacit', 'local', 'rubrica']
                    );
                }
            });

            // Avança a barra pelo número de notas processadas
            $bar->advance(count($notes));
        });

        $bar->finish();
        $this->info("\n\nLog de status atualizado com sucesso!");
    }
}
