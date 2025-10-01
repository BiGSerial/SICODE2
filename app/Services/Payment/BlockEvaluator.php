<?php

namespace App\Services\Payment;

use App\Models\Note;
use App\Models\Service;
use App\Models\Production;

class BlockEvaluator
{
    public const FREE        = 0; // pode despachar / atribuir
    public const HOLD_BLUE   = 1; // aberto / em andamento
    public const HOLD_YELLOW = 2; // “guarda” (status==1) ou pendência leve
    public const HOLD_GREEN  = 3; // completed
    public const HOLD_RED    = 4; // confirmado e “não refletiu no SAP”

    public function evaluate(Note $note, Service $service): array
    {
        $prod = $this->latestProductionForService($note, $service->uuid);

        $five   = $note->FiveNote;
        $wf     = $note->WorkForm;
        $validPartial = $note->Partials
            ?->where('allow', true)
            ->where('supervision', true)
            ->where('deny', false)
            ->sortByDesc('created_at')
            ->first();

        // ===== 0) Sem produção
        if (!$prod) {
            // WF rejeitado sempre vermelho (alinhe o nome do campo: rejected vs reject)
            if ($wf && ($wf->rejected ?? false)) {
                return $this->res(self::HOLD_RED, false, 'workform_rejected');
            }

            // FiveNote tem prioridade se: is_completed && is_supervisioned && !is_archived
            if ($five && ($five->is_completed ?? false) && ($five->is_supervisioned ?? false) && !($five->is_archived ?? false)) {
                return $this->res(self::FREE, true, 'fivenote_priority_no_production');
            }

            if ($validPartial) {
                return $this->res(self::FREE, true, 'partial_valid_without_production');
            }

            return $this->res(self::FREE, true, 'no_production');
        }



        // ===== 1) PRIORIDADE: FiveNote (is_completed && is_supervisioned && !is_archived)
        if ($five && ($five->is_completed ?? false) && ($five->is_supervisioned ?? false) && !($five->is_archived ?? false)) {

            if (!$prod->completed) {
                return $this->res(self::HOLD_BLUE, false, 'fivenote_priority_prod_open', $prod);
            }

            // produção fechada posterior ao FiveNote com dfive -> vermelho (não refletiu no SAP)
            if (
                ($prod->dfive ?? false) &&
                ($five->completed_at ?? null) &&
                ($prod->created_at ?? null) &&
                $five->completed_at < $prod->created_at
            ) {
                if ($prod->completed && $prod->confirmed) {
                    return $this->res(self::HOLD_RED, false, 'fivenote_priority_prod_closed_not_reflected', $prod);
                } else {
                    return $this->res(self::HOLD_GREEN, false, 'fivenote_priority_prod_closed_not_reflected_completed', $prod);
                }
            }

            return $this->res(self::FREE, true, 'fivenote_priority_prod_closed', $prod);
        }

        // ===== 2) WORKFORM prevalece sobre Partial (exceto rejeição)
        if ($wf) {
            if ($wf->rejected ?? false) {
                return $this->res(self::HOLD_RED, false, 'workform_rejected', $prod);
            }

            $wfMark = $wf->informed_at ?? $wf->created_at;
            if ($wfMark && ($prod->created_at ?? null) && $prod->created_at > $wfMark) {
                if ($prod->completed && $prod->confirmed) {
                    return $this->res($prod->completed ? self::HOLD_RED : self::HOLD_BLUE, false, 'workform_after_prod_confirmed', $prod);
                } elseif ($prod->completed && !$prod->confirmed) {
                    return $this->res(self::HOLD_GREEN, false, 'workform_after_prod_not_confirmed', $prod);
                }
            }

            if ($prod->completed && $prod->partial) {
                return $this->res(self::FREE, true, 'workform_partial_completed', $prod);
            }
        }
        // ===== 3) PARTIAL (só se não caiu em WF)
        elseif ($validPartial) {
            if (!$prod->completed) {
                return $this->res(self::HOLD_BLUE, false, 'partial_prod_open', $prod);
            }

            $prodMark = $prod->completed_at ?? $prod->created_at;
            if ($prodMark && $validPartial->supervision_at > $prodMark) {
                return $this->res(self::FREE, true, 'partial_newer_than_prod', $prod);
            }

            if ($prod->completed && $prod->confirmed) {
                return $this->res(self::HOLD_RED, false, 'partial_prod_closed_confirmed', $prod);
            } elseif ($prod->completed && !$prod->confirmed) {
                return $this->res(self::HOLD_GREEN, false, 'partial_prod_closed_not_confirmed', $prod);
            }
        }

        // ===== 4) FALLBACK SAP (dt e status iguais => não refletiu)
        if (
            ($prod->dt_note ?? null) && ($note->dt_status ?? null) &&
            $prod->dt_note->equalTo($note->dt_status) &&
            ($prod->status_note ?? null) !== null &&
            ($note->nstats ?? null) !== null &&
            $prod->status_note == $note->nstats
        ) {
            return $this->res(self::HOLD_RED, true, 'sap_not_reflected_same_dt_and_status', $prod);
        }

        // ===== Produção sem atribuição
        if (empty($prod->user_id)) {
            return $this->res(self::HOLD_YELLOW, false, 'production_without_assignment', $prod);
        }

        // ===== 5) Estados herdados
        if ($prod->confirmed ?? false) {
            return $this->res(self::HOLD_RED, true, 'prod_confirmed', $prod);
        }
        if ($prod->completed ?? false) {
            return $this->res(self::HOLD_GREEN, false, 'prod_completed', $prod);
        }
        if ((int) ($prod->status ?? 0) === 1) {
            return $this->res(self::HOLD_YELLOW, false, 'prod_status_guard', $prod);
        }

        return $this->res(self::HOLD_BLUE, false, 'prod_open_generic', $prod);
    }

    private function latestProductionForService(Note $note, string $serviceUuid): ?Production
    {
        if ($note->relationLoaded('Productions')) {
            return $note->Productions
                ->where('service_id', $serviceUuid)
                ->sortByDesc('created_at')
                ->first();
        }

        return Production::where('note_id', $note->id)
            ->where('service_id', $serviceUuid)
            ->orderByDesc('created_at')
            ->first();
    }

    private function res(int $block, bool $command, string $reason, ?Production $prod = null): array
    {
        return [
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'production' => $prod,
        ];
    }

    private function colorFor(int $block): string
    {
        return match ($block) {
            self::FREE        => '',
            self::HOLD_BLUE   => 'table-primary',
            self::HOLD_YELLOW => 'table-warning',
            self::HOLD_GREEN  => 'table-success',
            self::HOLD_RED    => 'table-danger',
            default           => '',
        };
    }
}
