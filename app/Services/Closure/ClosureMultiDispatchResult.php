<?php

namespace App\Services\Closure;

use Illuminate\Support\Collection;

/**
 * Resultado do despacho de Ordens de várias Notas: um item por Ordem e um resultado por Nota.
 */
class ClosureMultiDispatchResult
{
    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_ALREADY = 'already_dispatched';

    public const STATUS_REFUSED = 'refused';

    /**
     * @param array<int, array{order_id: int, note_id: int|null, status: string, message: string|null, meta_type: string|null}> $orders
     * @param array<int, ClosureDispatchResult> $notes resultado do despacho por note_id (somente Notas com sucesso)
     */
    public function __construct(
        public readonly array $orders,
        public readonly array $notes,
    ) {
    }

    /** @return Collection<int, int> */
    public function dispatchedOrderIds(): Collection
    {
        return $this->idsWithStatus(self::STATUS_DISPATCHED);
    }

    /** @return Collection<int, int> */
    public function alreadyDispatchedOrderIds(): Collection
    {
        return $this->idsWithStatus(self::STATUS_ALREADY);
    }

    /** @return Collection<int, int> */
    public function refusedOrderIds(): Collection
    {
        return $this->idsWithStatus(self::STATUS_REFUSED);
    }

    /** @return array<int, string> motivo por order_id recusada */
    public function refusals(): array
    {
        return collect($this->orders)
            ->where('status', self::STATUS_REFUSED)
            ->mapWithKeys(fn (array $item) => [$item['order_id'] => (string) $item['message']])
            ->all();
    }

    public function hasRefusals(): bool
    {
        return $this->refusedOrderIds()->isNotEmpty();
    }

    /** Sucesso parcial: há Ordens despachadas/já despachadas e também recusadas. */
    public function isPartial(): bool
    {
        return $this->hasRefusals() && count($this->orders) > $this->refusedOrderIds()->count();
    }

    /** @return Collection<int, int> */
    private function idsWithStatus(string $status): Collection
    {
        return collect($this->orders)->where('status', $status)->pluck('order_id')->values();
    }
}
