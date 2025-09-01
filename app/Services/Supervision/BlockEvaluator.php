<?php

namespace App\Services\Supervision;

use App\Models\Note;
use App\Models\Service;
use App\Models\Production;

class BlockEvaluator
{
    // Mantém a compatibilidade com tua view (0 livre; 1=primary; 2=warning; 3=success; 4=danger)
    public const FREE        = 0; // pode despachar
    public const HOLD_BLUE   = 1; // aberto/em andamento (production completed=false)
    public const HOLD_YELLOW = 2; // travar por guarda (status==1) ou regra de bloqueio
    public const HOLD_GREEN  = 3; // completed
    public const HOLD_RED    = 4; // confirmado (teu "danger") ou “não refletiu no SAP”

    /**
     * Retorna um array pronto pra usar na view:
     * ['block'=>int, 'command'=>bool, 'color'=>'table-...', 'reason'=>'...','production'=>$prod|null]
     */
    public function evaluate(Note $note, Service $service): array
    {
        // Garanta que as relações já vieram eager loaded (como vc já faz em getListsProperty)
        $prod = $this->latestProductionForService($note, $service->uuid);

        // Atalhos
        $five   = $note->FiveNote;                 // prioridade quando completed=true e is_supervisioned=false
        $wf     = $note->WorkForm;                 // WorkReport
        $partial = $note->Partials?->last();        // você usa sempre o último nas telas
        $validPartial = $note->Partials
            ?->where('allow', true)->where('supervision', false)->where('deny', false)->sortBy('created_at')->last();

        // ===== 0) Sem produção ainda -> livre
        if (!$prod) {
            // Se existir WorkForm rejeitado, nem deveria listar; mas se vier, consideramos bloqueio amarelo
            if ($wf && ($wf->reject ?? false)) {
                return $this->res(self::HOLD_YELLOW, false, 'workform_rejected');
            }
            // Partial válido mais recente? Livre.
            if ($validPartial) {
                return $this->res(self::FREE, true, 'partial_valid_without_production');
            }
            // FiveNote completed & !is_supervisioned? Livre (não há produção aberta)
            if ($five && ($five->completed ?? false) && !($five->is_supervisioned ?? false)) {
                return $this->res(self::FREE, true, 'fivenote_priority_no_production');
            }
            return $this->res(self::FREE, true, 'no_production');
        }

        // ===== 1) PRIORIDADE: FiveNote (se completed=true e is_supervisioned=false)
        if ($five && ($five->is_completed) && !($five->is_supervisioned)) {
            if (!$prod->completed && $prod->dfive) {
                // produção aberta: bloqueio azul
                return $this->res(self::HOLD_BLUE, false, 'fivenote_priority_prod_open', $prod);
            }

            if ($prod->completed && $prod->dfive && ($five->completed_at < $prod->created_at)) {
                // produção aberta: bloqueio azul
                return $this->res(self::HOLD_RED, false, 'fivenote_priority_prod_open', $prod);
            }
            // produção fechada: permitido (considerando prioridade FiveNote)
            return $this->res(self::FREE, true, 'fivenote_priority_prod_closed', $prod);
        }

        // ===== 2) WORKFORM tem precedência sobre partial (exceto se reject => nem lista)
        if ($wf) {
            if ($wf->reject ?? false) {
                // pela tua regra: nem aparece; mas se aparecer, bloqueia
                return $this->res(self::HOLD_YELLOW, false, 'workform_rejected', $prod);
            }

            // Se a produção for posterior ao informed_at (ou created_at se não houver informed_at) => bloqueia
            $wfMark = $wf->informed_at ?? $wf->created_at;
            if ($wfMark && !$prod->partial) {
                if ($prod->created_at > $wfMark) {
                    // se produção ainda aberta => azul; se fechada => vermelho (não refletiu no SAP)
                    return $this->res($prod->completed ? self::HOLD_RED : self::HOLD_BLUE, false, 'workform_after_prod', $prod);
                }
            } else {
                // não tem informed_at; compara com created_at
                if ($prod->created_at > $wf->created_at) {
                    return $this->res($prod->completed ? self::HOLD_RED : self::HOLD_BLUE, false, 'workform_after_prod', $prod);
                }
            }

            if ($prod->partial && $prod->completed) {
                // Se a produção for parcial e estiver completa, libera
                return $this->res(self::FREE, true, 'workform_partial_completed', $prod);
            }
        }
        // ===== 3) PARTIAL (só se não houver WorkForm válido acima)
        elseif ($validPartial) {
            // Produção aberta sempre bloqueia azul
            if (!$prod->completed) {
                return $this->res(self::HOLD_BLUE, false, 'partial_prod_open', $prod);
            }

            // Se o partial válido for mais recente do que completed_at OU (se não houver) created_at da produção => LIBERA
            $prodMark = $prod->completed_at ?? $prod->created_at;
            if ($validPartial->created_at > $prodMark) {
                return $this->res(self::FREE, true, 'partial_newer_than_prod', $prod);
            }

            // Partial mais antigo que a produção fechada => vermelho (não refletiu no SAP)
            return $this->res(self::HOLD_RED, false, 'partial_older_than_prod', $prod);
        }

        // ===== 4) FALLBACK: comparação SAP (dt_note==dt_status && status_note==nstats => ainda não executou no SAP)
        if (
            $prod->dt_note && $note->dt_status &&
            $prod->dt_note->equalTo($note->dt_status) &&
            $prod->status_note == $note->nstats
        ) {
            // “Pode liberar o comando (mas visual vermelho) para cobrar SAP”
            // Mantendo tua semântica: bloqueio vermelho porém com command liberado
            return $this->res(self::HOLD_RED, true, 'sap_not_reflected_same_dt_and_status', $prod);
        }

        // ===== 5) Estados herdados do teu uso atual
        if ($prod->confirmed) {
            // perigo/vermelho e mantém command liberado como no teu código
            return $this->res(self::HOLD_RED, true, 'prod_confirmed', $prod);
        }
        if ($prod->completed) {
            return $this->res(self::HOLD_GREEN, false, 'prod_completed', $prod);
        }
        if ((int)$prod->status === 1) {
            return $this->res(self::HOLD_YELLOW, false, 'prod_status_guard', $prod);
        }

        // produção “aberta” genérica
        return $this->res(self::HOLD_BLUE, false, 'prod_open_generic', $prod);
    }

    private function latestProductionForService(Note $note, string $serviceUuid): ?Production
    {
        // usa relação já carregada para evitar N+1; se não carregada, consulta.
        if ($note->relationLoaded('Productions')) {
            return $note->Productions->where('service_id', $serviceUuid)->sortBy('created_at')->last();
        }
        return Production::where('note_id', $note->id)->where('service_id', $serviceUuid)->orderBy('created_at')->first();
    }

    private function res(int $block, bool $command, string $reason, ?Production $prod = null): array
    {
        return [
            'block'   => $block,
            'command' => $command,
            'color'   => $this->colorFor($block),
            'reason'  => $reason,
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
