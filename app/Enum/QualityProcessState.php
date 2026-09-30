<?php

namespace App\Enum;

/**
 * Estado operacional do processo. É a fonte de verdade de "com quem está".
 * O par (phase, state) + round_number descreve exatamente a posição no fluxo.
 */
enum QualityProcessState: string
{
    case AWAITING_N1_DISPATCH = 'AWAITING_N1_DISPATCH';
    case AWAITING_DESIGNER    = 'AWAITING_DESIGNER';
    case AWAITING_N1_REVIEW   = 'AWAITING_N1_REVIEW';
    case AWAITING_N2_REVIEW   = 'AWAITING_N2_REVIEW';
    case N2_RETURNED          = 'N2_RETURNED';
    case CLOSING              = 'CLOSING';
    case SAP_FAILED           = 'SAP_FAILED';
    case COMPLETED            = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_N1_DISPATCH => 'Aguardando despacho do N1',
            self::AWAITING_DESIGNER    => 'Aguardando usuário',
            self::AWAITING_N1_REVIEW   => 'Aguardando análise do N1',
            self::AWAITING_N2_REVIEW   => 'Aguardando análise do N2',
            self::N2_RETURNED          => 'Devolvido pelo N2 ao N1',
            self::CLOSING              => 'Encerramento pendente',
            self::SAP_FAILED           => 'Encerramento pendente',
            self::COMPLETED            => 'Concluído',
        };
    }

    /** Rótulo para quem não vê o N2 (N1): o encerramento pendente aparece apenas como "Aguardando N2". */
    public function labelFor(bool $seesN2): string
    {
        return !$seesN2 && in_array($this, [self::CLOSING, self::SAP_FAILED], true) ? self::AWAITING_N2_REVIEW->label() : $this->label();
    }

    public function nextStepFor(QualityStageType $phase, bool $seesN2): string
    {
        return !$seesN2 && in_array($this, [self::CLOSING, self::SAP_FAILED], true) ? self::AWAITING_N2_REVIEW->nextStep($phase) : $this->nextStep($phase);
    }

    /** Quem detém o processo neste estado. */
    public function holder(): ?QualityStageLevel
    {
        return match ($this) {
            self::AWAITING_N1_DISPATCH, self::AWAITING_N1_REVIEW, self::N2_RETURNED => QualityStageLevel::N1,
            self::AWAITING_DESIGNER => QualityStageLevel::DRAWING,
            self::AWAITING_N2_REVIEW, self::CLOSING, self::SAP_FAILED => QualityStageLevel::N2,
            self::COMPLETED => null,
        };
    }

    /** Frase que diz o que acontece agora e qual é o próximo passo (para deixar as etapas claras). */
    public function nextStep(QualityStageType $phase): string
    {
        $ciclo = $phase->short();

        return match ($this) {
            self::AWAITING_N1_DISPATCH => "O N1 despacha a Nota ao usuário da atividade do {$ciclo}. A atividade é criada na pilha desse usuário.",
            self::AWAITING_DESIGNER    => "O usuário executa a atividade do {$ciclo} e finaliza pelo formulário da Qualidade. Ao finalizar, cai para a análise do N1.",
            self::AWAITING_N1_REVIEW   => 'O N1 analisa: aprova (segue ao N2) ou rejeita, devolvendo ao usuário da atividade com categoria e subcategoria.',
            self::AWAITING_N2_REVIEW   => $phase === QualityStageType::PROJECT
                ? 'O N2 analisa: aprova (encerra a atividade do 1º ciclo e abre AUTOMATICAMENTE a do 2º ciclo para o mesmo usuário) ou devolve ao N1 com categoria e subcategoria.'
                : 'O N2 analisa: aprova (encerra a atividade e conclui a Qualidade, sem volta) ou devolve ao N1 com categoria e subcategoria.',
            self::N2_RETURNED => 'O N2 devolveu ao N1. O N1 encaminha ao usuário (pode trocá-lo) ou questiona o N2.',
            self::CLOSING     => 'O encerramento ficou pendente. O N2 conclui o encerramento.',
            self::SAP_FAILED  => 'O encerramento ficou pendente. O N2 conclui o encerramento.',
            self::COMPLETED   => 'Processo concluído. Não há mais etapas.',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::AWAITING_N1_DISPATCH, self::N2_RETURNED => 'warning',
            self::AWAITING_DESIGNER  => 'info',
            self::AWAITING_N1_REVIEW => 'primary',
            self::AWAITING_N2_REVIEW => 'secondary',
            self::CLOSING            => 'dark',
            self::SAP_FAILED         => 'danger',
            self::COMPLETED          => 'success',
        };
    }
}
