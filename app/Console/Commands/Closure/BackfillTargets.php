<?php

namespace App\Console\Commands\Closure;

use App\Services\Closure\ClosureTargetFreezer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillTargets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:backfill-targets
        {--freeze : Congela de fato. Sem esta flag, roda em modo dry-run.}
        {--by= : ID do usuário responsável pelo congelamento (opcional)}
        {--until= : Última competência a processar, formato AAAA-MM (padrão: mês corrente).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uso único, no dia em que o módulo entra em operação: monta retroativamente uma '
        . 'competência (closure_cycle) para CADA mês histórico em que havia Ordens elegíveis, preservando a '
        . 'mensalização correta (fimReal no mês M entra na meta do mês M+1) — em vez de jogar tudo na competência atual.';

    public function handle(ClosureTargetFreezer $freezer): int
    {
        $untilOption = $this->option('until');
        $until       = $untilOption
            ? Carbon::createFromFormat('Y-m', $untilOption)->startOfMonth()
            : now()->startOfMonth();

        $referenceMonths = $this->pendingReferenceMonths($until);

        if ($referenceMonths->isEmpty()) {
            $this->info('Nenhuma Ordem elegível pendente de backfill.');

            return self::SUCCESS;
        }

        $labels = $referenceMonths->map(fn (Carbon $m) => $m->copy()->addMonthNoOverflow()->format('Y-m'));
        $this->info('Competências a processar: ' . $labels->implode(', '));

        $totalCreated = 0;

        foreach ($referenceMonths as $referenceMonth) {
            $target = $referenceMonth->copy()->addMonthNoOverflow();

            // Competências históricas são travadas (lock) na mesma passada — diferente da competência
            // corrente (closure:freeze-target), não existe "dia 2" para injetar Ordens de um mês já
            // encerrado há muito tempo.
            $result = $freezer->freeze($target->year, $target->month, (bool) $this->option('freeze'), $this->option('by'), true);

            $count = $result['orders']->count();

            if (!$this->option('freeze')) {
                $this->line("[dry-run] {$result['label']}: {$count} Ordens elegíveis (referência {$referenceMonth->format('Y-m')}).");

                continue;
            }

            if ($result['already_frozen']) {
                $this->warn("{$result['label']} já estava congelada — pulado.");

                continue;
            }

            $this->info("{$result['label']}: {$result['created']} Ordens congeladas (referência {$referenceMonth->format('Y-m')}).");
            $totalCreated += $result['created'];
        }

        if ($this->option('freeze')) {
            $this->info("Backfill concluído: {$totalCreated} Ordens no total.");
        } else {
            $this->warn('Modo dry-run — nenhum dado foi gravado. Use --freeze para gravar de verdade.');
        }

        return self::SUCCESS;
    }

    /**
     * Descobre, a partir de operations.fimReal, todos os meses de referência que ainda têm
     * Ordens elegíveis sem closure_target, até o mês anterior a $until (inclusive).
     */
    private function pendingReferenceMonths(Carbon $until): Collection
    {
        $lastReferenceMonth = $until->copy()->subMonthNoOverflow()->startOfMonth();

        return DB::table('orders')
            ->join('operations', 'operations.order_id', '=', 'orders.id')
            ->where('orders.statusSist', 'like', 'LIB%')
            ->where('orders.canceled', false)
            ->where('operations.operacao', '0020')
            ->where('operations.status', 'like', 'CONF%')
            ->whereNotNull('operations.fimReal')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('closure_targets')
                    ->whereColumn('closure_targets.order_id', 'orders.id');
            })
            ->selectRaw("DATE_FORMAT(operations.fimReal, '%Y-%m-01') as ref_month")
            ->distinct()
            ->pluck('ref_month')
            ->map(fn ($m) => Carbon::parse($m))
            ->filter(fn (Carbon $m) => $m->lte($lastReferenceMonth))
            ->sort()
            ->values();
    }
}
