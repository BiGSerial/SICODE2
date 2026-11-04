<?php

namespace App\Services\Closure;

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\ClosurePartnerSignal;
use App\Support\Ops\RowState;

/**
 * Transforma a linha do Despacho de Encerramento (uma Ordem) no que a view desenha: situação da linha (RowState), selo de
 * status e identificação do depósito bloqueado. A view não decide cor nem texto (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md).
 */
final class ClosureDispatchRowPresenter
{
    /** Sinal do Encerramento (ClosureSignal) => situação visual das listas operacionais. */
    private const ROW_STATE = [
        ClosureSignal::OVERDUE  => RowState::LATE,
        ClosureSignal::DONE     => RowState::DONE,
        ClosureSignal::ASSIGNED => RowState::ASSIGNED,
        ClosureSignal::PILE     => RowState::STACK,
    ];

    /**
     * @param \Illuminate\Support\Collection<int, ClosurePartnerSignal> $blockedByState bloqueios que travaram o estado (chave = id do estado)
     * @param \Illuminate\Support\Collection<string, ClosurePartnerSignal> $byCenter bloqueios ativos por centro de trabalho (chave = cenTrab)
     * @return array{state: string, signal: ?array, status: ?array{tone: string, label: string, hint: string}, deposit: ?array{tone: string, label: string, hint: string, locked: bool}, dispatched: bool, lockReason: ?string}
     */
    public static function present(object $order, $blockedByState, $byCenter): array
    {
        $signal     = ClosureSignal::forRow($order);
        $dispatched = $order->state_id !== null;
        $heldBy     = $order->assignee_name ?: ($order->holder_company_name ? 'a pilha de ' . $order->holder_company_name : 'outro responsável');

        return [
            'state'      => $signal ? (self::ROW_STATE[$signal['key']] ?? RowState::FREE) : RowState::FREE,
            'signal'     => $signal,
            'status'     => $dispatched ? ['tone' => ClosureOperationalStatus::toneFor($order->operational_status), 'label' => ClosureOperationalStatus::labelFor($order->operational_status), 'hint' => self::statusHint($order->operational_status)] : null,
            'deposit'    => self::deposit($order->state_id, $order->operational_status, $order->cen_trab ?? null, $blockedByState, $byCenter),
            'dispatched' => $dispatched,
            // Ordem que já tem posse nunca é despachada de novo: mudar de responsável é pela Transferência/Controle.
            'lockReason' => $dispatched ? 'Já despachada: está com ' . $heldBy . '. Para mudar o responsável use a Transferência.' : null,
        ];
    }

    /**
     * Identificação do depósito bloqueado numa Ordem: vermelha = travada pelo bloqueio; âmbar = no alcance (centro de
     * trabalho da operação 0010 coberto por bloqueio ativo), mas ainda não travada.
     *
     * @return ?array{tone: string, label: string, hint: string, locked: bool}
     */
    public static function deposit(int|string|null $stateId, ?string $status, ?string $cenTrab, $blockedByState, $byCenter): ?array
    {
        $locked = $stateId ? $blockedByState->get($stateId) : null;

        if ($locked) {
            return [
                'tone'   => 'red',
                'label'  => 'Depósito ' . $locked->deposit_code . ' bloqueado',
                'hint'   => self::describe($locked) . ' Esta Ordem foi travada por ele e volta ao status anterior quando o depósito for liberado.',
                'locked' => true,
            ];
        }

        $signal = $cenTrab ? $byCenter->get($cenTrab) : null;

        if (!$signal) {
            return null;
        }

        $why = $stateId
            ? 'Ela não foi travada porque está em "' . ClosureOperationalStatus::labelFor($status) . '"'
            : 'Ela não foi travada porque ainda não foi despachada';

        return [
            'tone'   => 'amber',
            'label'  => 'No alcance do depósito ' . $signal->deposit_code,
            'hint'   => 'O centro de trabalho ' . mb_strtoupper(trim($cenTrab)) . ' desta Ordem pertence ao depósito bloqueado. ' . self::describe($signal) . ' ' . $why . '; o bloqueio trava só Ordens em tratativa.',
            'locked' => false,
        ];
    }

    /** Texto curto, em português, do que o status operacional significa para quem olha a lista. */
    public static function statusHint(?string $status): string
    {
        return match ($status) {
            'AVAILABLE'        => 'Na pilha da empresa, sem responsável.',
            'ASSIGNED'         => 'Atribuída a um responsável; ainda não foi iniciada.',
            'IN_PROGRESS'      => 'O responsável está trabalhando nela.',
            'PAUSED'           => 'Em pausa, com motivo registrado.',
            'WAITING_PARTNER'  => 'Aguardando a parceira responder a pendência.',
            'DEPOSIT_BLOCKED'  => 'Travada porque o depósito do centro de trabalho está bloqueado.',
            'DEPOSIT_RELEASED' => 'Depósito liberado; pode ser retomada.',
            'FINISHED'         => 'Finalizada pelo responsável; aguardando a baixa no SAP.',
            'WAITING'          => 'Meta futura: aguarda o mês da meta para ser finalizada.',
            'CLOSED'           => 'Encerrada.',
            'CANCELLED'        => 'Cancelada.',
            default            => 'Ainda sem responsável: pode ser despachada.',
        };
    }

    private static function describe(ClosurePartnerSignal $signal): string
    {
        return 'Depósito ' . $signal->deposit_code . ($signal->deposit_label ? ' · ' . $signal->deposit_label : '') . ' bloqueado desde ' . $signal->created_at?->format('d/m/Y')
            . ($signal->origin === ClosurePartnerSignal::ORIGIN_CALENDAR ? ' (inventário)' : '') . '.';
    }
}
