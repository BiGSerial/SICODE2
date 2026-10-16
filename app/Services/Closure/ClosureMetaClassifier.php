<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureOrderState;
use App\Models\{ClosureTarget, Operation, Order};
use Carbon\{Carbon, CarbonInterface};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Tipo de meta da Ordem de Encerramento (docs/encerramento/REFORMULACAO_ENCERRAMENTO.md, §2).
 *
 *  - P (Passivo):     fimReal da 0020 antes do mês anterior (ou target de ciclo passado) e ainda não encerrada;
 *  - M (Meta atual):  fimReal da 0020 no mês anterior (ou target do ciclo corrente);
 *  - F (Meta futura): fimReal da 0020 no mês corrente.
 *
 * Dinâmico enquanto a atividade está aberta; congelado em closure_order_states.closed_meta_type ao encerrar.
 */
class ClosureMetaClassifier
{
    public const TYPE_PASSIVE = 'P';

    public const TYPE_META = 'M';

    public const TYPE_FUTURE = 'F';

    public const TYPES = [self::TYPE_PASSIVE, self::TYPE_META, self::TYPE_FUTURE];

    public static function label(?string $type): string
    {
        return match (self::letter($type)) {
            self::TYPE_PASSIVE => 'Passivo',
            self::TYPE_META    => 'Meta atual',
            self::TYPE_FUTURE  => 'Meta futura',
            default            => '—',
        };
    }

    public static function badgeClass(?string $type): string
    {
        return match (self::letter($type)) {
            self::TYPE_PASSIVE => 'bg-danger',
            self::TYPE_META    => 'bg-success',
            self::TYPE_FUTURE  => 'bg-primary',
            default            => 'bg-secondary',
        };
    }

    /** Letra normalizada (P/M/F) ou null. */
    public static function letter(?string $type): ?string
    {
        $type = strtoupper(trim((string) $type));

        return in_array($type, self::TYPES, true) ? $type : null;
    }

    public static function badge(?string $type): string
    {
        $letter = self::letter($type);

        return sprintf(
            '<span class="badge %s" title="%s">%s</span>',
            self::badgeClass($letter),
            e(self::label($letter)),
            $letter ?? '—'
        );
    }

    /** Período (YYYY-MM) usado no congelamento. */
    public static function periodFor(?CarbonInterface $at = null): string
    {
        return ($at ?? now())->format('Y-m');
    }

    /**
     * Tipo atual da Ordem (ou o congelado). Null quando não há como classificar
     * (sem target e sem 0020 CONF com fimReal, ou fimReal além do mês corrente).
     */
    public function typeFor(Order $order, ?CarbonInterface $asOf = null): ?string
    {
        return $this->typesFor(collect([$order]), $asOf)->get($order->id);
    }

