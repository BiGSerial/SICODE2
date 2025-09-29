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
        $five    = $note->FiveNote;                 // prioridade quando completed=true e is_supervisioned=false
        $wf      = $note->WorkForm;                 // WorkReport
        $partial = $note->Partials?->last();        // você usa sempre o último nas telas
        $validPartial = $note->Partials
            ?->where('allow', true)->where('supervision', false)->where('deny', false)->sortBy('created_at')->last();

        // ===== 0) Sem produção ainda -> livre
        if (!$prod) {
            // Se existir WorkForm rejeitado, nem deveria listar; mas se vier, consideramos bloqueio amarelo
            if ($wf && ($wf->reject ?? false)) {
                return $this->res(self::HOLD_YELLOW, false, 'workform_rejeitado');
            }
            // Partial válido mais recente? Livre.
            if ($validPartial) {
                return $this->res(self::FREE, true, 'partial_valido_sem_producao');
            }
            // FiveNote completed & !is_supervisioned? Livre (não há produção aberta)
            if ($five && ($five->completed ?? false) && !($five->is_supervisioned ?? false)) {
                return $this->res(self::FREE, true, 'fivenote_prioritario_sem_producao');
            }
            return $this->res(self::FREE, true, 'sem_producao');
        }

        // ===== 1) PRIORIDADE: FiveNote (se completed=true e is_supervisioned=false)
        if ($five && ($five->is_completed) && !($five->is_supervisioned)) {
            if (!$prod->completed && $prod->dfive) {
                // produção aberta: bloqueio azul
                return $this->res(self::HOLD_BLUE, false, 'fivenote_prioritario_producao_aberta_dfive', $prod);
            }

            if ($prod->completed && $prod->dfive && ($five->completed_at < $prod->created_at)) {
                // produção fechada após FiveNote prioritário
                return $this->res(self::HOLD_RED, false, 'fivenote_prioritario_antes_da_producao_dfive', $prod);
            }
            // produção fechada: permitido (considerando prioridade FiveNote)
            return $this->res(self::FREE, true, 'fivenote_prioritario_producao_fechada', $prod);
        }

        // ===== 2) WORKFORM tem precedência sobre partial (exceto se reject => nem lista)
        if ($wf) {
            if ($wf->reject ?? false) {
                // pela tua regra: nem aparece; mas se aparecer, bloqueia
                return $this->res(self::HOLD_YELLOW, false, 'workform_rejeitado', $prod);
            }

            // Se a produção for posterior ao informed_at (ou created_at se não houver informed_at) => bloqueia
            $wfMark = $wf->informed_at ?? $wf->created_at;
            if ($wfMark && !$prod->partial) {

                if ($prod->dt_note < $note->dt_status && $note->type_note == 2 && $prod->completed) {
                    // Se a data do estatus da nota for inferior a nova data de Status e for OV => libera
                    return $this->res(self::HOLD_RED, true, 'workform_ov_concluida_dt_note_antes_dt_status', $prod);
                }

                if ($prod->created_at > $wfMark) {
                    // se produção ainda aberta => azul; se fechada => vermelho (não refletiu no SAP)
                    return $this->res(
                        $prod->completed ? self::HOLD_RED : self::HOLD_BLUE,
                        false,
                        $prod->completed
                            ? 'workform_producao_fechada_posterior_ao_workform_nao_parcial'
                            : 'workform_producao_aberta_posterior_ao_workform_nao_parcial',
                        $prod
                    );
                }
            } else {

                if ($prod->dt_note > $note->dt_status) {
                    // Se a produção for posterior ao status da nota => bloqueia azul
                    return $this->res(self::HOLD_BLUE, false, 'workform_dt_note_posterior_dt_status', $prod);
                }

                // não tem informed_at; compara com created_at
                if ($prod->created_at > $wf->created_at) {
                    return $this->res(
                        $prod->completed ? self::HOLD_RED : self::HOLD_BLUE,
                        false,
                        $prod->completed
                            ? 'workform_producao_fechada_posterior_a_criacao_do_workform'
                            : 'workform_producao_aberta_posterior_a_criacao_do_workform',
                        $prod
                    );
                }
            }

            if ($prod->partial && $prod->completed) {
                // Se a produção for parcial e estiver completa, libera
                return $this->res(self::FREE, true, 'workform_parcial_concluida', $prod);
            }
        }
        // ===== 3) PARTIAL (só se não houver WorkForm válido acima)
        elseif ($validPartial) {
            // Produção aberta sempre bloqueia azul
            if (!$prod->completed) {
                return $this->res(self::HOLD_BLUE, false, 'partial_producao_aberta', $prod);
            }

            // Se o partial válido for mais recente do que completed_at OU (se não houver) created_at da produção => LIBERA
            $prodMark = $prod->completed_at ?? $prod->created_at;
            if ($validPartial->created_at > $prodMark) {
                return $this->res(self::FREE, true, 'partial_posterior_a_producao_fechada', $prod);
            }

            // Partial mais antigo que a produção fechada => vermelho (não refletiu no SAP)
            return $this->res(self::HOLD_RED, false, 'partial_anterior_a_producao_fechada', $prod);
        }

        // ===== 4) FALLBACK: comparação SAP (dt_note==dt_status && status_note==nstats => ainda não executou no SAP)
        if (
            $prod->dt_note && $note->dt_status &&
            $prod->dt_note->equalTo($note->dt_status) &&
            $prod->status_note == $note->nstats
        ) {
            // “Pode liberar o comando (mas visual vermelho) para cobrar SAP”
            // Mantendo tua semântica: bloqueio vermelho porém com command liberado
            return $this->res(self::HOLD_RED, true, 'sap_nao_refletido_mesma_data_status', $prod);
        }

        // ===== 5) Estados herdados do teu uso atual
        if ($prod->confirmed) {
            // perigo/vermelho e mantém command liberado como no teu código
            return $this->res(self::HOLD_RED, true, 'producao_confirmada', $prod);
        }
        if ($prod->completed) {
            return $this->res(self::HOLD_GREEN, false, 'producao_concluida', $prod);
        }
        if ((int)$prod->status === 1) {
            return $this->res(self::HOLD_YELLOW, false, 'producao_status_guarda', $prod);
        }

        // produção “aberta” genérica
        return $this->res(self::HOLD_BLUE, false, 'producao_aberta_sem_regra_especifica', $prod);
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
