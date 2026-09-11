<?php

namespace App\Services\WorkReports;

use App\Models\{Note, Partial};

class FinalWorkReportCreationGuard
{
    public function openPartialFor(Note $note): ?Partial
    {
        return $note->Partials()
            ->where('complete', false)
            ->where('allow', true)
            ->where('deny', false)
            ->orderByDesc('created_at')
            ->first();
    }

    public function canCreateFinalFor(Note $note): bool
    {
        return $this->openPartialFor($note) === null;
    }
}
