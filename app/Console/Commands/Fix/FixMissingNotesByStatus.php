<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV; // ORIGEM (SQL Server)
use App\Models\Note;            // DESTINO (MySQL)
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixMissingNotesByStatus extends Command
{
    /**
     * Uso:
     *  php artisan sicode:missing-notes --status=10        # imprime só as OVs faltantes (uma por linha)
     *  php artisan sicode:missing-notes --status=10 -v     # idem + contagens por chunk
     */
    protected $signature = 'sicode:missing-notes {--status=} {--days=7}';

    protected $description = 'Lista OVs que estão na origem (numStat, ultimoStatus=1) e faltam em notes (type_note=2, nstats) no mesmo status.';

    public function handle()
    {
        $status = $this->option('status');
        $days = (int)$this->option('days');
        $isVerbose = $this->option('verbose');
        $missing = [];
        $totalOriginRecordsProcessed = 0;
        $totalDestinationRecordsFound = 0;

        if (empty($status)) {
            $this->error('O parâmetro --status é obrigatório. Ex: --status=10');
            return Command::FAILURE;
        }

        $this->info("Iniciando busca por OVs faltantes para o status [{$status}] nos últimos [{$days}] dias.");

        BaseOV::where('ultimoStatus', 1)
            ->select('OV')
            ->where('numStat', $status)
            ->whereDate('dtStat', '>=', now()->subDays($days))
            ->chunk(1000, function ($baseOVsChunk) use ($status, &$missing, $isVerbose, &$totalOriginRecordsProcessed, &$totalDestinationRecordsFound) {

                // 1. Coleta OVs da origem, filtrando valores nulos ou vazios
                $originOVs = $baseOVsChunk->pluck('OV')->toArray();

                if (empty($originOVs)) {
                    // Se não há OVs válidas neste chunk da origem, pula para o próximo
                    if ($isVerbose) {
                        $this->line('Chunk: Nenhuma OV válida na origem, pulando.');
                    }
                    return;
                }

                $totalOriginRecordsProcessed += count($originOVs);

                // 2. Coleta notes do destino que correspondem às OVs da origem e ao status, filtrando nulos
                $destinyNotes = Note::whereIn('note', $originOVs)
                    ->where('type_note', 2) // OV
                    ->where('nstats', $status)
                    ->pluck('note')
                    ->toArray();

                $totalDestinationRecordsFound += count($destinyNotes);

                // 3. Compara os arrays para encontrar as OVs que estão na origem mas não no destino
                $diff = array_diff($originOVs, $destinyNotes);

                dd(count($diff) . ' - ' . count($originOVs) . ' - ' . count($destinyNotes));

                if (!empty($diff)) {
                    $missing = array_merge($missing, array_values($diff));
                }

                if ($isVerbose) {
                    $this->line(sprintf(
                        'Chunk processado: Origem válidas (%d), Destino encontradas (%d), Faltantes neste chunk (%d)',
                        count($originOVs),
                        count($destinyNotes),
                        count($diff)
                    ));
                }
            });

        $totalMissing = count($missing);

        $this->line(''); // Nova linha para melhor legibilidade
        $this->info('--- Resumo Final ---');
        $this->line(sprintf('Total de OVs processadas na origem (status %d, %d dias): %d', $status, $days, $totalOriginRecordsProcessed));
        $this->line(sprintf('Total de Notes encontradas no destino (status %d): %d', $status, $totalDestinationRecordsFound));
        $this->line(sprintf('Total de OVs faltantes: %d', $totalMissing));
        $this->line('');

        if ($totalMissing > 0) {
            $this->error('OVs Faltantes encontradas:'); // Usar error para destacar
            foreach ($missing as $ov) {
                $this->line("  - {$ov}");
            }
        } else {
            $this->info('Nenhuma OV faltante encontrada para o status e período especificados. Tudo certo!');
        }

        return Command::SUCCESS;
    }
}
