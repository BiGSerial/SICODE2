<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\Pep as SourcePep;
use App\Models\Pep;
use Illuminate\Console\Command;
use Throwable;

class SyncPeps extends Command
{
    protected $signature = 'sicode:sync-peps {--chunk=1000 : Registros por lote}';

    protected $description = 'Sincroniza os saldos PEP do SQL Server para a base local.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $total     = SourcePep::query()->count();
        $synced    = 0;

        $this->info("Sincronizando {$total} PEP(s)...");

        try {
            SourcePep::query()
                ->select(['objeto', 'ano', 'orcamento', 'real', 'compromisso', 'planResOrd', 'disposto', 'disponivel', 'regiao'])
                ->whereNotNull('objeto')
                ->orderBy('objeto')
                ->chunk($chunkSize, function ($sourceRows) use (&$synced) {
                    $now  = now();
                    $rows = $sourceRows
                        ->map(fn (SourcePep $pep) => [
                            'objeto'       => trim((string) $pep->objeto),
                            'ano'          => $pep->ano,
                            'orcamento'    => $pep->orcamento,
                            'real'         => $pep->real,
                            'compromisso'  => $pep->compromisso,
                            'plan_res_ord' => $pep->planResOrd,
                            'disposto'     => $pep->disposto,
                            'disponivel'   => $pep->disponivel,
                            'regiao'       => $pep->regiao,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ])
                        ->filter(fn (array $row) => $row['objeto'] !== '')
                        ->values()
                        ->all();

                    if (!$rows) {
                        return;
                    }

                    Pep::query()->upsert($rows, ['objeto'], [
                        'ano',
                        'orcamento',
                        'real',
                        'compromisso',
                        'plan_res_ord',
                        'disposto',
                        'disponivel',
                        'regiao',
                        'updated_at',
                    ]);

                    $synced += count($rows);
                });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Falha ao sincronizar PEPs: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$synced} PEP(s) sincronizados.");

        return self::SUCCESS;
    }
}
