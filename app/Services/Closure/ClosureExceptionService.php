<?php

namespace App\Services\Closure;

use App\Models\{ClosureCycle, ClosureTarget, Order};
use Illuminate\Validation\ValidationException;

/**
 * Caminho de exceção para a meta de encerramento: permite registrar uma Ordem como "caso atípico"
 * em uma competência mesmo que ela já esteja FROZEN (fora do fluxo automático de
 * ClosureTargetFreezer). Não é automático — exige justificativa e quem autorizou
 * (solicitação superior), confirmado pelo usuário em 2026-08-31.
 */
class ClosureExceptionService
{
    public function registerException(
        Order $order,
        ClosureCycle $cycle,
        string $reason,
        ?string $authorizedBy,
        ?string $requestedBy = null
    ): ClosureTarget {
        if ($order->canceled) {
            throw ValidationException::withMessages([
                'order' => "Ordem {$order->ordem} está cancelada — não pode entrar na meta, nem por exceção.",
            ]);
        }

        if ($order->ClosureTarget) {
            throw ValidationException::withMessages([
                'order' => "Ordem {$order->ordem} já possui registro de meta (competência {$order->ClosureTarget->Cycle?->label}).",
            ]);
        }

        $noteNumber = trim((string) ($order->Note?->note ?? ''));

        if ($noteNumber === '' || $noteNumber === '0') {
            throw ValidationException::withMessages([
                'order' => "Ordem {$order->ordem} não possui Nota agregadora válida — não pode entrar na meta.",
            ]);
        }

        $status = (string) $order->statusSist;

        if (str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE')) {
            throw ValidationException::withMessages([
                'order' => "Ordem {$order->ordem} já está encerrada no SAP (statusSist={$status}) — não faz sentido entrar na meta.",
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Justificativa é obrigatória para registrar uma exceção.',
            ]);
        }

        if (!$authorizedBy) {
            throw ValidationException::withMessages([
                'authorized_by' => 'É necessário informar quem autorizou (solicitação superior) esta exceção.',
            ]);
        }

        return ClosureTarget::create([
            'closure_cycle_id' => $cycle->id,
            'order_id'         => $order->id,
            'note_id'          => $order->note_id,
            'entry_rule'       => ClosureTarget::ENTRY_RULE_EXCEPTION,
            'entry_reference'  => [
                'status_sist_no_momento' => $order->statusSist,
            ],
            'snapshot_status_sist' => $order->statusSist,
            'frozen_at'            => now(),
            'is_exception'         => true,
            'exception_reason'     => $reason,
            'requested_by'         => $requestedBy,
            'authorized_by'        => $authorizedBy,
            'authorized_at'        => now(),
        ]);
    }
}
