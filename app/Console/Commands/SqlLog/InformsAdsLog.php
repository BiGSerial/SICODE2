<?php

namespace App\Console\Commands\SqlLog;

use App\Models\Adsform;
use App\Models\sicodesql\LogAdsInforms;
use Illuminate\Console\Command;

class InformsAdsLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:informs-ads-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $full = !(LogAdsInforms::count() > 0);
        $limitBatch = 300;

        $totalSteps = Adsform::count();

        if ($totalSteps == 0) {
            $this->info('Nenhum registro encontrado.');
            return;
        }

        // Configure o ProgressBar
        $bar = $this->output->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();


        Adsform::when(!$full, function ($query) {
            return $query->where('updated_at', '>=', now()->subDays(1));
        })->chunk(1000, function ($adsforms) use ($bar, $full, $limitBatch) {

            $dataBatch = [];
            foreach ($adsforms as $adsform) {

                if ($full) {

                    $dataBatch[] = [
                        'adsform_id' => $adsform->id,
                        'work_report_id' => $adsform->work_report_id,
                        'note_id' => $adsform->note_id,
                        'user_name' => $adsform->user->name,
                        'name' => $adsform->name,
                        'obs' => $adsform->obs,
                        'contract' => $adsform->contract,
                        'center' => $adsform->center,
                        'deposit' => $adsform->deposit,
                        'amount' => $adsform->amount,
                        'date' => $adsform->created_at,
                    ];

                    if (count($dataBatch) >= $limitBatch) {
                        LogAdsInforms::insert($dataBatch);
                        $dataBatch = [];
                    }

                } else {

                    LogAdsInforms::updateOrCreate(
                        [
                            'adsform_id' => $adsform->id,
                            'work_report_id' => $adsform->work_report_id,
                            'note_id' => $adsform->note_id,
                        ],
                        [
                            'user_name' => $adsform->user->name,
                            'name' => $adsform->name,
                            'obs' => $adsform->obs,
                            'contract' => $adsform->contract,
                            'center' => $adsform->center,
                            'deposit' => $adsform->deposit,
                            'amount' => $adsform->amount,
                            'date' => $adsform->created_at,
                        ]
                    );
                }

                // Atualiza a barra de progresso
                $bar->setMessage('Processando...'); // Mensagem de progresso
                $bar->advance();
            }
            if (count($dataBatch) > 0) {
                // Insere os dados restantes
                LogAdsInforms::insert($dataBatch);
            }
        });
    }
}
