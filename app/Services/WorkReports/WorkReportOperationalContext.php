<?php

namespace App\Services\WorkReports;

use App\Models\{Note, Production, WorkReport};
use Illuminate\Support\Collection;

class WorkReportOperationalContext
{
    public const STATUS_VALID            = 'valid';
    public const STATUS_LEGACY_RESOLVED  = 'legacy_resolved';
    public const STATUS_AMBIGUOUS        = 'ambiguous';
    public const STATUS_INVALID          = 'invalid';
    public const STATUS_MISSING_RELATION = 'missing_relation';

    public function __construct(
        public readonly string $status,
        public readonly ?string $reason = null,
        public readonly ?WorkReport $workReport = null,
        public readonly ?Note $note = null,
        public readonly ?Production $production = null,
        public readonly ?string $stage = null,
        public readonly array $finalScopes = [],
        public readonly Collection $orders = new Collection(),
    ) {
    }

    public function trusted(): bool
    {
        return in_array($this->status, [
            self::STATUS_VALID,
            self::STATUS_LEGACY_RESOLVED,
        ], true);
    }

    public function ambiguous(): bool
    {
        return $this->status === self::STATUS_AMBIGUOUS;
    }
}
