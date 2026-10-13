<?php

namespace App\Services\Closure;

use Illuminate\Support\Collection;

class ClosureOrderBatchPreview
{
    /**
     * @param Collection<int, ClosureOrderBatchItemResult> $items
     */
    public function __construct(
        public readonly string $action,
        public readonly Collection $items,
    ) {
    }

    public function eligible(): Collection
    {
        return $this->items->filter(fn (ClosureOrderBatchItemResult $item) => $item->isEligible())->values();
    }

    public function refused(): Collection
    {
        return $this->items->filter(fn (ClosureOrderBatchItemResult $item) => !$item->isEligible())->values();
    }
}
