<?php

namespace App\Jobs\Legal;

use App\Models\Legal\LegalDemandSubdemand;
use App\Services\Legal\LegalDemandSubdemandMetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSubdemandJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $subdemandId;
    public ?string $trigger;

    public int $uniqueFor = 120;

    public function __construct(int $subdemandId, ?string $trigger = null)
    {
        $this->onQueue('default');
        $this->subdemandId = $subdemandId;
        $this->trigger = $trigger;
    }

    public function uniqueId(): string
    {
        return 'legal-subdemand-' . $this->subdemandId;
    }

    public function handle(LegalDemandSubdemandMetricsService $metrics): void
    {
        $sub = LegalDemandSubdemand::query()
            ->with(['demand', 'events'])
            ->find($this->subdemandId);

        if (!$sub || !$sub->demand) {
            return;
        }

        $fingerprint = hash('sha256', implode('|', [
            (string) $sub->id,
            (string) ($sub->updated_at?->timestamp ?? 0),
            (string) ($sub->status instanceof \BackedEnum ? $sub->status->value : $sub->status),
            (string) ($sub->deadline_at?->timestamp ?? 0),
        ]));

        $already = $sub->events()
            ->where('event_type', 'async_processed')
            ->whereJsonContains('payload->fingerprint', $fingerprint)
            ->exists();
        if ($already) {
            return;
        }

        $metrics->refreshForDemand($sub->demand);

        $sub->events()->create([
            'event_type' => 'async_processed',
            'from_status' => $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status,
            'to_status' => $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status,
            'actor_user_id' => null,
            'actor_role' => 'system',
            'reason' => null,
            'description' => 'Processamento assíncrono da subdemanda executado.',
            'payload' => [
                'trigger' => $this->trigger ?: 'unknown',
                'fingerprint' => $fingerprint,
            ],
            'occurred_at' => now(),
        ]);
    }
}

