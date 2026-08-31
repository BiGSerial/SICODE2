<?php

namespace App\Console\Commands\Closure;

use App\Models\{ClosureCycle, ClosureTarget, Operation, Order};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreezeTarget extends Command
{
    protected const ENTRY_RULE = 'lib_op20_conf_fimreal_v1';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:freeze-target
        {competencia? : Competência alvo no formato AAAA-MM (padrão: mês corrente)}
        {--freeze : Congela de fato (cria closure_cycle/closure_targets). Sem esta flag, roda em modo dry-run.}
        {--by= : ID do usuário responsável pelo congelamento (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Congela a meta de encerramento de uma competência (dry-run por padrão; use --freeze para gravar).';

    public function handle(): int
    {
        [$year, $month] = $this->resolveCompetencia();

        $referenceStart = Carbon::create($year, $month, 1)->subMonthNoOverflow()->startOfMonth();
        $referenceEnd   = (clone $referenceStart)->endOfMonth();

        $label = sprintf('%04d-%02d', $year, $month);

        $this->info("Competência alvo: {$label} (referência de fimReal: {$referenceStart->format('Y-m-d')} a {$referenceEnd->format('Y-m-d')})");

        $eligibleOrders = $this->eligibleOrdersQuery($referenceStart, $referenceEnd)->get();

        $this->info('Ordens elegíveis encontradas: ' . $eligibleOrders->count());

        if (!$this->option('freeze')) {
            $this->warn('Modo dry-run (nenhum dado foi gravado). Use --freeze para congelar de verdade.');
            $this->table(
                ['order_id', 'ordem', 'note_id', 'statusSist'],
                $eligibleOrders->take(20)->map(fn (Order $order) => [
                    $order->id,
                    $order->ordem,
                    $order->note_id,
                    $order->statusSist,
                ])
            );

            if ($eligibleOrders->count() > 20) {
                $this->line('(mostrando as 20 primeiras — total acima)');
            }

            return self::SUCCESS;
        }

        $cycle = ClosureCycle::firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['label' => $label, 'status' => ClosureCycle::STATUS_OPEN]
        );

        if ($cycle->status === ClosureCycle::STATUS_FROZEN) {
            $this->error("A competência {$label} já está congelada (frozen_at: {$cycle->frozen_at}). Nada foi alterado.");

            return self::FAILURE;
        }

        $created = 0;

        DB::transaction(function () use ($eligibleOrders, $cycle, $referenceStart, $referenceEnd, &$created) {
            foreach ($eligibleOrders as $order) {
                $operation = $this->matchingOperation($order, $referenceStart, $referenceEnd);

                ClosureTarget::create([
                    'closure_cycle_id' => $cycle->id,
                    'order_id'         => $order->id,
                    'note_id'          => $order->note_id,
                    'entry_rule'       => self::ENTRY_RULE,
                    'entry_reference'  => [
                        'operation_id'     => $operation?->id,
                        'fim_real'         => $operation?->fimReal?->toDateString(),
                        'operation_status' => $operation?->status,
                    ],
                    'snapshot_status_sist' => $order->statusSist,
                    'frozen_at'            => now(),
                ]);

                $created++;
            }

            $cycle->update([
                'status'    => ClosureCycle::STATUS_FROZEN,
                'frozen_at' => now(),
                'frozen_by' => $this->option('by'),
            ]);
        });

        $this->info("Congelamento concluído: {$created} Ordens registradas na meta {$label}.");

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

    private function eligibleOrdersQuery(Carbon $referenceStart, Carbon $referenceEnd)
    {
        return Order::query()
            ->where('statusSist', 'like', 'LIB%')
            ->where('canceled', false)
            ->whereDoesntHave('ClosureTarget')
            ->whereHas('Note', function ($query) {
                $query->excludeCanceledFullDone();
            })
            ->whereHas('Operations', function ($query) use ($referenceStart, $referenceEnd) {
                $query->where('operacao', '0020')
                    ->where('status', 'like', 'CONF%')
                    ->whereNotNull('fimReal')
                    ->whereBetween('fimReal', [$referenceStart->toDateString(), $referenceEnd->toDateString()]);
            });
    }

    private function matchingOperation(Order $order, Carbon $referenceStart, Carbon $referenceEnd): ?Operation
    {
        return Operation::query()
            ->where('order_id', $order->id)
            ->where('operacao', '0020')
            ->where('status', 'like', 'CONF%')
            ->whereNotNull('fimReal')
            ->whereBetween('fimReal', [$referenceStart->toDateString(), $referenceEnd->toDateString()])
            ->first();
    }
}
