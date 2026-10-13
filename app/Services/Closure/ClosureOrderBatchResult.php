<?php

namespace App\Services\Closure;

use Illuminate\Support\Collection;

class ClosureOrderBatchResult
{
    /**
     * @param Collection<int, ClosureOrderBatchItemResult> $items
     */
    public function __construct(
        public readonly string $batchId,
        public readonly string $action,
        public readonly Collection $items,
    ) {
    }

    public function successful(): Collection
    {
        return $this->items->filter(fn (ClosureOrderBatchItemResult $item) => $item->isSuccessful())->values();
    }

    public function refused(): Collection
    {
        return $this->items->filter(fn (ClosureOrderBatchItemResult $item) => !$item->isSuccessful())->values();
    }
}
