<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\Notestatus;
use App\Models\Production;
use App\Models\SicodeSql\Production as SicodeSqlProduction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProductionLog extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_production {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send LOG Production to SQL SERVER (Optimized with Upsert and Batching)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o ambiente é de produção antes de continuar
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NÃO É UM SERVIDOR DE PRODUÇÃO, ABORTANDO LOG DE PROPAGAÇÃO</>');
            return; // Interrompe a execução se não for produção
        }

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> Verificando Produções para Sincronização.... </>');

        $days = $this->option('days');
        $processedRecordsCount = 0; // Contador para o total de registros processados

        // Prepara a consulta inicial com carregamento antecipado das relações
        $query = Production::where('d5', false)
            ->whereDate('updated_at', '>=', Carbon::now()->subDays($days))
            ->with([
                'Note',
                'User',
                'Company',
                'Service',
                // Carregamento condicional para otimizar apenas quando necessário
                'Dispatcher.Employee.Contract.company',
                'Att.Employee.Contract.company',
                'Analise'
            ]);

        // Obtém o número total de registros para a barra de progresso
        $totalProductions = $query->count();

        // Se não houver registros, informa e finaliza
        if ($totalProductions === 0) {
            $this->info("<bg=green;fg=white;options=bold> CONCLUÍDO </><fg=yellow;options=bold> NENHUM REGISTRO ENCONTRADO PARA SINCRONIZAÇÃO.");
            $this->info('<bg=green;fg=white> CONCLUÍDO </>');
            return;
        }

        $this->info("<bg=blue;fg=white> INFO </> <fg=white;options=bold> Total de {$totalProductions} registros a serem processados.</>");

        $progressBar = $this->createProgressBar($totalProductions);
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Iniciando sincronização em massa...');

        // Define o tamanho do lote para a operação upsert (seguro para SQL Server)
        // Aproximadamente 30 colunas * 60 registros = 1800 parâmetros (dentro do limite de 2100)
        $upsertBatchSize = 50;

        // Processa as produções em chunks de 500 para gerenciar a memória
        $query->chunk(500, function ($chunk) use ($progressBar, &$processedRecordsCount, $upsertBatchSize) {
            $dataForUpsert = []; // Array para armazenar os dados de um chunk antes de subdividir

            // Prepara os dados de cada produção para o formato do upsert
            foreach ($chunk as $production) {
                $dataForUpsert[] = [
                    'production_id' => $production->id,
                    'user' => optional($production->User)->name ?? 'Desconhecido',
                    'company' => optional($production->Company)->name ?? 'Desconhecido',
                    'dispatch_by' => optional($production->Dispatcher)->name ?? 'Desconhecido',
                    'company_dispatch' => optional(optional(optional($production->Dispatcher)->Employee)->Contract)->company->name ?? 'Desconhecido',
                    'att_by' => optional($production->Att)->name ?? 'Desconhecido',
                    'company_att' => optional(optional(optional($production->Att)->Employee)->Contract)->company->name ?? 'Desconhecido',
                    'service' => optional($production->Service)->service ?? 'Desconhecido',
                    'note' => optional($production->Note)->note ?? 'desconhecido',
                    'status' => Notestatus::status($production->status)->status,
                    'dispatch_at' => $production->dispatch_at,
                    'att_at' => $production->att_at,
                    'completed_at' => $production->completed_at,
                    'confirmed_at' => $production->confirmed_at,
                    'completed' => $production->completed,
                    'confirmed' => $production->confirmed,
                    'stopped' => $production->stopped,
                    'note_status' => $production->status_note,
                    'conclusion' => optional($production->Analise)->conclusion ?? "",
                    'mmgd' => $production->mmgd,
                    'transfer' => $production->transferred,
                    'input_manual' => $production->manual,
                    'conf_manual' => $production->conf_manual,
                    'reje_manual' => $production->rejected,
                    'dhstats' => $production->dt_note,
                    'type_note' => optional($production->Note)->type_note ?? 3,
                    'eo' => $production->eo,
                    'iproject' => $production->iproject,
                    'cadastro' => $production->cadastro,
                    'postes_u' => $production->postes_u,
                    'postes_c' => $production->postes_c,
                    'centroTrab' => $production->centroTrab,
                    'noinconsistency' => $production->noinconsistency,
                    'ma' => $production->ma,
                    'created_at' => $production->created_at ?? Carbon::now(), // Garante que created_at existe
                    'updated_at' => Carbon::now(), // Atualiza updated_at para o upsert
                    'partial' => $production->partial,
                    'partial_at' => $production->partial_at,
                    'dfive' => $production->dfive,
                ];
            }

            // Divide os dados preparados em lotes menores para o upsert
            $batchesForUpsert = array_chunk($dataForUpsert, $upsertBatchSize);

            foreach ($batchesForUpsert as $batch) {
                if (!empty($batch)) {
                    SicodeSqlProduction::upsert(
                        $batch,
                        ['production_id'], // Coluna(s) que identificam unicidade
                        [ // Colunas a serem atualizadas se o registro existir
                            'user', 'company', 'dispatch_by', 'company_dispatch', 'att_by', 'company_att', 'service',
                            'note', 'status', 'dispatch_at', 'att_at', 'completed_at', 'confirmed_at', 'completed',
                            'confirmed', 'stopped', 'note_status', 'conclusion', 'mmgd', 'transfer', 'input_manual',
                            'conf_manual', 'reje_manual', 'dhstats', 'type_note', 'eo', 'iproject', 'cadastro',
                            'postes_u', 'postes_c', 'centroTrab', 'noinconsistency', 'ma', 'updated_at', 'partial', 'partial_at', 'dfive'
                        ]
                    );
                }
            }

            // Atualiza o contador e a barra de progresso após processar todo o chunk
            $processedRecordsCount += $chunk->count();
            $progressBar->setMessage("Processando chunk de {$chunk->count()} registros...");
            $progressBar->advance($chunk->count());
        });

        $progressBar->finish(); // Finaliza a barra de progresso

        $this->info("<bg=green;fg=white;options=bold> CONCLUÍDO </><fg=white;options=bold> Total de {$processedRecordsCount} registros sincronizados com sucesso.</>");
        $this->info('<bg=green;fg=white> CONCLUÍDO </>');
    }
}
