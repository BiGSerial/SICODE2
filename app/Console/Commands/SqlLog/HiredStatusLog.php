<?php

namespace App\Console\Commands\SqlLog;

use App\Models\Note;
use App\Models\Production;
use App\Models\SicodeSql\HiringStatus;
use App\Models\ViabilityApproval;
use App\Repositories\HiringRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

class HiredStatusLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_hired_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o log de status da contratação verificando os contratados';


    protected HiringRepository $HiringRepository;

    public function __construct(HiringRepository $HiringRepository)
    {
        parent::__construct();
        $this->HiringRepository = $HiringRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {



        $query = HiringStatus::where('position', 'CONTRATANTE');


        $totalSteps = $query->count();




        $bar = $this->output->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();



        $query->chunk(500, function ($notes) use ($bar) {


            $localNotes = Note::whereIn('note', $notes->pluck('note'))
                    ->whereHas('viabilities.orders.operations', function ($q) {
                        $q->where('operacao', '0010')
                        ->where('status', 'like', 'CONF%');
                    })->with('viabilities')->get();

            if ($localNotes->count() > 0) {

                foreach ($localNotes as $lNote) {
                    $updateNote = $notes->where('note_id', $lNote->id)->first();
                    if ($updateNote) {
                        $updateNote->position = 'CONTRATADO';
                        $updateNote->last_date = $lNote->viabilities?->last()->hired_at;
                        $updateNote->register = $lNote->viabilities?->last()->user?->Registration;
                        $updateNote->responsible = $lNote->viabilities?->last()->user?->name;
                        $updateNote->save();
                    }

                    $bar->advance();
                }


            }          // Avança a barra de progresso




        });




        $bar->finish();
        $this->info("\n\n");
        $this->info('Finalizado com sucesso!');
    }


}
