<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureOrderState;
use Carbon\Carbon;

/**
 * Sinalização por cor das Ordens nas listas do Encerramento (Despacho, Controle, Atividade). UMA regra para todas as telas.
 *
 *  - vermelho (overdue): finalizada há mais de 24h e o SAP ainda não encerrou a Ordem (ENTE/ENCE) — precisa de atenção;
 *  - verde    (done):    concluída (finalizada pelo responsável, aguardando a meta, ou encerrada no SAP);
 *  - azul     (assigned): atribuída a um responsável e em tratativa;
 *  - amarelo  (pile):    está na pilha de alguém (empresa/usuário) e NÃO foi atribuída.
 *
 * Precedência: vermelho > verde > azul > amarelo. Ordem não despachada ou fora dessas regras não tem sinal (null).
 * Dados de entrada: status operacional, responsável atribuído, posse atual, quando entrou no status e statusSist do SAP.
 */
final class ClosureSignal
{
    public const ASSIGNED = 'assigned';
    public const DONE     = 'done';
    public const OVERDUE  = 'overdue';
    public const PILE     = 'pile';

    /** Horas depois de finalizada em que a falta de baixa no SAP vira alerta vermelho. */
    public const OVERDUE_AFTER_HOURS = 24;

    private const DONE_STATUSES = ['FINISHED', 'WAITING', 'CLOSED'];

    /** Status em que a atribuição ainda vale como "em tratativa". */
    private const WORKING_STATUSES = ['ASSIGNED', 'IN_PROGRESS', 'PAUSED', 'WAITING_PARTNER', 'DEPOSIT_BLOCKED', 'DEPOSIT_RELEASED'];

    private const META = [
        self::OVERDUE  => ['Sem baixa no SAP', 'ri-alarm-warning-line', 'Finalizada há mais de 24h e o SAP ainda não encerrou a Ordem (ENTE/ENCE).'],
        self::DONE     => ['Concluída', 'ri-checkbox-circle-line', 'Concluída: finalizada pelo responsável, aguardando a meta ou já encerrada no SAP.'],
        self::ASSIGNED => ['Atribuída', 'ri-user-follow-line', 'Atribuída a um responsável e em tratativa.'],
        self::PILE     => ['Na pilha, sem atribuição', 'ri-inbox-line', 'Está na pilha de alguém e ainda não foi atribuída a um responsável.'],
    ];

    /**
     * @return array{key: string, label: string, icon: string, hint: string}|null
     */
    public static function resolve(
        ?string $operationalStatus,
        mixed $assignedUserId,
        mixed $holderCompanyId,
        mixed $holderUserId,
        mixed $enteredStatusAt,
        ?string $statusSist,
        ?Carbon $now = null,
    ): ?array {
        $status = (string) $operationalStatus;

        if ($status === '' || $status === 'CANCELLED') {
            return null;
        }

        $key = null;

        if ($status === 'FINISHED' && self::overdue($enteredStatusAt, $statusSist, $now)) {
            $key = self::OVERDUE;
        } elseif (in_array($status, self::DONE_STATUSES, true)) {
            $key = self::DONE;
        } elseif (!empty($assignedUserId) && in_array($status, self::WORKING_STATUSES, true)) {
            $key = self::ASSIGNED;
        } elseif ($status === 'AVAILABLE' && empty($assignedUserId) && (!empty($holderCompanyId) || !empty($holderUserId))) {
            $key = self::PILE;
        }

        return $key === null ? null : self::describe($key, $key === self::OVERDUE ? self::hoursSince($enteredStatusAt, $now) : null);
    }

    public static function forState(ClosureOrderState $state, ?Carbon $now = null): ?array
    {
        return self::resolve(
            $state->operational_status,
            $state->assigned_user_id,
            $state->current_holder_company_id,
            $state->current_holder_user_id,
            $state->entered_current_status_at,
            $state->Order?->statusSist,
            $now,
        );
    }

    /** Linha de lista (consulta com colunas do estado): operational_status, assigned_user_id, current_holder_*, entered_current_status_at, statusSist. */
    public static function forRow(object $row, ?Carbon $now = null): ?array
    {
        return self::resolve(
            $row->operational_status ?? null,
            $row->assigned_user_id ?? null,
            $row->current_holder_company_id ?? null,
            $row->current_holder_user_id ?? null,
            $row->entered_current_status_at ?? null,
            $row->statusSist ?? null,
            $now,
        );
    }

    /** Legenda (ordem da precedência) para as telas. @return array<int, array{key: string, label: string, icon: string, hint: string}> */
    public static function legend(bool $executor = false): array
    {
        // Na tela do executor tudo é dele: "atribuída" e "na pilha" não distinguem nada, então só restam as cores de atenção e de conclusão.
        $keys = $executor ? [self::OVERDUE, self::DONE] : [self::OVERDUE, self::DONE, self::ASSIGNED, self::PILE];

        return array_map(fn (string $key) => self::describe($key), $keys);
    }

    private static function describe(string $key, ?int $overdueHours = null): array
    {
        [$label, $icon, $hint] = self::META[$key];

        if ($key === self::OVERDUE && $overdueHours !== null) {
            $hint .= " Finalizada há {$overdueHours}h.";
        }

        return ['key' => $key, 'label' => $label, 'icon' => $icon, 'hint' => $hint];
    }

    private static function overdue(mixed $enteredStatusAt, ?string $statusSist, ?Carbon $now): bool
    {
        if ($enteredStatusAt === null || self::sapClosed((string) $statusSist)) {
            return false;
        }

        return self::hoursSince($enteredStatusAt, $now) >= self::OVERDUE_AFTER_HOURS;
    }

    private static function hoursSince(mixed $enteredStatusAt, ?Carbon $now): int
    {
        if ($enteredStatusAt === null) {
            return 0;
        }

        return (int) Carbon::parse($enteredStatusAt)->diffInHours($now ?? now());
    }

    private static function sapClosed(string $statusSist): bool
    {
        return str_starts_with($statusSist, 'ENTE') || str_starts_with($statusSist, 'ENCE');
    }
}
