<?php

namespace App\Services\Closure;

use App\Models\Production;
use Illuminate\Support\Collection;

class ClosureDispatchResult
{
    /**
     * @param Collection<int, object> $states
     * @param Collection<int, int> $addedOrderIds
     */
    public function __construct(
        public readonly Production $production,
        public readonly Collection $states,
        public readonly Collection $addedOrderIds,
        public readonly bool $productionWasCreated,
    ) {
    }

    public function isIdempotent(): bool
    {
        return !$this->productionWasCreated && $this->addedOrderIds->isEmpty();
    }
}
