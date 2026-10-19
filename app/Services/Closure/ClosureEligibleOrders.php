<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureOrderState;
use App\Models\{ClosureTarget, Operation, Order};
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Conjunto de Ordens do Encerramento (elegíveis OR com estado OR com target), com tipo P/M/F e fimReal da 0020.
 *
 * A parte cara (elegibilidade sobre as ~75k Ordens: LIB%, Nota válida, 0020 CONF) é cacheada por mês
 * (chave inclui YYYY-MM e versão; TTL curto). A parte barata (estados, congelamento, targets) é lida ao vivo
 * a cada chamada, então dispatch/atribuição/encerramento aparecem sem invalidar o cache pesado.
 * `forget()` existe para quando a própria elegibilidade mudar (ex.: comandos de sync, testes).
 */
class ClosureEligibleOrders
{
    public const VERSION = 'v3';

    public const TTL = 300;

    private ?array $memo = null;

    public function __construct(private ?ClosureMetaClassifier $classifier = null)
    {
        $this->classifier ??= new ClosureMetaClassifier();
    }

    public static function cacheKey(?CarbonInterface $at = null): string
    {
        return 'closure:eligible-orders:' . self::VERSION . ':' . ($at ?? now())->format('Y-m');
    }

    public static function forget(): void
    {
        Cache::forget(self::cacheKey());
        Cache::forget(self::citiesCacheKey());
        Cache::forget(self::informCompaniesCacheKey());
    }

    public static function informCompaniesCacheKey(?CarbonInterface $at = null): string
    {
        return 'closure:inform-companies:' . self::VERSION . ':' . ($at ?? now())->format('Y-m');
    }

    public static function citiesCacheKey(?CarbonInterface $at = null): string
    {
        return 'closure:eligible-cities:' . self::VERSION . ':' . ($at ?? now())->format('Y-m');
    }

    /**
     * @return array<int, array{type: ?string, fim: ?string, competence: ?string}> por order_id (chave int)
     */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $eligible = Cache::remember(self::cacheKey(), self::TTL, fn () => $this->computeEligible());

        $states  = ClosureOrderState::query()->toBase()->get(['order_id', 'closed_meta_type', 'closed_meta_period'])->keyBy('order_id');
        $frozen  = $states->pluck('closed_meta_type', 'order_id')->all();
        $targets = ClosureTarget::query()
            ->join('closure_cycles', 'closure_cycles.id', '=', 'closure_targets.closure_cycle_id')
            ->selectRaw('closure_targets.order_id, (closure_cycles.year * 100 + closure_cycles.month) as period_key')
            ->pluck('period_key', 'order_id')->all();

        $ids     = array_keys($eligible + $frozen + $targets);
        $missing = array_values(array_filter($ids, fn ($id) => !array_key_exists($id, $eligible)));
        $extra   = [];

        foreach (array_chunk($missing, 1000) as $chunk) {
            $extra += Operation::query()->whereIn('order_id', $chunk)
                ->where('operacao', '0020')->where('status', 'like', 'CONF%')->whereNotNull('fimReal')
                ->groupBy('order_id')->selectRaw('order_id, MAX(fimReal) as fim')->pluck('fim', 'order_id')->all();
        }

        $now     = now();
        $current = (int) $now->format('Ym');
        $result  = [];

        foreach ($ids as $id) {
            $fim    = isset($eligible[$id]) ? $eligible[$id]['fim'] : ($extra[$id] ?? null);
            $target = isset($targets[$id]) ? (int) $targets[$id] : null;

            // Congelada (encerrada) > ciclo do target > mês seguinte ao fimReal (a parte do fimReal já vem no cache).
            $competence = ($frozen[$id] ?? null) !== null && ($states[$id]->closed_meta_period ?? null)
                ? $states[$id]->closed_meta_period
                : ($target !== null
                    ? $this->classifier->competence(null, $target, null, $current)
                    : (isset($eligible[$id]) ? $eligible[$id]['competence'] : ClosureMetaClassifier::competenceFromFim($fim, $current)));

            $result[$id] = [
                'type' => $this->classifier->resolve(
                    $frozen[$id] ?? null,
                    $target,
                    $fim,
                    $current,
                    $now
                ),
                'fim'        => $fim,
                'competence' => $competence,
            ];
        }

