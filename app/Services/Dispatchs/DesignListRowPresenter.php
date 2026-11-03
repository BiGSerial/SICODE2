<?php

namespace App\Services\Dispatchs;

use App\Helpers\DaysLeft;
use App\Models\{Note, Production};
use App\Services\Design\BlockEvaluator;
use App\Support\Ops\RowState;

/**
 * Monta, para as listas do Desenho (Despacho, Controle e Serviços), tudo o que uma linha mostra, já com tom semântico
 * (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; as regras de cor do Desenho moram aqui.
 *
 * Regras de cor preservadas da tela antiga:
 *  - DStatus (dias no status): verde abaixo de 4; âmbar de 4 até o limite; vermelho acima dele. O limite é 5 na lista de
 *    despacho e 6 no controle e em Serviços (como já era).
 *  - Prazo real (dias que faltam, `days_left`): vencido cinza; de 0 a 5 vermelho; de 6 a 9 âmbar; 10 ou mais verde.
 *    A tela mostra os dias já gastos (30 menos o que falta).
 */
class DesignListRowPresenter
{
    /** Textos da avaliação de bloqueio em português (antes o tooltip mostrava o código interno). */
    private const REASONS = [
        'no_production'                             => 'Sem atividade aberta. Pode ser atribuída.',
        'different_and_completed_confirmed'         => 'Já concluída e confirmada, mas a nota mudou de status ou de data no SAP. Pode ser atribuída de novo.',
        'same_and_completed_confirmed'              => 'Concluída e confirmada sem mudança no SAP.',
        'same_completed_not_confirmed'              => 'Concluída há pouco; aguardando o SAP atualizar.',
        'production_without_assignment'             => 'Despachada para a empresa, ainda sem usuário.',
        'production_status_2_not_completed'         => 'Atribuída a um usuário e em andamento.',
        'production_completed_waiting_confirmation' => 'Concluída; aguardando confirmação.',
        'prod_inconst_open_generic'                 => 'Atividade aberta em situação fora do fluxo padrão.',
    ];

    /** Converte o nível do `BlockEvaluator` do Desenho na situação visual da linha. */
    public static function rowState(array $eval, bool $stackOpen): string
    {
        if ($stackOpen) {
            return RowState::FREE;
        }

        return match ((int) $eval['block']) {
            BlockEvaluator::HOLD_BLUE   => RowState::ASSIGNED,
            BlockEvaluator::HOLD_YELLOW => RowState::STACK,
            BlockEvaluator::HOLD_GREEN  => RowState::DONE,
            BlockEvaluator::HOLD_RED    => RowState::LATE,
            // Fora do fluxo padrão (antes cinza): aberta com usuário, ou concluída aguardando confirmação.
            BlockEvaluator::HOLD_INCONST => ($eval['reason'] ?? null) === 'production_completed_waiting_confirmation' ? RowState::DONE : RowState::ASSIGNED,
            default                      => RowState::FREE,
        };
    }

    /** Tom do "dias no status": verde < 4, âmbar até `$dangerAbove`, vermelho acima. */
    public static function daysInStatusTone(?int $days, int $dangerAbove): string
    {
        return match (true) {
            $days === null       => 'muted',
            $days < 4            => 'green',
            $days > $dangerAbove => 'red',
            default              => 'amber',
        };
    }

    public static function daysInStatusHint(int $dangerAbove): string
    {
        return 'Dias no status atual. Verde: menos de 4. Âmbar: de 4 a ' . $dangerAbove . '. Vermelho: ' . ($dangerAbove + 1) . ' ou mais. Os prazos de Nota não seguem com precisão os regulatórios das OVs; avalie caso a caso.';
    }

