<?php

namespace App\Console\Commands\Tools;

use App\Models\Production;
use App\Models\SicodeSql\HiringStatus;
use App\Models\ViabilityApproval;
use App\Repositories\ApprovalsRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

class HiringStatusLog extends Command
{
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

        $full = !count(HiringStatus::all());

        $query = $this->approvalsRepository->getBaseQuery();


        $totalSteps = clone $query->count();




        $bar = $this->output->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();



        $query->chunk(500, function ($notes) use ($bar, $full) {

            $maxBatch = 200;
            $batchNotes = [];

            foreach ($notes as $note) {
                $hiringStatus = $this->emptyArray();

                $hiringStatus->note_id = $note->id;
                $hiringStatus->note = $note->note;
                $hiringStatus->dt_status = $note->dt_status;

                if (!$note->approval) {
                    $hiringStatus->last_date = $note->dt_status;
                    $hiringStatus->position = 'PILHA PARA VALIDAÇÃO';
                } else {
                    if ($note->approval->approved) {
                        $hiringStatus->last_date = $note->approval->approved_at;
                        $hiringStatus->position = 'CONTRATANTE';
                        $hiringStatus->tacit = $note->approval->tacit;
                    } elseif ($note->approval->reclaims->isEmpty()) {
                        $hiringStatus->last_date = $note->approval->created_at;
                        $hiringStatus->position = 'PROGRAMADOR';
                        $hiringStatus->register = $note->approval->user?->Registration;
                        $hiringStatus->responsible = $note->approval->user?->name;
                        $hiringStatus->tacit = $note->approval->tacit;
                    } elseif ($note->approval->reclaims->isNotEmpty() && !$note->approval->reclaims->last()->completed) {
                        $hiringStatus->last_date = $note->approval->reclaims->last()->created_at;
                        $hiringStatus->position = 'CIP';
                        $hiringStatus->register = $note->approval->reclaims->last()->production?->user?->Registration;
                        $hiringStatus->responsible =  $note->approval->reclaims->last()->production?->user?->name;
                        $hiringStatus->tacit = $note->approval->tacit;
                    } elseif ($note->approval->reclaims->isNotEmpty() && $note->approval->reclaims->last()->completed) {
                        $hiringStatus->last_date = $note->approval->reclaims->last()->completed_at;
                        $hiringStatus->position = 'PROGRAMADOR';
                        $hiringStatus->register = $note->approval->user?->Registration;
                        $hiringStatus->responsible =  $note->approval->user?->name;
                        $hiringStatus->tacit = $note->approval->tacit;
                    }
                }

                if ($full) {
                    $batchNotes[] = $hiringStatus;

                    if (count($batchNotes) >= $maxBatch) {
                        HiringStatus::insert($batchNotes);
                        $batchNotes = [];
                    }
                } else {
                    HiringStatus::updateOrCreate(
                        [
                            'note_id' => $hiringStatus->note_id,
                        ],
                        [
                            'note' => $hiringStatus->note,
                            'dt_status' => $hiringStatus->dt_status,
                            'last_date' => $hiringStatus->last_date,
                            'position' => $hiringStatus->position,
                            'register' => $hiringStatus->register,
                            'responsible' => $hiringStatus->responsible,
                            'tacit' => $hiringStatus->tacit,
                        ]
                    );
                }

                $bar->setMessage('Processando: ' . $note->note); // Atualiza a mensagem
                $bar->advance(); // Avança a barra de progresso

            }

            if (count($batchNotes) > 0) {
                HiringStatus::insert($batchNotes);
                $batchNotes = [];
            }

        });


        $bar->finish();
        $this->info("\n\n");
        $this->info('Finalizado com sucesso!');
    }

    private function emptyArray()
    {
        return (object)[
            'note_id' => null,
            'note' => null,
            'dt_status' => null,
            'last_date' => null,
            'position' => null,
            'register' => null,
            'responsible' => null,
            'tacit' => null,
            'tacit_at' => null,
        ];
    }
}