        return $this->memo = $result;
    }

    /**
     * Empresa da relação com o INFORME, por Ordem (order_id => company_id): a mesma fonte da coluna EMPRESA da
     * Lista para Medição (`work_reports.company_id`; sem informe, `partials.company_id`).
     * Regra, por Ordem, parando na primeira que existir (empate = registro mais recente, maior id):
     *  1. informe não cancelado ligado à Ordem (`order_work_report`);
     *  2. parcial ligada à Ordem (`order_partial`);
     *  3. informe não cancelado da Nota da Ordem (informe sem vínculo explícito com Ordens).
     * Cacheado com o mesmo TTL do conjunto; Ordens do conjunto que ficaram fora do cache são calculadas ao vivo.
     *
     * @return array<int, string> order_id => company_id (UUID; '' = sem informe/parcial)
     */
    public function informCompanies(): array
    {
        $ids     = array_keys($this->all());
        $cached  = Cache::remember(self::informCompaniesCacheKey(), self::TTL, fn () => $this->computeInformCompanies($ids));
        $missing = array_values(array_filter($ids, fn ($id) => !array_key_exists($id, $cached)));

        // Ordens sem empresa são guardadas como '' no cache para não serem recalculadas a cada chamada.
        return $missing === [] ? $cached : $cached + $this->computeInformCompanies($missing);
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, string> order_id => company_id ('' = sem informe/parcial)
     */
    private function computeInformCompanies(array $ids): array
    {
        $map = array_fill_keys($ids, '');

        foreach (array_chunk($ids, 1000) as $chunk) {
            $found = [];

            $sources = [
                fn (array $in) => DB::table('order_work_report as p')->join('work_reports as w', 'w.id', '=', 'p.work_report_id')
                    ->whereIn('p.order_id', $in)->where('w.canceled', false)->whereNotNull('w.company_id')
                    ->orderBy('w.id')->get(['p.order_id as oid', 'w.company_id as cid']),
                fn (array $in) => DB::table('order_partial as p')->join('partials as pt', 'pt.id', '=', 'p.partial_id')
                    ->whereIn('p.order_id', $in)->whereNotNull('pt.company_id')
                    ->orderBy('pt.id')->get(['p.order_id as oid', 'pt.company_id as cid']),
                fn (array $in) => DB::table('orders as o')->join('work_reports as w', 'w.note_id', '=', 'o.note_id')
                    ->whereIn('o.id', $in)->where('w.canceled', false)->whereNotNull('w.company_id')
                    ->orderBy('w.id')->get(['o.id as oid', 'w.company_id as cid']),
            ];

            foreach ($sources as $source) {
                $pending = array_values(array_diff($chunk, array_keys($found)));

                if ($pending === []) {
                    break;
                }

                foreach ($source($pending) as $row) {
                    $found[(int) $row->oid] = (string) $row->cid; // ordenado por id asc: o mais recente sobrescreve
                }
            }

            $map = $found + $map;
        }

        return $map;
    }

    /** @return array<int, array{fim: ?string, competence: ?string}> order_id => fimReal e competência pelo fimReal (somente elegíveis) */
    private function computeEligible(): array
    {
        $rows = $this->classifier->scopeEligible(Order::query())
            ->select('orders.id')
            ->selectRaw("(select max(o.fimReal) from operations o where o.order_id = orders.id and o.operacao = '0020' and o.status like 'CONF%' and o.fimReal is not null) as fim")
            ->get();

        $map     = [];
        $current = (int) now()->format('Ym');

        foreach ($rows as $row) {
            $map[(int) $row->id] = ['fim' => $row->fim, 'competence' => ClosureMetaClassifier::competenceFromFim($row->fim, $current)];
        }

        return $map;
    }
}