    /** Tom do prazo real: vencido cinza, 0–5 dias vermelho, 6–9 âmbar, 10 ou mais verde. */
    public static function deadlineTone(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null => 'muted',
            $daysLeft < 0      => 'muted',
            $daysLeft < 6      => 'red',
            $daysLeft < 10     => 'amber',
            default            => 'green',
        };
    }

    public const DEADLINE_HINT = 'Prazo real, já descontados os tempos em status não contabilizáveis. Verde: 10 ou mais dias para vencer. Âmbar: de 6 a 9. Vermelho: até 5. Cinza: vencido.';

    /** @return array<string, mixed> */
    public function deadline(?int $daysLeft): array
    {
        return [
            'value' => $daysLeft === null ? null : 30 - $daysLeft,
            'tone'  => self::deadlineTone($daysLeft),
            'hint'  => ($daysLeft !== null && $daysLeft < 0 ? 'Vencido. ' : '') . self::DEADLINE_HINT,
            'late'  => $daysLeft !== null && $daysLeft < 0,
        ];
    }

    private function shortName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $parts = explode(' ', trim($name));

        return count($parts) > 1 ? $parts[0] . ' ' . end($parts) : $parts[0];
    }

    private function daysSince($date): ?int
    {
        return $date ? (int) \Carbon\Carbon::parse($date)->startOfDay()->diffInDays(now()->startOfDay()) : null;
    }

    private function daysSinceStatus(?Note $note): ?int
    {
        return $note?->dt_status ? (int) $note->dt_status->diffInDays() : null;
    }

    /**
     * Linha da Lista para Desenho (despacho).
     *
     * @param array{eval: array, stack: ?Production, canDispatch: bool} $state
     */
    public function main(Note $list, array $state): array
    {
        $eval       = $state['eval'];
        $stack      = $state['stack'];
        $production = $stack ?: ($eval['production'] ?? null);
        $assignee   = $production?->User?->name ?: $production?->Company?->name;
        $days       = $this->daysSinceStatus($list);
        $isNote     = (int) $list->type_note === 1;

        $parecer = match ($list->pze_parecer) {
            'Vencido'     => ['label' => 'Vencido', 'tone' => 'red', 'hint' => 'Prazo de execução vencido'],
            'Não vencido' => ['label' => 'Em prazo', 'tone' => 'green', 'hint' => 'Prazo de execução em dia'],
            default       => ['label' => 'Desconhecido', 'tone' => 'muted', 'hint' => 'Sem parecer de prazo'],
        };

        return [
            'id'          => (int) $list->id,
            'note'        => $list->note,
            'is45'        => (bool) $list->is45,
            'doe'         => (bool) $list->doe,
            'mmgd'        => (bool) $list->mmgd,
            'createdAt'   => $list->dt_created,
            'numPedido'   => $list->numPedido ? mb_strtoupper($list->numPedido) : null,
            'rubrica'     => $list->rubrica,
            'city'        => $list->lexp,
            'material'    => $list->material,
            'group2'      => $list->group2,
            'group5'      => $list->group5,
            'postes'      => $list->postes,
            'returnCount' => (int) ($eval['count'] ?? 0),
            'returnUser'  => $production?->User?->name,
            'nstats'      => $list->nstats,
            // Nota (tipo 1) mostra o centro de trabalho com o status pequeno; OV mostra só o status.
            'statusMain'  => $isNote ? ($list->centerjob ?: null) : $list->nstats,
            'statusSub'   => $isNote ? $list->nstats : null,
            'days'        => $days,
            'daysTone'    => self::daysInStatusTone($days, 5),
            'daysHint'    => self::daysInStatusHint(5),
            'deadline'    => $this->deadline($list->days_left === null ? null : (int) $list->days_left),
            'parecer'     => $parecer,
            'state'       => self::rowState($eval, (bool) $stack),
            'reason'      => $stack ? 'Disponível na pilha da empresa para atribuição individual.' : (self::REASONS[$eval['reason'] ?? ''] ?? null),
            'canDispatch' => (bool) $state['canDispatch'],
            'stackOpen'   => (bool) $stack,
            'assignee'    => $this->shortName($assignee) ?? 'Desconhecido',
        ];
    }

    /** Linha do Controle do Desenho (uma Production). */
    public function control(Production $list): array
    {
        $note     = $list->Note;
        $daysLeft = (new DaysLeft($note))->getDaysLeft();
        $days     = $this->daysSinceStatus($note);

        $company      = $list->Company?->name;
        $companyLabel = null;

        if ($company) {
            $parts = array_values(array_filter(explode(' ', trim($company))));
            $count = count($parts);

            $companyLabel = match (true) {
                $count >= 3 => trim($parts[0] . ' ' . substr($parts[$count - 2], 0, 1) . substr($parts[$count - 1], 0, 1)),
                $count >= 2 => trim($parts[0] . ' ' . substr($parts[1], 0, 2)),
                default     => substr($parts[0] ?? '', 0, 1),
            };
        }

        return [
            'id'           => (int) $list->id,
            'noteId'       => (int) $list->note_id,
            'note'         => $note->note,
            'is45'         => (bool) $note->is45,
            'doe'          => (bool) $note->doe,
            'mmgd'         => (bool) $note->mmgd,
            'priority'     => (bool) $list->priority,
            'ri'           => (bool) $list->d5,
            'block'        => (bool) $list->block,
            'completed'    => (bool) $list->completed,
            'hasUser'      => (bool) $list->user_id,
            'user'         => $list->User,
            'group1'       => $note->group1,
            'group2'       => $note->group2,
            'rubrica'      => $note->rubrica,
            'center'       => $list->centroTrab ?: $note->centerjob,
            'city'         => $note->lexp,
            'description'  => $note->rubrica === 'BT Zero' ? $note->numPedido : $note->material,
            'company'      => $company,
            'companyLabel' => $companyLabel,
            'postes'       => $note->postes,
            'dispatchAt'   => $list->dispatch_at,
            'dispatchDays' => $this->daysSince($list->dispatch_at),
            'attAt'        => $list->att_at,
            'attDays'      => $this->daysSince($list->att_at),
            'days'         => $days,
            'daysTone'     => self::daysInStatusTone($days, 6),
            'daysHint'     => self::daysInStatusHint(6),
            'deadline'     => $this->deadline($daysLeft),
            'status'       => (int) $list->status,
        ];
    }

    /** Linha de Serviços → Desenho (a pilha de quem está logado). */
    public function mine(Production $list): array
    {
        $note    = $list->Note;
        $days    = $this->daysSinceStatus($note);
        $status  = (int) $list->status;
        $tracked = in_array($status, [Production::STATUS_IN_PROJECT_REVIEW, Production::STATUS_REJECTED_PROJECT_REVIEW, Production::STATUS_RELEASED_TO_FINISH], true)
            && (bool) ($list->has_project_review_cycle ?? false);
        $quality = $list->activeQualityProcess;
        $isOv    = (int) $note->type_note !== 1;

        return [
            'id'             => (int) $list->id,
            'noteId'         => (int) $note->id,
            'note'           => $note->note,
            'files'          => $note->Files,
            'priority'       => (bool) $list->priority,
            'ri'             => (bool) $list->d5,
            'express'        => $note->pze == '25',
            'expressDays'    => $note->pze,
            'doe'            => (bool) $note->doe,
            'rubrica'        => $note->rubrica,
            'city'           => $note->lexp,
            'zone'           => $note->group1,
            'material'       => $note->material,
            'postes'         => $note->postes,
            'noteStatus'     => $isOv ? $note->nstats : $note->centerjob,
            'receivedStatus' => $isOv ? $list->status_note : $list->centroTrab,
            'days'           => $days,
            'daysTone'       => self::daysInStatusTone($days, 6),
            'daysHint'       => self::daysInStatusHint(6),
            'dispatchDays'   => $this->daysSince($list->dispatch_at),
            'attDays'        => $this->daysSince($list->att_at),
            'deadline'       => $this->deadline($note->days_left === null ? null : (int) $note->days_left),
            'status'         => $status,
            'reviewTracked'  => $tracked,
            'quality'        => $quality ? [
                'phase' => $quality->phase->short(),
                'round' => $quality->round_number,
            ] : null,
            'completed' => (bool) $list->completed,
            'block'     => (bool) $list->block,
        ];
    }
}
