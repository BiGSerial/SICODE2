<?php

namespace App\Jobs;

use App\Services\Ads\AdsDeadlinePolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class SyncAdsDueAtJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly ?string $state = null)
    {
    }

    public function handle(AdsDeadlinePolicy $policy): void
    {
        $policy->syncPending($this->state);
    }
}
