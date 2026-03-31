<?php

namespace App\Services\Supervision;

use App\Models\Note;
use App\Models\Service;
use App\Models\Production;

class BlockEvaluator
{
    // Compatibilidade com a view: 0=livre; 1=primary; 2=warning; 3=success; 4=danger
    public const FREE        = 0; // Livre / Despachável
    public const HOLD_BLUE   = 1; // Aberto / Em andamento
    public const HOLD_YELLOW = 2; // Travado (Guarda ou Rejeitado)
    public const HOLD_GREEN  = 3; // Concluído / Sucesso
    public const HOLD_RED    = 4; // Erro de sincronia SAP / Confirmado

    /**
     * Avalia o status de bloqueio para a Note e o Serviço fornecidos.
     */
    public function evaluate(Note $note, Service $service): array
    {
        $prod = $this->latestProductionForService($note, $service->uuid);

        // Atalhos
        $five    = $note->FiveNote;
        $wf      = $note->WorkForm;
        $hasWorkForm = (bool) $wf;
        $isPartialFromProd = !$hasWorkForm && (bool) ($prod?->partial);
        $validPartial = $this->latestValidPartialForSupervision($note);

        // ===== 0) SEM PRODUÇÃO NO BANCO =====
        if (!$prod) {
            if ($wf && ($wf->rejected ?? false)) {
                return $this->res(self::HOLD_YELLOW, false, 'workform_rejeitado');
            }
            if ($wf) {
                // WorkForm tem precedência sobre Partial quando não há produção.
                return $this->res(self::FREE, true, 'workform_sem_producao', null, false);
            }
            if ($validPartial) {
                return $this->res(self::FREE, true, 'partial_valido_sem_producao', null, true);
            }
            if ($five && ($five->is_completed) && !($five->is_supervisioned)) {
                return $this->res(self::FREE, true, 'fivenote_prioritario_sem_producao');
            }
            return $this->res(self::FREE, true, 'sem_producao');
        }

        // ===== 1) PRIORIDADE MESTRE: FiveNote =====
        if ($five && ($five->is_completed) && !($five->is_supervisioned)) {
            if (!$prod->completed && $prod->dfive) {
                return $this->res(self::HOLD_BLUE, false, 'fivenote_prioritario_producao_aberta_dfive', $prod);
            }
            if ($prod->completed && $prod->dfive && ($five->completed_at < $prod->created_at)) {
                return $this->res(self::HOLD_RED, false, 'fivenote_prioritario_antes_da_producao_dfive', $prod);
            }
            return $this->res(self::FREE, true, 'fivenote_prioritario_producao_fechada', $prod);
        }

        // ===== 2) VERIFICAÇÃO DE PRODUÇÃO EM ABERTO (FIX: Garante cor Azul) =====
        if (!$prod->completed) {
            // Se a produção tem status de guarda (status == 1), retorna Amarelo
            if ((int)$prod->status === 1) {
                return $this->res(self::HOLD_YELLOW, false, 'producao_status_guarda', $prod, $isPartialFromProd);
            }
            // Caso contrário, se está aberta (completed false), é sempre Azul
            return $this->res(self::HOLD_BLUE, false, 'producao_em_andamento', $prod, $isPartialFromProd);
        }

        // ===== 3) SE CHEGOU AQUI, A PRODUÇÃO ESTÁ CONCLUÍDA ($prod->completed = true) =====

        // --- Regras para WorkForm ---
        if ($wf) {
            if ($wf->rejected ?? false) {
                return $this->res(self::HOLD_YELLOW, false, 'workform_rejeitado_producao_concluida', $prod, false);
            }

            $wfMark = $wf->informed_at ?? $wf->created_at;
            $prodMark = $prod->completed_at ?? $prod->created_at;

            // Se o informe foi reenviado após a última produção finalizada,
            // libera nova fiscalização.
            if ($wfMark && $prodMark && $wfMark > $prodMark) {
                return $this->res(self::FREE, true, 'workform_reinformado_pos_producao_concluida', $prod, false);
            }

            if ($wfMark && !$prod->partial) {
                // Caso específico de OV (tipo 2)
                if ($prod->dt_note < $note->dt_status && $note->type_note == 2) {
                    return $this->res(self::HOLD_RED, true, 'workform_ov_concluida_dt_note_antes_dt_status', $prod);
                }

                // Se produção foi criada após o relatório do Workform, indica que não refletiu no SAP
                if ($prod->created_at > $wfMark) {
                    return $this->res(self::HOLD_RED, false, 'workform_producao_fechada_posterior_ao_workform', $prod, false);
                }
            }

            if ($prod->partial) {
                return $this->res(self::FREE, true, 'workform_parcial_concluida', $prod, false);
            }
        }

        // --- Regras para Partial (se não houver WorkForm) ---
        elseif ($validPartial) {
            $prodMark = $prod->completed_at ?? $prod->created_at;
            if ($validPartial->created_at > $prodMark) {
                return $this->res(self::FREE, true, 'partial_posterior_a_producao_fechada', $prod, true);
            }
            return $this->res(self::HOLD_RED, false, 'partial_anterior_a_producao_fechada', $prod, true);
        }

        // ===== 4) FALLBACKS PARA PRODUÇÃO CONCLUÍDA =====

        // Check de sincronia SAP (mesma data e status indica que o SAP ainda não atualizou)
        if (
            $prod->dt_note && $note->dt_status &&
            $prod->dt_note->equalTo($note->dt_status) &&
            $prod->status_note == $note->nstats
        ) {
            return $this->res(
                self::HOLD_RED,
                true,
                'sap_nao_refletido_mesma_data_status',
                $prod,
                $isPartialFromProd
            );
        }

        if ($prod->confirmed) {
            return $this->res(
                self::HOLD_RED,
                true,
                'producao_confirmada',
                $prod,
                $isPartialFromProd
            );
        }

        // Se concluída e passou por todas as travas: Verde
        return $this->res(
            self::HOLD_GREEN,
            false,
            'producao_concluida',
            $prod,
            $isPartialFromProd
        );
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

    private function latestValidPartialForSupervision(Note $note): ?\App\Models\Partial
    {
        if ($note->relationLoaded('Partials')) {
            return $note->Partials
                ->where('allow', true)
                ->where('supervision', false)
                ->where('deny', false)
                ->sortByDesc('created_at')
                ->first();
        }

        return $note->Partials()
            ->where('allow', true)
            ->where('supervision', false)
            ->where('deny', false)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    private function res(int $block, bool $command, string $reason, ?Production $prod = null, bool $isPartial = false): array
    {
        return [
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'production' => $prod,
            'isPartial'  => $isPartial,
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
