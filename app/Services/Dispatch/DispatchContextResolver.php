<?php

namespace App\Services\Dispatch;

use App\Models\Note;
use App\Models\Service;
use App\Support\SicodeRules;

class DispatchContextResolver
{
    public function for(Note $note, Service $service): array
    {
        $serviceKey = $this->serviceKey($service);
        $context = [
            'service_key' => $serviceKey,
            'requires_dd' => $this->requiresDd($serviceKey),
            'is_partial' => false,
            'is_d5_fiscalization' => false,
            'can_dispatch' => true,
            'block_reason' => null,
        ];

        if ($serviceKey === 'supervision') {
            $eval = app(\App\Services\Supervision\BlockEvaluator::class)->evaluate($note, $service);

            $context['is_partial'] = (bool) ($eval['isPartial'] ?? false);
            $context['can_dispatch'] = (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
            $context['is_d5_fiscalization'] = (bool) (
                $note->FiveNote
                && $note->FiveNote->is_completed
                && !$note->FiveNote->is_supervisioned
            );
        } elseif ($serviceKey === 'survey') {
            $eval = app(\App\Services\Design\BlockEvaluator::class)->evaluate($note, $service);

            $context['can_dispatch'] = !$eval['block'] || (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
        } elseif ($serviceKey === 'desenho') {
            $eval = app(\App\Services\Design\BlockEvaluator::class)->evaluate($note, $service);

            $context['can_dispatch'] = !$eval['block'] || (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
        } elseif (in_array($serviceKey, ['analises', 'analises-pre', 'analises_pre', 'reverse'], true)) {
            $eval = app(\App\Services\Design\BlockEvaluator::class)->evaluate($note, $service);

            $context['can_dispatch'] = !$eval['block'] || (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
        } elseif ($serviceKey === 'payment') {
            $eval = app(\App\Services\Payment\BlockEvaluator::class)->evaluate($note, $service);

            $context['is_partial'] = (bool) ($eval['isPartial'] ?? false);
            $context['can_dispatch'] = !$eval['block'] || (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
            $context['is_d5_fiscalization'] = (bool) $note->FiveNote;
        }

        return $context;
    }

    public function serviceKey(Service $service): string
    {
        return match ($service->folder) {
            'levantamento' => 'survey',
            'fiscalizacao' => 'supervision',
            'pagamento' => 'payment',
            default => (string) $service->folder,
        };
    }

    private function requiresDd(string $serviceKey): bool
    {
        return match ($serviceKey) {
            'survey' => SicodeRules::requiresDdForSurveyDispatch(),
            'supervision' => SicodeRules::requiresDdForSupervisionDispatch(),
            default => false,
        };
    }
}
