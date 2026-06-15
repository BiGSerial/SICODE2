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
    protected $description = 'Remove medidas ausentes na origem sem ProtestJob e encerra as que possuem histórico';

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

        // Medidas não atualizadas pela sincronização são consideradas ausentes na origem.
        $baseQuery = MedProtest::query()
            ->where('updated_at', '<', $cutoff)
            ->where('statusSist', 'MEDA')
            ->withExists('ProtestJobs')
            ->with('Protest:id,nota');

        $totalCandidates = (clone $baseQuery)->count();

        if ($totalCandidates === 0) {
            $this->info('Nenhum registro elegível encontrado. Nada a fazer. ✅');
            return self::SUCCESS;
        }

        $this->line("Registros candidatos: {$totalCandidates}");
        $this->line('Data de corte (updated_at <): ' . $cutoff->toDateTimeString());
        $this->line('Modo: ' . ($dryRun ? 'DRY RUN (simulação, nada será alterado)' : 'EXECUÇÃO REAL'));
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
                ' - MedProtest ID: %d | protest: %s | statusSist: %s | ação: %s | updated_at: %s',
                $med->id,
                optional($med->protest)->nota ?? 'N/A',
                $med->statusSist ?? 'N/A',
                $med->protest_jobs_exists ? 'encerrar como MEDE' : 'excluir',
                $med->updated_at ? $med->updated_at->toDateTimeString() : 'N/A'
            ));
        }

        $this->newLine();

        if (!$dryRun && !$force) {
            if (! $this->confirm('Confirmar saneamento desses registros?', true)) {
                $this->warn('Operação cancelada pelo usuário.');
                return self::SUCCESS;
            }
        }

        $this->line('Processando registros...');
        $bar = $this->createProgressBar($totalCandidates);
        $bar->start();

        $deletedCount = 0;
        $closedCount  = 0;
        $processed    = 0;

        // Processamento em blocos para não estourar memória
        (clone $baseQuery)
            ->orderBy('id')
            ->chunkById(1000, function ($meds) use (&$closedCount, &$deletedCount, &$processed, $dryRun, $bar) {
                /** @var \App\Models\MedProtest $med */
                foreach ($meds as $med) {
                    $processed++;

                    if (! $dryRun) {
                        if ($med->protest_jobs_exists) {
                            $med->update([
                                'statusSist' => 'MEDE',
                                'dtFimMedida' => today(),
                            ]);
                            $closedCount++;
                        } else {
                            $med->delete();
                            $deletedCount++;
                        }
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
            $this->line('DRY RUN: nenhum registro foi alterado. ✅');
        } else {
            $this->line("Registros encerrados como MEDE: {$closedCount} ✅");
            $this->line("Registros efetivamente deletados: {$deletedCount} 🗑️");
        }

        return self::SUCCESS;
    }
}
