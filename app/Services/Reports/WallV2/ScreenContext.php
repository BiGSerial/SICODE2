<?php

namespace App\Services\Reports\WallV2;

class ScreenContext
{
    public function __construct(
        public readonly string $type,
        public readonly string $fixedChart,
    ) {
    }

    public function isFixed(): bool
    {
        return $this->type === 'fixed_chart';
    }
}
