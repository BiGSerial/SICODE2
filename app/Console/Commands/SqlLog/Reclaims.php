<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Reclaim;
use App\Models\SicodeSql\ReclaimLog;
use Illuminate\Console\Command;

class Reclaims extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:reclaims {--full} {--days=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia para Log os retonos de retrabalho para o SQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $date = $this->option('full') ? '' : now()->subDays($this->option('days'))->startOfDay()->format('Y-m-d H:i:s');
        $totalSteps = Reclaim::when($date, function ($query) use ($date) {
            return $query->where('completed_at', '>=', $date);
        })->where('completed', true)->count();



        // Configure o ProgressBar
        $bar = $this->createProgressBar($totalSteps);

        // Estilo customizado para uma aparência moderna e elegante
        // $bar->setFormatDefinition('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');

        // Defina o formato customizado
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');

        //  Caracteres para um visual mais limpo (opcional, mas recomendado)
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso (pode ser o mesmo da barra preenchida)

        // Adicione informações úteis
        $bar->setMessage('Iniciando...'); // Mensagem inicial


        $bar->start();

        Reclaim::when($date, function ($query) use ($date) {
            return $query->where('completed_at', '>=', $date);
        })->where('completed', true)->with('Production', 'Note', 'Approvals', 'Viabilities', 'Service')
            ->chunk(500, function ($reclaims) use (&$bar) {

                foreach ($reclaims as $reclaim) {

                    $origem['origem'] = 'Desconhecida';
                    $work = null;

                    if ($reclaim->approvals->isNotEmpty()) {
                        $origem['origem'] = 'Analise Projeto';
                        $work = $reclaim->approvals->first();
                    }

                    if ($reclaim->viabilities->isNotEmpty()) {
                        $origem['origem'] = 'Viabilidade';
                        $work = $reclaim->viabilities->first();
                    }

                    if ($reclaim->Waiting) {
                        $origem['origem'] = 'Contratação';
                        $work = $reclaim->waiting;
                    }

                    $chk = ReclaimLog::updateOrCreate(['reclaim_id' => $reclaim->id], [
                        'note' => $reclaim->note->note,
                        'origin' => $origem['origem'],
                        'service' => $reclaim->Service->service,
                        'category' => $reclaim->category,
                        'emissor' => $work ? $work->user->name : 'Desconhecido',
                        'company_emissor' => $work ? $work->User->company->name : 'Desconhecido',
                        'received_at' => $reclaim->created_at,
                        'att_at' => $reclaim->production ? $reclaim->production->att_at : '',
                        'completed_at' => $reclaim->completed_at,
                        'user' => $reclaim->production && $reclaim->production->User ? $reclaim->production->User->name : 'Desconhecido',
                        'company_user' => $reclaim->production && $reclaim->production->Company ? $reclaim->production->Company->name : 'Desconhecido',
                    ]);

                    $bar->setMessage($reclaim->note->note." - ".$reclaim->Service->service); // Atualize a mensagem
                    $bar->advance();
                }
            });

        // Mensagem de finalização
        $bar->setMessage('<info>Concluído!</info>'); // Use um estilo para destacar

        $bar->finish();

        // Adicione uma nova linha após a barra de progresso
        $this->output->writeln(''); // Garante que a saída seguinte não fique na mesma linha da barra
    }
}
