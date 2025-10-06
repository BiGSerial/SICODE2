<?php

namespace App\Services\Viability;

use App\Models\Viability;
use App\Models\ViabilityRehiringAudit;
use Illuminate\Support\Facades\Auth;

class LogRehiring
{
    public static function handle(Viability $before, Viability $after, array $context = []): void
    {
        $actorId = Auth::id(); // string UUID

        $daysCountBefore = method_exists($before, 'days') ? $before->days()->count() : 0;

        ViabilityRehiringAudit::create([
            'viability_id'       => $after->id,
            'acted_by_user_id'   => $actorId,

            'old_company_id'     => optional($before->Company)->id,
            'old_engineer_id'    => optional($before->Engineer)->id,

            'new_company_id'     => $context['new_company_id'] ?? optional($after->Company)->id,
            'new_engineer_id'    => $context['new_engineer_id'] ?? optional($after->Engineer)->id,

            'was_newsend'        => (bool)($context['was_newsend'] ?? false),
            'was_rehiring'       => (bool)($context['was_rehiring'] ?? false),

            'old_sended_at'      => $before->sended_at,
            'new_sended_at'      => $after->sended_at,

            'had_days_before'    => $daysCountBefore > 0,
            'days_count_before'  => $daysCountBefore,

            'meta'               => $context['meta'] ?? null,
        ]);
    }
}