    /**
     * Classifica várias Ordens com 3 consultas, sem N+1.
     *
     * @param Collection<int, Order> $orders
     * @return Collection<int, string|null> tipo por order_id
     */
    public function typesFor(Collection $orders, ?CarbonInterface $asOf = null): Collection
    {
        $ids = $orders->pluck('id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $frozen = ClosureOrderState::query()
            ->whereIn('order_id', $ids)
            ->whereNotNull('closed_meta_type')
            ->pluck('closed_meta_type', 'order_id');

        $targets = ClosureTarget::query()
            ->join('closure_cycles', 'closure_cycles.id', '=', 'closure_targets.closure_cycle_id')
            ->whereIn('closure_targets.order_id', $ids)
            ->selectRaw('closure_targets.order_id, (closure_cycles.year * 100 + closure_cycles.month) as period_key')
            ->pluck('period_key', 'order_id');

        $fimReal = Operation::query()
            ->whereIn('order_id', $ids)
            ->where('operacao', '0020')
            ->where('status', 'like', 'CONF%')
            ->whereNotNull('fimReal')
            ->groupBy('order_id')
            ->selectRaw('order_id, MAX(fimReal) as fim_real')
            ->pluck('fim_real', 'order_id');

        $asOf    = $asOf ?? now();
        $current = (int) $asOf->format('Ym');
        $result  = collect();

        foreach ($ids as $id) {
            $result[$id] = $this->resolve(
                $frozen->get($id),
                $targets->has($id) ? (int) $targets->get($id) : null,
                $fimReal->get($id),
                $current,
                $asOf
            );
        }

        return $result;
    }

    /**
     * Elegível a aparecer no Encerramento: LIB%, não cancelada, não encerrada no SAP,
     * com 0020 CONF + fimReal até o mês corrente e Nota válida.
     */
    public function scopeEligible(Builder $query): Builder
    {
        $nextMonthStart = now()->startOfMonth()->addMonth()->toDateString();

        return $query
            ->where('orders.statusSist', 'like', 'LIB%')
            ->where('orders.statusSist', 'not like', 'ENTE%')
            ->where('orders.statusSist', 'not like', 'ENCE%')
            ->where('orders.canceled', false)
            ->whereHas('Note', fn ($note) => $note->excludeCanceledFullDone()
                ->whereNotNull('note')
                ->where('note', '!=', '')
                ->where('note', '!=', '0'))
            ->whereHas('Operations', fn ($operation) => $operation
                ->where('operacao', '0020')
                ->where('status', 'like', 'CONF%')
                ->whereNotNull('fimReal')
                ->where('fimReal', '<', $nextMonthStart));
    }

    /**
     * Filtra em SQL pelo tipo atual (ou congelado). Aceita 'P', 'M', 'F' ou lista.
     *
     * @param string|array<int, string> $types
     */
    public function whereMetaType(Builder $query, string|array $types): Builder
    {
        $types = array_values(array_filter(array_map(fn ($type) => self::letter($type), (array) $types)));

        if ($types === []) {
            return $query->whereRaw('1 = 0');
        }

        [$sql, $bindings] = $this->metaTypeSql();

        // Invariante: closure_targets tem unique(order_id); o subselect `limit 1` abaixo equivale ao join PHP em typesFor().
        return $query->whereRaw(
            "({$sql}) in (" . implode(',', array_fill(0, count($types), '?')) . ')',
            array_merge($bindings, $types)
        );
    }

    /** Acrescenta a coluna `meta_type` ao select (use junto de select('orders.*')). */
    public function withMetaType(Builder $query): Builder
    {
        [$sql, $bindings] = $this->metaTypeSql();

        return $query->selectRaw("({$sql}) as meta_type", $bindings);
    }

    /**
     * Expressão SQL do tipo; espera a tabela `orders` na consulta.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function metaTypeSql(): array
    {
        $now       = now();
        $current   = (int) $now->format('Ym');
        $monthFrom = $now->copy()->startOfMonth()->toDateString();
        $monthTo   = $now->copy()->startOfMonth()->addMonth()->toDateString();
        $prevFrom  = $now->copy()->startOfMonth()->subMonthNoOverflow()->toDateString();

        $frozen = '(select s.closed_meta_type from closure_order_states s where s.order_id = orders.id)';
        $target = '(select (c.year * 100 + c.month) from closure_targets t'
            . ' join closure_cycles c on c.id = t.closure_cycle_id where t.order_id = orders.id limit 1)';
        $fim = "(select max(o.fimReal) from operations o where o.order_id = orders.id and o.operacao = '0020'"
            . " and o.status like 'CONF%' and o.fimReal is not null)";

        $sql = "coalesce({$frozen}, case"
            . " when {$target} is not null then case when {$target} > ? then 'F' when {$target} = ? then 'M' else 'P' end"
            . " when {$fim} is null then null"
            . " when {$fim} >= ? then case when {$fim} < ? then 'F' else null end"
            . " when {$fim} >= ? then 'M'"
            . " else 'P' end)";

        return [$sql, [$current, $current, $monthFrom, $monthTo, $prevFrom]];
    }

    private function resolve(?string $frozen, ?int $targetPeriod, mixed $fimReal, int $current, CarbonInterface $asOf): ?string
    {
        if ($frozen !== null && self::letter($frozen)) {
            return self::letter($frozen);
        }

        if ($targetPeriod !== null) {
            return match (true) {
                $targetPeriod > $current   => self::TYPE_FUTURE,
                $targetPeriod === $current => self::TYPE_META,
                default                    => self::TYPE_PASSIVE,
            };
        }

        if ($fimReal === null) {
            return null;
        }

        $date = Carbon::parse($fimReal);
        $key  = (int) $date->format('Ym');

        return match (true) {
            $key > $current                                                           => null,
            $key === $current                                                         => self::TYPE_FUTURE,
            $date->format('Ym') === $asOf->copy()->subMonthNoOverflow()->format('Ym') => self::TYPE_META,
            default                                                                   => self::TYPE_PASSIVE,
        };
    }
}
