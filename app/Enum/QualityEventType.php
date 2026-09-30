<?php

namespace App\Enum;

enum QualityEventType: string
{
    case DISPATCHED           = 'DISPATCHED';            // N2 → N1
    case DESIGNER_ASSIGNED    = 'DESIGNER_ASSIGNED';     // N1 → usuário
    case STAGE_STARTED        = 'STAGE_STARTED';         // usuário iniciou
    case STAGE_SUBMITTED      = 'STAGE_SUBMITTED';       // usuário → N1
    case APPROVED             = 'APPROVED';              // N1 ou N2 aprovou
    case FORWARDED_TO_N2      = 'FORWARDED_TO_N2';       // N1 → N2
    case REJECTED             = 'REJECTED';              // N1 rejeitou / N2 devolveu
    case RETURNED_TO_DESIGNER = 'RETURNED_TO_DESIGNER';  // N1 → usuário após rejeição
    case RETURNED_TO_N1       = 'RETURNED_TO_N1';        // N2 → N1
    case RETURN_FORWARDED     = 'RETURN_FORWARDED';      // N1 encaminhou a devolução do N2
    case BUDGET_RELEASED      = 'BUDGET_RELEASED';       // 2º ciclo iniciado (nova atividade aberta)
    case REASSIGNED           = 'REASSIGNED';            // N1 trocou o usuário da atividade
    case CONTESTED            = 'CONTESTED';             // N1 questionou a devolução do N2
    case COMMENT_ADDED        = 'COMMENT_ADDED';
    case SAP_REQUESTED        = 'SAP_REQUESTED';
    case SAP_SUCCEEDED        = 'SAP_SUCCEEDED';
    case SAP_FAILED           = 'SAP_FAILED';
    case PRODUCTION_CLOSED    = 'PRODUCTION_CLOSED';
    case COMPLETED            = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::DISPATCHED           => 'Despachado do N2 para o N1',
            self::DESIGNER_ASSIGNED    => 'Despachado do N1 ao usuário',
            self::STAGE_STARTED        => 'Usuário iniciou a atividade',
            self::STAGE_SUBMITTED      => 'Usuário enviou ao N1',
            self::APPROVED             => 'Aprovado',
            self::FORWARDED_TO_N2      => 'Encaminhado do N1 ao N2',
            self::REJECTED             => 'Rejeitado',
            self::RETURNED_TO_DESIGNER => 'Devolvido ao usuário',
            self::RETURNED_TO_N1       => 'Devolvido do N2 ao N1',
            self::RETURN_FORWARDED     => 'N1 encaminhou a devolução ao usuário',
            self::BUDGET_RELEASED      => '2º ciclo iniciado',
            self::REASSIGNED           => 'Atividade reatribuída a outro usuário',
            self::CONTESTED            => 'N1 questionou a devolução e devolveu ao N2',
            self::COMMENT_ADDED        => 'Comentário',
            self::SAP_REQUESTED        => 'Alteração de status SAP solicitada',
            self::SAP_SUCCEEDED        => 'Status SAP alterado',
            self::SAP_FAILED           => 'Falha ao alterar status no SAP',
            self::PRODUCTION_CLOSED    => 'Atividade do usuário encerrada',
            self::COMPLETED            => 'Processo de Qualidade concluído',
        };
    }

    /** Ícone (remixicon) do evento na linha do tempo. */
    public function icon(): string
    {
        return match ($this) {
            self::DISPATCHED => 'ri-send-plane-2-line',
            self::DESIGNER_ASSIGNED, self::RETURN_FORWARDED => 'ri-user-shared-line',
            self::REASSIGNED      => 'ri-exchange-line',
            self::STAGE_STARTED   => 'ri-play-circle-line',
            self::STAGE_SUBMITTED => 'ri-upload-cloud-2-line',
            self::APPROVED        => 'ri-checkbox-circle-line',
            self::FORWARDED_TO_N2 => 'ri-share-forward-line',
            self::REJECTED        => 'ri-close-circle-line',
            self::RETURNED_TO_DESIGNER, self::RETURNED_TO_N1 => 'ri-arrow-go-back-line',
            self::BUDGET_RELEASED => 'ri-rocket-line',
            self::COMMENT_ADDED   => 'ri-chat-3-line',
            self::CONTESTED       => 'ri-question-answer-line',
            self::SAP_REQUESTED, self::SAP_SUCCEEDED => 'ri-database-2-line',
            self::SAP_FAILED        => 'ri-error-warning-line',
            self::PRODUCTION_CLOSED => 'ri-lock-line',
            self::COMPLETED         => 'ri-flag-2-line',
        };
    }

    /** Cor semântica: success, danger, warning, primary, info, secondary. */
    public function tone(): string
    {
        return match ($this) {
            self::APPROVED, self::COMPLETED, self::SAP_SUCCEEDED, self::BUDGET_RELEASED => 'success',
            self::REJECTED, self::RETURNED_TO_DESIGNER, self::RETURNED_TO_N1, self::SAP_FAILED => 'danger',
            self::CONTESTED, self::REASSIGNED => 'warning',
            self::STAGE_SUBMITTED, self::FORWARDED_TO_N2 => 'primary',
            self::DISPATCHED, self::DESIGNER_ASSIGNED, self::RETURN_FORWARDED, self::STAGE_STARTED => 'info',
            default => 'secondary',
        };
    }

    /** decisoes | fluxo — base dos filtros da linha do tempo. */
    public function group(): string
    {
        return match ($this) {
            self::APPROVED, self::REJECTED, self::CONTESTED, self::COMPLETED, self::SAP_REQUESTED, self::SAP_SUCCEEDED, self::SAP_FAILED, self::PRODUCTION_CLOSED, self::RETURNED_TO_N1 => 'decisoes',
            default => 'fluxo',
        };
    }
}
