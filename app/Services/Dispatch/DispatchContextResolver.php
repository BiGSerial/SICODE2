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
            $fiveNote = $note->WorkForm?->FiveNote ?? $note->FiveNote;

            $context['is_partial'] = (bool) ($eval['isPartial'] ?? false);
            $context['can_dispatch'] = (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
            $context['is_d5_fiscalization'] = (bool) (
                $fiveNote
                && $fiveNote->is_completed
                && !$fiveNote->is_supervisioned
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
            // D5 legada e D5 de informe coexistem: linha de informe só olha a D5 do próprio informe;
            // linha da nota só olha a legada. Só conta se ainda exige ação da Medição.
            $fiveNote = (int) ($note->dispatch_work_report_id ?? 0) > 0
                ? $note->WorkForm?->FiveNote
                : $note->FiveNote;
            $fiveNote = $fiveNote?->needsPaymentAction() ? $fiveNote : null;

            $context['is_partial'] = (bool) ($eval['isPartial'] ?? false);
            $context['can_dispatch'] = !$eval['block'] || (bool) ($eval['command'] ?? false);
            $context['block_reason'] = $eval['reason'] ?? null;
            $context['is_d5_fiscalization'] = (bool) $fiveNote;
        }

        return $context;
    }

    public function serviceKey(Service $service): string
    {
        return match ($service->folder) {
            'levantamento' => 'survey',
            'fiscalizacao' => 'supervision',
            'pagamento' => 'payment',
            'publicacao' => 'publication',
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
