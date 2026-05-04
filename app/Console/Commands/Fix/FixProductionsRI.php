<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Reclaim;
use Illuminate\Console\Command;

class FixProductionsRI extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix_productionsRI {--days=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify about extra register in Destiny.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $totalSteps = Reclaim::when($this->option('days'), function ($query) {
            return $query->where('created_at', '>=', now()->subDays($this->option('days'))->startOfDay());
        })->count();

        $bar = $this->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();


        Reclaim::when($this->option('days'), function ($query) {
            return $query->where('created_at', '>=', now()->subDays($this->option('days'))->startOfDay());
        })->chunk(500, function ($reclaims) use ($bar) {
            foreach ($reclaims as $reclaim) {
                if ($reclaim->production && $reclaim->production->note_id != $reclaim->note_id) {
                    $note = $reclaim->production->note->note;
                    $reclaim->production->update([
                        'note_id' => $reclaim->note_id,
                    ]);

                    $this->info("Updated production {$note} to {$reclaim->note->note}");
                } elseif ($reclaim->production) {
                    $reclaim->production->update([
                        'dt_note' => $reclaim->note->dt_status,
                        'dhstats' => $reclaim->note->dt_status,
                        'status_note' => $reclaim->note->nstats,
                        'centroTrab' => $reclaim->note->centerjob,
                    ]);
                }


                $bar->advance();
            }
        });

        $bar->setMessage('Finalizando...'); // Mensagem final
        $bar->finish();
        $this->info("\n\n");
        $this->info('Processo concluído!');
    }

}
