<?php

namespace App\Services\Closure;

class ClosureOrderBatchItemResult
{
    public const STATUS_ELIGIBLE = 'eligible';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_REFUSED = 'refused';

    public function __construct(
        public readonly int $stateId,
        public readonly ?int $orderId,
        public readonly string $status,
        public readonly ?string $reason = null,
    ) {
    }

    public function isEligible(): bool
    {
        return $this->status === self::STATUS_ELIGIBLE;
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }
}
