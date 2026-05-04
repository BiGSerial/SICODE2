<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use App\Models\ViabilityApproval;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TacitInApproval extends Command
{
    use ShowsProgress;

    private $userId;
    private $serviceId;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:tacitInApproval {--days=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finaliza a espera por aprovação tácita da Validação de Projetos';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->userId = User::first()->id;
        $this->serviceId = Service::where('service', 'Desenho')->first()->uuid;


        $now = Carbon::now()->startOfDay();
        $limitDate = $now->copy();

        $businessDays = 0;

        while ($businessDays < $this->option('days')) {
            $limitDate->subDay();
            if ($limitDate->isWeekday()) {
                $businessDays++;
            }
        }

        $finalLimitDate = $limitDate->copy()->subSecond();

        $totalSteps = ViabilityApproval::where('approved', false)
                        ->count();

        $bar = $this->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();



        ViabilityApproval::where('approved', false)
                        ->chunk(500, function ($approvals) use ($bar, $finalLimitDate) {

                            foreach ($approvals as $approval) {

                                /**
                                 *Verifica que a obra está ainda no prazo e se não existe Retorno interno ativo
                                 */
                                if ($approval->reclaims->isEmpty() && !$approval->note->files()->where('file_name', 'like', 'PROJETO%')->where('service_id', $this->serviceId)->exists()) {

                                    if (Reclaim::hasActiveForService($approval->note_id, $this->serviceId)) {
                                        continue;
                                    }

                                    $production = null;

                                    // Verifica se já existe uma produção finalizada para o mesmo serviço e nota
                                    // Se existir, cria uma nova produção com o mesmo usuário e empresa
                                    $hasProduction = Production::where('service_id', $this->serviceId)
                                        ->where('note_id', $approval->note_id)
                                        ->where('completed', true)
                                        ->orderBy('completed_at', 'desc')
                                        ->first();


                                    if ($hasProduction) {
                                        $production = Production::create([
                                            'note_id' => $approval->note_id,
                                            'service_id' => $this->serviceId,
                                            'completed' => false,
                                            'd5' => true,
                                            'att_at' => now(),
                                            'att_by' => $approval->user_id,
                                            'dispatch_at' => now(),
                                            'dispatch_by' => $approval->user_id,
                                            'user_id' => $hasProduction->user_id,
                                            'company_id' => $hasProduction->company_id,
                                            'status' => 2,
                                            'dt_note' => $approval->note->dt_status,
                                            'dhstats' => $approval->note->dt_status,
                                            'status_note' => $approval->note->nstats,
                                            'centroTrab' => $approval->note->centerjob,
                                        ]);
                                    }



                                    $toReclaim = $approval->reclaims()->create([
                                        'service_id' => $this->serviceId,
                                        'note_id' => $approval->note_id,
                                        'production_id' => $production ? $production->id : null,
                                        'category' => "ANEXAR PDF",

                                    ]);

                                    if ($toReclaim) {
                                        $toReclaim->Comments()->create([
                                            'user_id' => $approval->user_id,
                                            'message' => "Gentileza Anexar PDF.\n >> Enviado automáticamente por System Admin <<",
                                        ]);
                                    }

                                    continue;

                                } elseif ($approval->reclaims->isEmpty() && $approval->note->dt_status <= $finalLimitDate && $approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists()) {
                                    $approval->approved = true;
                                    $approval->tacit = true;
                                    $approval->approved_at = Carbon::now();
                                    $approval->reason = "Liberado tácitamente por status da nota ter mais que {$this->option('days')} dias úteis >>> System Admin <<<";
                                    $approval->save();

                                    continue;
                                }


                                /**
                                 * Liberação tácita das obbras em posse dos programadores
                                 * que não foram reclamadas e não possuem projeto
                                 */
                                if ($approval->reclaims->isEmpty() && $approval->created_at <= $finalLimitDate && !$approval->approved) {

                                    /**
                                     *  Verifica se o arquivo de projeto existe.
                                     *  Se existir, aprova a nota e libera para contratação.
                                     *  Se não existir, cria uma reclamação para anexar o projeto.
                                     *  e cria uma produção para o último usuário que esteve com a obra.
                                     *  Se a produção já existir, deixa no pooll de reclamação
                                     *  e não cria uma nova produção.
                                     */
                                    if ($approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists()) {
                                        $approval->approved = true;
                                        $approval->tacit = true;
                                        $approval->approved_at = Carbon::now();
                                        $approval->reason = "Liberado tácitamente após {$this->option('days')} dias úteis >>> System Admin <<<";
                                        $approval->save();
                                    } else {

                                        $approval->tacit = true;
                                        $approval->save();

                                        if (Reclaim::hasActiveForService($approval->note_id, $this->serviceId)) {
                                            continue;
                                        }

                                        $production = null;

                                        // Verifica se já existe uma produção finalizada para o mesmo serviço e nota
                                        // Se existir, cria uma nova produção com o mesmo usuário e empresa
                                        $hasProduction = Production::where('service_id', $this->serviceId)
                                            ->where('note_id', $approval->note_id)
                                            ->where('completed', true)
                                            ->orderBy('completed_at', 'desc')
                                            ->first();


                                        if ($hasProduction) {
                                            $production = Production::create([
                                                'note_id' => $approval->note_id,
                                                'service_id' => $this->serviceId,
                                                'completed' => false,
                                                'd5' => true,
                                                'att_at' => now(),
                                                'att_by' => $approval->user_id,
                                                'dispatch_at' => now(),
                                                'dispatch_by' => $approval->user_id,
                                                'user_id' => $hasProduction->user_id,
                                                'company_id' => $hasProduction->company_id,
                                                'status' => 2,
                                                'dt_note' => $approval->note->dt_status,
                                                'dhstats' => $approval->note->dt_status,
                                                'status_note' => $approval->note->nstats,
                                                'centroTrab' => $approval->note->centerjob,
                                            ]);
                                        }



                                        $toReclaim = $approval->reclaims()->create([
                                            'service_id' => $this->serviceId,
                                            'note_id' => $approval->note_id,
                                            'production_id' => $production ? $production->id : null,
                                            'category' => "ANEXAR PDF",

                                        ]);

                                        if ($toReclaim) {
                                            $toReclaim->Comments()->create([
                                                'user_id' => $approval->user_id,
                                                'message' => "Gentileza Anexar PDF.\n >> Enviado automáticamente por System Admin <<",
                                            ]);
                                        }
                                    }


                                } elseif (
                                    $approval->reclaims->isNotEmpty() &&
                                    $approval->reclaims->last()->completed &&
                                    !$approval->approved
                                ) {

                                    if ($approval->reclaims->last()->completed_at <= $finalLimitDate && $approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists()) {

                                        $approval->approved = true;
                                        $approval->tacit = true;
                                        $approval->approved_at = Carbon::now();
                                        $approval->reason = "Liberado tácitamente após {$this->option('days')} dias úteis >>> System Admin <<<";
                                        $approval->save();

                                    } elseif (!$approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists()) {
                                        if (Reclaim::hasActiveForService($approval->note_id, $this->serviceId)) {
                                            continue;
                                        }

                                        $production = null;

                                        // Verifica se já existe uma produção finalizada para o mesmo serviço e nota
                                        // Se existir, cria uma nova produção com o mesmo usuário e empresa
                                        $hasProduction = Production::where('service_id', $this->serviceId)
                                            ->where('note_id', $approval->note_id)
                                            ->where('completed', true)
                                            ->orderBy('completed_at', 'desc')
                                            ->first();


                                        if ($hasProduction) {
                                            $production = Production::create([
                                                'note_id' => $approval->note_id,
                                                'service_id' => $this->serviceId,
                                                'completed' => false,
                                                'd5' => true,
                                                'att_at' => now(),
                                                'att_by' => $approval->user_id,
                                                'dispatch_at' => now(),
                                                'dispatch_by' => $approval->user_id,
                                                'user_id' => $hasProduction->user_id,
                                                'company_id' => $hasProduction->company_id,
                                                'status' => 2,
                                                'dt_note' => $approval->note->dt_status,
                                                'dhstats' => $approval->note->dt_status,
                                                'status_note' => $approval->note->nstats,
                                                'centroTrab' => $approval->note->centerjob,
                                            ]);
                                        }



                                        $toReclaim = $approval->reclaims()->create([
                                            'service_id' => $this->serviceId,
                                            'note_id' => $approval->note_id,
                                            'production_id' => $production ? $production->id : null,
                                            'category' => "ANEXAR PDF",

                                        ]);

                                        if ($toReclaim) {
                                            $toReclaim->Comments()->create([
                                                'user_id' => $approval->user_id,
                                                'message' => "Gentileza Anexar PDF.\n >> Enviado automáticamente por System Admin <<",
                                            ]);
                                        }
                                    } elseif ($approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists() && $approval->tacit) {
                                        $approval->approved = true;
                                        $approval->tacit = true;
                                        $approval->approved_at = Carbon::now();
                                        $approval->reason = "Liberado tácitamente após {$this->option('days')} dias úteis >>> System Admin <<<";
                                        $approval->save();
                                    }
                                }

                                $bar->advance();
                            }

                        });


        $bar->finish();
        $this->info("\n\n");
        $this->info('Aprovações tácitas:');

        // $now = Carbon::now()->startOfDay();
        // $limitDate = $now->copy();

        // $businessDays = 0;

        // while ($businessDays < $this->option('days')) {
        //     $limitDate->subDay();
        //     if ($limitDate->isWeekday()) {
        //         $businessDays++;
        //     }
        // }

        // $finalLimitDate = $limitDate->copy()->subSecond();

        // $totalSteps = ViabilityApproval::where('created_at', '<=', $finalLimitDate)
        //     ->where('approved', false)
        //     ->count();

        // $bar = $this->createProgressBar($totalSteps);
        // $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        // $bar->setBarCharacter('<fg=green>█</>');
        // $bar->setEmptyBarCharacter('<fg=red>░</>');
        // $bar->setProgressCharacter('<fg=green>█</>');
        // $bar->setMessage('Iniciando...');
        // $bar->start();

        // $simulacao = [
        //     'tacito_normal' => [],
        //     'tacito_RI' => [],
        //     'RI_Sem_Projeto' => [],
        //     'RI_Nao_Finalizado' => [],
        // ];

        // ViabilityApproval::with(['reclaims', 'note.files'])->chunk(500, function ($approvals) use ($bar, $finalLimitDate, &$simulacao) {

        //     foreach ($approvals as $approval) {

        //         // Aprovação normal sem reclaims
        //         if (
        //             $approval->reclaims->isEmpty() &&
        //             $approval->created_at <= $finalLimitDate &&
        //             !$approval->approved
        //         ) {
        //             $simulacao['tacito_normal'][] = [
        //                 'note' => $approval->note->note,
        //                 'created_at' => $approval->created_at,
        //             ];
        //         }

        //         // Com reclaim finalizado, com projeto
        //         elseif (
        //             $approval->reclaims->isNotEmpty() &&
        //             $approval->reclaims->last()->completed &&
        //             $approval->reclaims->last()->completed_at <= $finalLimitDate &&
        //             !$approval->approved &&
        //             $approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists()
        //         ) {
        //             $simulacao['tacito_RI'][] = [
        //                 'note' => $approval->note->note,
        //                 'completed_at' => $approval->reclaims->last()->completed_at,
        //             ];
        //         }

        //         // Com reclaim finalizado, mas sem projeto
        //         elseif (
        //             $approval->reclaims->isNotEmpty() &&
        //             $approval->reclaims->last()->completed &&
        //             $approval->reclaims->last()->completed_at <= $finalLimitDate &&
        //             !$approval->approved &&
        //             !$approval->note->files()->where('file_name', 'like', 'PROJETO%')->exists()
        //         ) {
        //             $simulacao['RI_Sem_Projeto'][] = [
        //                 'note' => $approval->note->note,
        //                 'obs' => 'Reclamação finalizada, mas nenhum arquivo PROJETO encontrado.',
        //             ];
        //         }

        //         // Reclamação não finalizada
        //         elseif (
        //             $approval->reclaims->isNotEmpty() &&
        //             !$approval->reclaims->last()->completed
        //         ) {
        //             $simulacao['RI_Nao_Finalizado'][] = [
        //                 'note' => $approval->note->note,
        //                 'obs' => 'Reclamação ainda não finalizada.',
        //             ];
        //         }

        //         $bar->advance();
        //     }

        // });

        // $bar->finish();

        // $this->info("\n\nSimulação concluída.");
        // $this->info("Total normal: " . count($simulacao['tacito_normal']));
        // $this->info("Total com RI e projeto: " . count($simulacao['tacito_RI']));
        // $this->info("Total com RI sem projeto: " . count($simulacao['RI_Sem_Projeto']));
        // $this->info("Total com RI não finalizado: " . count($simulacao['RI_Nao_Finalizado']));

        // // Exportar para JSON
        // $filename = 'simulacao_aprovacoes_RI_' . now()->format('Ymd_His') . '.json';
        // Storage::put("simulacoes/{$filename}", json_encode($simulacao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // $this->info("Arquivo salvo em: storage/app/simulacoes/{$filename}");
    }
}
