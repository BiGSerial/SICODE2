<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\MedProtest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneProtestMed extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:prune-protest-med
                            {--dry : Executa em modo de simulação, sem deletar os registros}
                            {--force : Força a execução sem pedir confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune MedProtest records that are no longer in the source system and have no ProtestJob associated';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $activityName = 'Prune MedProtest órfãos';
        $dryRun       = (bool) $this->option('dry');
        $force        = (bool) $this->option('force');

        $this->newLine();
        $this->info("=== [$activityName] Iniciando " . ($dryRun ? '(DRY RUN)' : '') . " ===");

        $cutoff = Carbon::now()->subHours(1);

        // Base query: medidas sem atualização há mais de 48h e sem ProtestJob associado
        $baseQuery = MedProtest::query()
            ->where('updated_at', '<', $cutoff)
            ->doesntHave('ProtestJobs')
            ->with('Protest:id,nota');

        $totalCandidates = (clone $baseQuery)->count();

        if ($totalCandidates === 0) {
            $this->info('Nenhum registro elegível encontrado. Nada a fazer. ✅');
            return self::SUCCESS;
        }

        $this->line("Registros candidatos: {$totalCandidates}");
        $this->line('Data de corte (updated_at <): ' . $cutoff->toDateTimeString());
        $this->line('Modo: ' . ($dryRun ? 'DRY RUN (simulação, nada será deletado)' : 'EXECUÇÃO REAL'));
        $this->newLine();

        // Exibe uma pequena amostra para conferência
        $sample = (clone $baseQuery)
            ->orderBy('id')
            ->limit(10)
            // precisamos selecionar a foreign key (protest_id) se for escolher colunas,
            // caso contrário deixe vazio para selecionar todas as colunas.
            ->get(['id', 'protest_id', 'statusSist', 'updated_at']);

        $this->line('Exemplo de registros que serão afetados:');
        foreach ($sample as $med) {
            $this->line(sprintf(
                ' - MedProtest ID: %d | protest: %s | statusSist: %s | updated_at: %s',
                $med->id,
                optional($med->protest)->nota ?? 'N/A',
                $med->statusSist ?? 'N/A',
                $med->updated_at ? $med->updated_at->toDateTimeString() : 'N/A'
            ));
        }

        $this->newLine();

        if (!$dryRun && !$force) {
            if (! $this->confirm('Confirmar exclusão desses registros?', true)) {
                $this->warn('Operação cancelada pelo usuário.');
                return self::SUCCESS;
            }
        }

        $this->line('Processando registros...');
        $bar = $this->createProgressBar($totalCandidates);
        $bar->start();

        $deletedCount = 0;
        $processed    = 0;

        // Processamento em blocos para não estourar memória
        (clone $baseQuery)
            ->orderBy('id')
            ->chunkById(1000, function ($meds) use (&$deletedCount, &$processed, $dryRun, $bar) {
                /** @var \App\Models\MedProtest $med */
                foreach ($meds as $med) {
                    $processed++;

                    if (! $dryRun && $med->statusSist === 'MEDA') {
                        $med->delete();
                        $deletedCount++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("=== [$activityName] Concluído ===");
        $this->line("Registros candidatos totais: {$totalCandidates}");
        $this->line("Registros processados: {$processed}");
        if ($dryRun) {
            $this->line('DRY RUN: nenhum registro foi removido. ✅');
        } else {
            $this->line("Registros efetivamente deletados: {$deletedCount} 🗑️");
        }

        return self::SUCCESS;
    }
}
