<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Http\Livewire\Construction\Hiring\Actions\Viability;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use App\Models\ViabilityApproval;
use App\Repositories\ApprovalsRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TacitToApproval extends Command
{
    use ShowsProgress;

    private $userId;
    private $serviceId;
    private $production;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:tacitToApproval {--days=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libera automáticamente por aprovação tácita da Validação de Projetos para contratação';


    protected ApprovalsRepository $approvalsRepository;

    public function __construct(ApprovalsRepository $approvalsRepository)
    {
        parent::__construct();
        $this->approvalsRepository = $approvalsRepository;
    }

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


        $query = clone $this->approvalsRepository->getBaseQuery()
                        ->where('dt_status', '<=', $finalLimitDate);



        $totalSteps = $query->count();



        $bar = $this->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();



        $query->chunk(500, function ($approvals) use ($bar, $finalLimitDate, &$teste) {

            foreach ($approvals as $approval) {

                if ($approval->files()->where('file_name', 'like', 'PROJETO%')->where('service_id', $this->serviceId)->exists()) {
                    ViabilityApproval::create([
                        'user_id' => $this->userId,
                        'note_id' => $approval->id,
                        'approved'  => true,
                        'tacit' => true,
                        'status' => $approval->nstats,
                        'dt_status' => $approval->dt_status,
                        'approved_at' => now(),
                        'reason' => "Liberado automáticamente por aprovação tácita após " . $this->option('days') . " dias úteis sem definição \n >> System Admin <<",
                    ]);
                } else {
                    $toApproval =  ViabilityApproval::create([
                        'user_id' => $this->userId,
                        'note_id' => $approval->id,
                        'approved'  => false,
                        'tacit' => true,
                        'status' => $approval->nstats,
                        'dt_status' => $approval->dt_status,
                        'approved_at' => null,
                        'reason' => "Assumido automáticamente após " . $this->option('days') . " dias úteis sem definição \n >> System Admin <<",
                    ]);

                    if ($toApproval) {
                        if (Reclaim::hasActiveForService($toApproval->note_id, $this->serviceId)) {
                            continue;
                        }

                        $production = null;



                        $hasProduction = Production::where('service_id', $this->serviceId)
                            ->where('note_id', $toApproval->note_id)
                            ->where('completed', true)
                            ->orderBy('completed_at', 'desc')
                            ->first();


                        if ($hasProduction) {
                            $production = Production::create([
                                'note_id' => $approval->id,
                                'service_id' => $this->serviceId,
                                'completed' => false,
                                'd5' => true,
                                'att_at' => now(),
                                'att_by' => $this->userId,
                                'dispatch_at' => now(),
                                'dispatch_by' => $this->userId,
                                'user_id' => $hasProduction->user_id,
                                'company_id' => $hasProduction->company_id,
                                'status' => 2,
                                'dt_note' => $approval->dt_status,
                                'dhstats' => $approval->dt_status,
                                'status_note' => $approval->nstats,
                                'centroTrab' => $approval->centerjob,
                            ]);
                        }



                        $toReclaim = $toApproval->reclaims()->create([
                            'service_id' => $this->serviceId,
                            'note_id' => $toApproval->note_id,
                            'production_id' => $production ? $production->id : null,
                            'category' => "ANEXAR PDF",

                        ]);

                        if ($toReclaim) {
                            $toReclaim->Comments()->create([
                                'user_id' => $this->userId,
                                'message' => "Obra Retornada automáticamente da etapa de Validação de Projeto, por não ter sido identificado a existência do arquivo de projeto. Gentileza anexar o projeto ao SICODE.\n >> System Admin <<",
                            ]);
                        }


                    }
                }

                $bar->advance();
            }

        });


        $bar->finish();
        $this->info("\n\n");
        $this->info('Aprovações tácitas:');


        // $this->userId = User::first()->id;
        // $this->serviceId = Service::where('service', 'Desenho')->first()->uuid;

        // // dd($this->userId, $this->serviceId);

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

        // $query = clone $this->approvalsRepository->getBaseQuery()
        //     ->where('dt_status', '<=', $finalLimitDate);

        // $totalSteps = $query->count();

        // // Simulação
        // $simulacao = [
        //     'tacitamenteAprovadas' => [],
        //     'retornadasSemProjeto' => [],
        //     'productionsCriadas' => [],
        //     'reclamacoesCriadas' => [],
        //     'comentariosCriados' => [],
        // ];

        // $bar = $this->createProgressBar($totalSteps);
        // $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        // $bar->setBarCharacter('<fg=green>█</>');
        // $bar->setEmptyBarCharacter('<fg=red>░</>');
        // $bar->setProgressCharacter('<fg=green>█</>');
        // $bar->setMessage('Iniciando...');
        // $bar->start();

        // $query->chunk(500, function ($approvals) use ($bar, $finalLimitDate, &$simulacao) {

        //     foreach ($approvals as $approval) {
        //         if ($approval->files()->where('file_name', 'like', 'PROJETO%')->exists()) {
        //             // Simula a aprovação tácita com projeto
        //             $simulacao['tacitamenteAprovadas'][] = [
        //                 'note' => $approval->note,
        //                 'status' => $approval->nstats,
        //                 'dt_status' => $approval->dt_status,
        //             ];
        //         } else {
        //             // Simula a criação de uma aprovação não-aprovada
        //             $simulacao['retornadasSemProjeto'][] = [
        //                 'note' => $approval->note,
        //                 'status' => $approval->nstats,
        //                 'dt_status' => $approval->dt_status,
        //             ];

        //             // Simula busca por produção anterior
        //             $hasProduction = Production::where('note_id', $approval->id)
        //                 ->where('service_id', $this->serviceId)
        //                 ->where('completed', true)
        //                 ->orderBy('completed_at', 'desc')
        //                 ->first();

        //             if ($hasProduction) {
        //                 $simulacao['productionsCriadas'][] = [
        //                     'note' => $approval->note,
        //                     'usuario_anterior' => $hasProduction->user_id,
        //                     'empresa_anterior' => $hasProduction->company_id,
        //                 ];
        //             }

        //             $simulacao['reclamacoesCriadas'][] = [
        //                 'note' => $approval->note,
        //                 'tem_production' => $hasProduction ? true : false,
        //             ];

        //             $simulacao['comentariosCriados'][] = [
        //                 'note_id' => $approval->id,
        //                 'mensagem' => 'Obra retornada automaticamente por ausência de projeto.',
        //             ];
        //         }

        //         $bar->advance();
        //     }

        // });

        // $bar->finish();

        // $this->info("\n\nSimulação concluída.");
        // $this->info("Total aprovadas tacitamente: " . count($simulacao['tacitamenteAprovadas']));
        // $this->info("Total retornadas sem projeto: " . count($simulacao['retornadasSemProjeto']));

        // // Gera nome do arquivo baseado no timestamp
        // $filename = 'simulacao_aprovacoes_' . now()->format('Ymd_His') . '.json';

        // // Salva o arquivo em storage/app/simulacoes/
        // Storage::put("simulacoes/{$filename}", json_encode($simulacao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // $this->info("Arquivo salvo em: storage/app/simulacoes/{$filename}");

    }
}
