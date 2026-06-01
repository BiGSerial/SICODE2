<?php

namespace App\Observers;

use App\Models\ProtestJob;
use App\Services\Legal\LegalDemandActivityLinkService;

class ProtestJobObserver
{
    public function created(ProtestJob $job): void
    {
        app(LegalDemandActivityLinkService::class)->syncForProtestJobCreated($job);
    }

    public function deleted(ProtestJob $job): void
    {
        app(LegalDemandActivityLinkService::class)->unlinkForProtestJobRemoved($job, 'activity_deleted');
    }
}

