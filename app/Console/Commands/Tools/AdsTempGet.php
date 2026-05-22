<?php
namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;


use App\Models\Edp_cipqa\OldAdsList;
use App\Models\Note;
use App\Models\OldAdsInform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class AdsTempGet extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:ads-temp-get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando Temporário para pegar informações de ADS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $full = !(OldAdsInform::count() > 0);

        $totalSteps = OldAdsList::count();

        // Configure o ProgressBar
        $bar = $this->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();

        OldAdsList::chunk(1000, function ($adsList) use ($bar, $full) {


            $dataBatch = [];
            $notesIds = array_merge(
                $adsList->pluck('Nota')->toArray(),
                $adsList->pluck('Ov')->toArray()
            );
            $notes = Note::whereIn('note', $notesIds)->get();

            foreach ($adsList as $ads) {


                if ($ads->Nota != null) {
                    $note = $notes->where('note', $ads->Nota)->first();
                } elseif ($ads->Ov != null) {
                    $note = $notes->where('note', $ads->Ov)->first();
                } else {
                    $note = null;
                }

                if ($note) {

                    if ($full) {

                        $dataBatch[] = [
                            'note_id' => $note->id,
                            'ads_id' => $ads->id,
                            'user' => $ads->Usuario,
                            'date' => $ads->Data,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                    } else {

                        OldAdsInform::updateOrCreate(
                            [
                                'note_id' => $note->id,
                                'ads_id' => $ads->id,
                            ],
                            [
                                'user' => $ads->Usuario,
                                'date' => $ads->Data,
                            ]
                        );

                        $bar->advance();
                    }

                }

            }

            if ($dataBatch) {
                try {
                    OldAdsInform::insert($dataBatch);
                } catch (PDOException $e) {
                    Log::error('Erro ao inserir lote: ' . $e->getMessage());
                }

                $bar->advance(count($dataBatch));
            }



        });

        // Mensagem de finalização
        $bar->setMessage('<info>Concluído!</info>'); // Use um estilo para destacar
        $bar->finish();

        // Adicione uma nova linha após a barra de progresso
        $this->output->writeln(''); // Garante que a saída seguinte não fique na mesma linha da barra
    }
}
