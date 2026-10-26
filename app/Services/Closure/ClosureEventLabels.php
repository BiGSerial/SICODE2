<?php

namespace App\Services\Closure;

/** Rótulos em português (e ícone) dos eventos da linha do tempo de uma Ordem; o código do evento nunca vai cru para a UI. */
class ClosureEventLabels
{
    private const MAP = [
        'ITEM_ADDED_TO_PRODUCTION' => ['Incluída na produção', 'ri-add-box-line'],
        'DISPATCHED'               => ['Despachada', 'ri-send-plane-line'],
        'ASSIGNED'                 => ['Atribuída', 'ri-user-add-line'],
        'CLAIMED'                  => ['Assumida', 'ri-hand-coin-line'],
        'TRANSFERRED'              => ['Transferida', 'ri-exchange-line'],
        'RETURNED_TO_POOL'         => ['Devolvida à pilha', 'ri-arrow-go-back-line'],
        'STARTED'                  => ['Iniciada', 'ri-play-circle-line'],
        'WAITING_RETURN'           => ['Aguardando retorno', 'ri-time-line'],
        'PRIORITY_CHANGED'         => ['Prioridade alterada', 'ri-flag-2-line'],
        'PARTNER_DEPOSIT_BLOCKED'  => ['Depósito bloqueado', 'ri-lock-line'],
        'PARTNER_DEPOSIT_RELEASED' => ['Depósito liberado', 'ri-lock-unlock-line'],
        'ISSUE_BLOCKED'            => ['Enviada à parceira', 'ri-send-plane-2-line'],
        'ISSUE_RELEASED'           => ['Retorno da parceira validado', 'ri-checkbox-circle-line'],
        'STATUS_CHANGED'           => ['Status alterado', 'ri-refresh-line'],
        'FINISHED'                 => ['Finalizada', 'ri-checkbox-circle-line'],
        'WAITING_FUTURE'           => ['Aguardando a meta', 'ri-calendar-event-line'],
        'WAITING_PROMOTED'         => ['Liberada para a meta', 'ri-calendar-check-line'],
        'SAP_CLOSED'               => ['Encerrada no SAP', 'ri-lock-line'],
        'SAP_CANCELLED'            => ['Cancelada no SAP', 'ri-close-circle-line'],
        'RI_SENT'                  => ['RI enviado ao Desenho', 'ri-lock-line'],
        'RI_RETURNED'              => ['RI retornado pelo Desenho', 'ri-lock-unlock-line'],
        'RI_CANCELLED'             => ['RI cancelado pela gestão', 'ri-close-circle-line'],
    ];

    /** Eventos de PENDÊNCIA (closure_issue_events): [rótulo, ícone, tom]. */
    private const ISSUE_MAP = [
        'OPENED'                  => ['Aberta', 'ri-add-circle-line', 'muted'],
        'REQUESTED'               => ['Enviada à parceira', 'ri-send-plane-2-line', 'blue'],
        'RESPONDED'               => ['Parceira respondeu', 'ri-reply-line', 'green'],
        'VALIDATING'              => ['Em validação', 'ri-search-eye-line', 'amber'],
        'RETURNED_FOR_CORRECTION' => ['Devolvida para correção', 'ri-arrow-go-back-line', 'red'],
        'RESOLVED'                => ['Resolvida', 'ri-checkbox-circle-line', 'green'],
        'RELEASED_BY_OPERATOR'    => ['Desbloqueada pelo Encerramento', 'ri-lock-unlock-line', 'muted'],
    ];

    /** Status da PENDÊNCIA: [rótulo, tom]. */
    private const ISSUE_STATUS_MAP = [
        'OPEN'       => ['Aberta', 'muted'],
        'REQUESTED'  => ['Aguardando resposta', 'amber'],
        'RESPONDED'  => ['Respondida · aguardando validação', 'blue'],
        'VALIDATING' => ['Em validação', 'blue'],
        'RETURNED'   => ['Devolvida para correção', 'red'],
        'RESOLVED'   => ['Resolvida', 'green'],
    ];

    public static function issueStatusLabel(?string $status): string
    {
        return self::ISSUE_STATUS_MAP[(string) $status][0] ?? (string) $status;
    }

    public static function issueStatusTone(?string $status): string
    {
        return self::ISSUE_STATUS_MAP[(string) $status][1] ?? 'muted';
    }

    public static function issueLabel(?string $type): string
    {
        return self::ISSUE_MAP[(string) $type][0] ?? ucfirst(mb_strtolower(str_replace('_', ' ', (string) $type)));
    }

    public static function issueIcon(?string $type): string
    {
        return self::ISSUE_MAP[(string) $type][1] ?? 'ri-record-circle-line';
    }

    public static function issueTone(?string $type): string
    {
        return self::ISSUE_MAP[(string) $type][2] ?? 'muted';
    }

    public static function label(?string $type): string
    {
        $type = (string) $type;

        return self::MAP[$type][0] ?? ucfirst(mb_strtolower(str_replace('_', ' ', $type)));
    }

    public static function icon(?string $type): string
    {
        return self::MAP[(string) $type][1] ?? 'ri-record-circle-line';
    }
}
