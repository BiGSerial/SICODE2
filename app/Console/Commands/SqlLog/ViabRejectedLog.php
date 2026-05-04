<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\SicodeSql\InformLog;
use App\Models\Form;
use App\Models\SicodeSql\ViabReject;
use Carbon\Carbon;
use Illuminate\Console\Command;

use function Laravel\Prompts\form;

class ViabRejectedLog extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_rejected_viab {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send LOG Informs to SQL SERVER';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT INFORMS SERVER, ABORTING PROPAGATION LOG</>');
            return; // Adicionando um retorno aqui para abortar a execução
        }

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> Verifying Informs.... </>');

        $days = $this->option('days');

        $forms = Form::where('rejected', true)->whereDate('updated_at', '>=', Carbon::now()->subDays($days))->get();
        $progressBar = $this->createProgressBar($forms->count());
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');

        Form::where('rejected', true)
            ->whereDate('updated_at', '>=', Carbon::now()->subDays($days))
            ->chunk(500, function ($forms) use ($progressBar) {
            foreach ($forms as $form) {
                if ($form->Viability && $form->Viability->Orders->isNotEmpty()) {
                foreach ($form->Viability->Orders as $order) {
                    $check = ViabReject::updateOrCreate(
                    [
                        'form_id' => $form->id,
                        'order' => $order->ordem,
                    ],
                    [
                        'note' => $form->Viability->Note->note,
                        'responsible' => $form->responsible,
                        'company' => $form->Viability->Company->name,
                        'reason' => $form->reason,
                        'description' => $form->description,
                        'created_at' => $form->created_at,
                        'updated_at' => $form->updated_at,
                    ]
                    );

                    if ($check->wasRecentlyCreated) {
                    $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> " . $order->ordem . " </>";
                    } else {
                    $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> " . $order->ordem . " </>";
                    }
                }
                }

                $progressBar->setMessage($msg);
                $progressBar->advance();
            }
            });

        $progressBar->finish();

        if ($forms->isEmpty()) {
            $this->info("<bg=green;fg=white;options=bold> DONE </><fg=yellow;options=bold> NO REGISTERS FOUND");
        } else {
            $this->info("<bg=blue;fg=white;options=bold> INFO </><fg=white;options=bold> WE HAVE FOUND {$forms->count()} REGISTERS THAT AREN'T IN INFORMS LOG");
        }

        $this->info('<bg=green;fg=white> DONE </>');
    }
}
