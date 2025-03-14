<?php

namespace App\Console\Commands\tools;

use App\Models\Edp_cipqa\InfoAdsTemp;
use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class AdsTempGet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:ads-temp-get {--full} {--days=1}';

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
        $date = $this->option('full') ? null : now()->subDays($this->option('days'))->startOfDay()->format('Y-m-d H:i:s');
        $query = InfoAdsTemp::when($date, function ($query) use ($date) {
            return $query->where('DATA_ENVIO', '>=', $date);
        })->whereNotNull('OV_NOTA')->where('RODOU_SCRIPT', 'SIM')->orderBy('DATA_ENVIO', 'ASC')->orderBy('OV_NOTA', 'ASC');

        $totalSteps = $query->count();

        // Configure o ProgressBar
        $bar = $this->output->createProgressBar($totalSteps);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso
        $bar->setMessage('Iniciando...'); // Mensagem inicial
        $bar->start();

        $offset = 0;
        $limit = 500;

        do {
            $adsTemps = $query->offset($offset)
                               ->limit($limit)
                               ->get();

            $count = $adsTemps->count();

            if ($count > 0) {
                $notes = Note::whereIn('note', $adsTemps->pluck('OV_NOTA')->toArray())->get();
                foreach ($adsTemps as $ads) {
                    // $notes = Note::where('note', $ads->OV_NOTA)->get();
                    $note = $notes->where('note', $ads->OV_NOTA)->first();

                    if ($note) {
                        $chk = $note->TempAdsInfos()->updateOrCreate([
                            'note' => $ads->OV_NOTA,
                            'sended_at' => $ads->DATA_ENVIO,
                        ], [
                            'company_name' => $ads->EMPREITEIRA,
                            'from' => $ads->SOLICITANTE,
                        ]);

                        $message = $ads->OV_NOTA . " - " . ($chk->wasRecentlyCreated ? "CREATED" : "UPDATED");
                    } else {
                        $message = $ads->OV_NOTA . " - NOT FOUND";
                    }

                    $bar->setMessage($message);
                    $bar->advance();
                }
                $offset += $limit;
            }
        } while ($count > 0);

        // Mensagem de finalização
        $bar->setMessage('<info>Concluído!</info>'); // Use um estilo para destacar
        $bar->finish();

        // Adicione uma nova linha após a barra de progresso
        $this->output->writeln(''); // Garante que a saída seguinte não fique na mesma linha da barra
    }
}
