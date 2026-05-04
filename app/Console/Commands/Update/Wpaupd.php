<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\Wpaupdstatus;
use App\Models\Wpa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class Wpaupd extends Command
{
    use ShowsProgress;

    protected $signature   = 'sicode:upd_wpa';
    protected $description = 'Update WPA status from SQL Base (bulk upsert)';

    public function handle()
    {
        $log = null;

        try {
            // Ajuste conforme seu volume
            $chunkSize = 1_000;

            // Total para a barra
            $total = Wpaupdstatus::count();
            $log = new RegistroJson('upd_wpa', $this->options(), $total);

            if ($total === 0) {
                $this->info('Nenhum registro encontrado em Wpaupdstatus.');
                $log->save();
                return Command::SUCCESS;
            }

            $progressBar = $this->createProgressBar($total);
            $progressBar->setFormat('<bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s%');
            $progressBar->start();

        // Dica: upsert não dispara eventos de model. Se precisar, considere processar diferentemente.
        // Em MySQL, upsert exige índice UNIQUE/PK na coluna de conflito (production_id).
        // Veja a nota de migration ao final.

        // Para manter a ordem estável durante a paginação
            Wpaupdstatus::orderBy('production_id')->chunk($chunkSize, function ($wpas) use ($progressBar) {

            // Monte o payload a partir dos campos vindos da base SQL
            $now = Carbon::now();

            $rows = $wpas->map(function ($w) use ($now) {
                return [
                    'production_id' => $w->production_id,       // chave de conflito
                    'sector'        => $w->SectorId,
                    'stats'         => $w->statusNota,
                    'execstats'     => $w->statusExec,
                    'lat'           => $this->castNullFloat($w->Latitude),
                    'long'          => $this->castNullFloat($w->Longitude),
                    'issue_at'      => $this->castDate($w->IssueDate),
                    'completed_at'  => $this->castDate($w->ConclusionDate),
                    'updated_at'    => $now,                    // timestamps (upsert não seta sozinho)
                ];
            })->values()->all();

            // Upsert em massa: conflita por production_id e atualiza estes campos
            // Observação: se sua tabela usa created_at, você pode definir quando for insert:
            // - No MySQL, é possível incluir 'created_at' no array e usar VALUES(created_at) numa trigger/DEFAULT.
            //   Aqui manteremos simples e deixaremos o default do banco/nullable.
            Wpa::upsert(
                $rows,
                ['production_id'],                    // unique key / conflito
                ['sector', 'stats', 'execstats', 'lat', 'long', 'issue_at', 'completed_at', 'updated_at'] // colunas a atualizar
            );

            $progressBar->advance(count($rows));
            });

            $progressBar->finish();
            $this->newLine(2);
            $this->info('Upsert concluído com sucesso.');
            $log->setUpdated($total);
            $log->save();

            return Command::SUCCESS;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }

    private function castNullFloat($value)
    {
        // Converte string vazia em null; mantém float numérico
        if ($value === '' || $value === null) {
            return null;
        }
        return is_numeric($value) ? (float) $value : null;
    }

    private function castDate($value)
    {
        // Aceita formatos comuns de data/hora; retorna null em caso de vazio/ inválido
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
