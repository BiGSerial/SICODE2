<?php

namespace App\Console\Commands\Closure;

use App\Models\Order;
use App\Services\Closure\ClosureTargetFreezer;
use Illuminate\Console\Command;

class FreezeTarget extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:freeze-target
        {competencia? : Competência alvo no formato AAAA-MM (padrão: mês corrente)}
        {--freeze : Grava o snapshot de fato (cria closure_cycle/closure_targets). Sem esta flag, roda em modo dry-run.}
        {--lock : Junto com --freeze, trava a competência (status=FROZEN) após gravar. Sem --lock, a competência fica OPEN e aceita rodar de novo depois (ex.: injeção às 0:00 do dia seguinte).}
        {--by= : ID do usuário responsável pelo congelamento (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grava/atualiza o snapshot da meta de UMA competência (uso mensal recorrente). Fluxo '
        . 'combinado: rodar no dia 1 sem --lock (primeiro snapshot), rodar de novo às 0:00 do dia 2 sem --lock '
        . '(injeta Ordens sincronizadas com atraso) e só então rodar com --lock (trava de vez). Para o backlog '
        . 'histórico na entrada em operação, usar closure:backfill-targets.';

    public function handle(ClosureTargetFreezer $freezer): int
    {
        [$year, $month] = $this->resolveCompetencia();

        $result = $freezer->freeze($year, $month, (bool) $this->option('freeze'), $this->option('by'), (bool) $this->option('lock'));

        $this->info(sprintf(
            'Competência alvo: %s (referência de fimReal: %s a %s)',
            $result['label'],
            $result['reference_start']->format('Y-m-d'),
            $result['reference_end']->format('Y-m-d')
        ));

        $this->info('Ordens elegíveis (ainda sem closure_target) encontradas: ' . $result['orders']->count());

        if (!$this->option('freeze')) {
            $this->warn('Modo dry-run (nenhum dado foi gravado). Use --freeze para gravar o snapshot de verdade.');
            $this->table(
                ['order_id', 'ordem', 'note_id', 'statusSist'],
                $result['orders']->take(20)->map(fn (Order $order) => [
                    $order->id,
                    $order->ordem,
                    $order->note_id,
                    $order->statusSist,
                ])
            );

            if ($result['orders']->count() > 20) {
                $this->line('(mostrando as 20 primeiras — total acima)');
            }

            return self::SUCCESS;
        }

        if ($result['already_frozen']) {
            $this->error("A competência {$result['label']} já está travada (frozen_at: {$result['cycle']->frozen_at}). Nada foi alterado.");

            return self::FAILURE;
        }

        $this->info("Snapshot gravado: {$result['created']} Ordens novas registradas na meta {$result['label']}.");

        if ($result['locked_now']) {
            $this->info("Competência {$result['label']} travada (FROZEN) — não aceita mais novas Ordens.");
        } else {
            $this->line("Competência {$result['label']} continua OPEN — pode rodar este comando de novo para injetar novas Ordens, ou com --lock para travar.");
        }

        return self::SUCCESS;
    }

    private function resolveCompetencia(): array
    {
        $competencia = $this->argument('competencia');

        if (!$competencia) {
            return [(int) now()->year, (int) now()->month];
        }

        [$year, $month] = explode('-', $competencia);

        return [(int) $year, (int) $month];
    }
}
