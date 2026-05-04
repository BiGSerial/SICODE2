<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\RamalReport;
use App\Models\SicodeSql\LogInformsSmc;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InformsSMC extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_informs_smc {--days=0}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza RamalReport para log_informs_smc no SQL Server';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Bloqueia ambientes de QA/local
        if (env('APP_QA') || env('APP_ENV') === 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NÃO É AMBIENTE DE PRODUÇÃO, ABORTANDO </>');
            return;
        }

        $days = (int) $this->option('days');

        // Monta a query de RamalReport
        $query = RamalReport::query();
        if ($days > 0) {
            $query->whereDate('updated_at', '>=', Carbon::now()->subDays($days));
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('<bg=green;fg=white> CONCLUÍDO </> <fg=yellow;options=bold> NENHUM REGISTRO PARA SINCRONIZAR </>');
            return;
        }

        $this->info("<bg=blue;fg=white> INFO </> <fg=white;options=bold> {$total} registros encontrados para sincronização </>");
        $bar = $this->createProgressBar($total);
        $bar->start();

        $upsertBatchSize = 50;
        $now = Carbon::now();

        // Processa em chunks para economizar memória
        $query->chunk(500, function ($chunk) use ($bar, $upsertBatchSize, $now) {
            $payload = [];

            foreach ($chunk as $ramal) {
                $payload[] = [
                    'smc_id'      => $ramal->id,
                    'note'     => $ramal->Note->note,
                    'company'  => $ramal->Company->name,
                    'user'     => $ramal->User->name,
                    'date'        => $ramal->date,
                    'equipment'   => $ramal->equipment,
                    'connection'  => $ramal->connection,
                    'observation' => $ramal->observation,
                    'retry'       => $ramal->retry,
                    'created_in'  => $ramal->created_at,
                    'updated_in'  => $ramal->updated_at,
                    'rejected'    => $ramal->rejected,
                    'rejected_at' => $ramal->rejected_at,
                    'informed_at' => $ramal->informed_at,
                    'updated_at'  => $now,
                ];
            }

            // Divide em lotes menores e faz upsert no SQL Server
            foreach (array_chunk($payload, $upsertBatchSize) as $batch) {
                LogInformsSmc::upsert(
                    $batch,
                    ['smc_id'], // coluna única
                    [           // colunas a atualizar em caso de conflito
                        'note', 'company', 'user', 'date', 'equipment',
                        'connection', 'observation', 'retry', 'created_in',
                        'updated_in', 'rejected', 'rejected_at', 'informed_at',
                        'updated_at'
                    ]
                );
            }

            $bar->advance($chunk->count());
        });

        $bar->finish();
        $this->info("\n<bg=green;fg=white> CONCLUÍDO </> <fg=white;options=bold> {$total} registros sincronizados. </>");
    }
}
