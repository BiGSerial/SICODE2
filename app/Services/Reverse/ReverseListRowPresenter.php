<?php

namespace App\Services\Reverse;

use App\Custom\Notestatus;
use App\Helpers\DaysLeft;
use App\Models\{Note, Production};
use App\Services\Design\BlockEvaluator;
use App\Support\Ops\{RowState, Tone};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Linha pronta das telas do Fluxo Reverso (Despacho, Controle e Serviços) no padrão de listas operacionais
 * (docs/PADRAO_LISTAS_OPERACIONAIS.md): a view só desenha, nunca decide cor nem calcula prazo.
 */
class ReverseListRowPresenter
{
    /** Prazo total (dias) de que a coluna "Prazo Real" subtrai os dias que faltam. */
    public const SLA_DAYS = 30;

    public const PRAZO_HINT = 'Dias do prazo já consumidos (de 30); os tempos em status não contabilizáveis já foram descontados. Verde: 10 ou mais dias para vencer. Âmbar: de 6 a 9. Vermelho: menos de 6. Cinza: vencido.';

    /** Situação da linha a partir do resultado do `BlockEvaluator` (5 = inconsistente/aberta, tratada como "com usuário"). */
    public static function stateFromBlock(int $block): string
    {
        return $block === BlockEvaluator::HOLD_INCONST ? RowState::ASSIGNED : RowState::fromBlock($block);
    }

    /** Nome curto (primeiro e último). */
    public static function shortName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $parts = explode(' ', $name);

        return count($parts) > 1 ? $parts[0] . ' ' . end($parts) : $parts[0];
    }

    /**
     * "Prazo Real": tom pela regra original (vencido cinza; menos de 6 dias vermelho; menos de 10 âmbar; senão verde)
     * e o número exibido (prazo de 30 dias menos o que falta).
     *
     * @return array{tone: string, label: int, hint: string}
     */
    public function deadline(?int $daysLeft): array
    {
        $days = (int) $daysLeft;

        return [
            'tone' => match (true) {
                $days < 0  => 'muted',
                $days < 6  => 'red',
                $days < 10 => 'amber',
                default    => 'green',
            },
            'label' => self::SLA_DAYS - $days,
            'hint'  => self::PRAZO_HINT,
        ];
    }

    /** Selo "Situação" do SAP (parecer de prazo). */
    public function parecer(?string $parecer): array
    {
        return match ($parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Prazo do SAP vencido'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Prazo do SAP em dia'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'O SAP não informou o parecer de prazo'],
        };
    }

    /**
     * Linha da Lista para despacho (Nota/OV). `$state` = `rowDispatchState()` do componente (bloqueio + pilha da empresa).
     */
    public function dispatchRow(Note $list, array $state): array
    {
        $eval  = $state['eval'];
        $stack = $state['stack'];

        $production = $stack ?: ($eval['production'] ?? null);
        $assignee   = self::shortName($production?->User?->name ?: $production?->Company?->name) ?? ($production ? 'Desconhecido' : null);

        return [
            'key'         => (string) $list->id,
            'noteId'      => (int) $list->id,
            'note'        => $list->note,
            'state'       => $stack ? RowState::FREE : self::stateFromBlock((int) $eval['block']),
            'reason'      => $stack ? 'Disponível na pilha da empresa para atribuição individual.' : null,
            'canDispatch' => (bool) $state['canDispatch'],
            'stackOpen'   => (bool) $stack,
            'assignee'    => $assignee,
            'count'       => (int) ($eval['count'] ?? 0),
            'createdAt'   => $list->dt_created,
            'numPedido'   => $list->numPedido ? mb_strtoupper($list->numPedido) : null,
            'rubrica'     => $list->rubrica,
            'city'        => $list->lexp,
            'material'    => $list->material,
            'group1'      => $list->group1,
            'group2'      => $list->group2,
            'center'      => (int) $list->type_note === 1 ? $list->centerjob : null,
            'status'      => $list->nstats,
            'deadline'    => $this->deadline($list->days_left),
            'parecer'     => $this->parecer($list->pze_parecer),
        ];
    }

    /**
     * Linha do Controle (Production). Sem cor de situação: só a prioridade marca a linha.
     */
    public function controlRow(Production $list): array
    {
        $note = $list->Note;

        return [
            'id'           => (int) $list->id,
            'noteId'       => (int) $list->note_id,
            'note'         => $note->note,
            'priority'     => (bool) $list->priority,
            'ri'           => (bool) $list->d5,
            'group2'       => $note->group2,
            'rubrica'      => $note->rubrica,
            'center'       => $list->centroTrab ?: $note->centerjob,
            'city'         => $note->lexp,
            'zone'         => $note->group1,
            'description'  => $note->rubrica == 'BT Zero' ? $note->numPedido : $note->material,
            'company'      => $list->Company?->name,
            'dispatchAt'   => $list->dispatch_at,
            'dispatchDays' => $this->daysSince($list->dispatch_at),
            'attAt'        => $list->att_at,
            'attDays'      => $this->daysSince($list->att_at),
            'deadline'     => $this->deadline((new DaysLeft($note))->getDaysLeft()),
            'statusTone'   => Tone::forProductionStatus($list->status),
            'statusLabel'  => Notestatus::status($list->status)->status,
        ];
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha (`<x-legal.note-demand-tags>`). */
    public function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    private function daysSince($date): ?int
    {
        return $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays(now()->startOfDay()) : null;
    }

    /**
     * Linha de Serviços (auto-atribuição): bloqueio pelas Productions da atividade, na regra original:
     * data do SAP mudou desde a última = livre; aberta = com usuário (ou pilha, se sem usuário); concluída = concluída ou SAP pendente.
     *
     * @return array<string, mixed>
     */
    public function serviceRow(Note $list, string $serviceUuid, int $userId, bool $seesNote): array
    {
        $last = $list->Productions->where('service_id', $serviceUuid)->last();

        $block = 0;

        if ($last && $last->dt_note != $list->dt_status) {
            $block = 0;
        } elseif ($last && !$last->completed && !$last->confirmed) {
            $block = 1;
        } elseif ($last && $last->completed && !$last->confirmed) {
            $block = 2;
        } elseif ($last && $last->completed && $last->confirmed) {
            $block = 3;
        } elseif ($last && $last->dt_note === $list->dt_status) {
            $block = 3;
        }

        $assignee = $last ? (self::shortName($last->User?->name) ?? 'Desconhecido') : null;
        $noUser   = $last && !$last->User;

        $state = match (true) {
            $block === 1 && !$noUser => RowState::ASSIGNED,
            $block === 1             => RowState::STACK,
            $block === 2             => RowState::DONE,
            $block === 3             => RowState::LATE,
            default                  => RowState::FREE,
        };

        return [
            'key'         => (string) $list->id,
            'noteId'      => (int) $list->id,
            'note'        => ($seesNote || ($last && (int) $last->user_id === $userId)) ? $list->note : null,
            'state'       => $state,
            'canDispatch' => $block === 0,
            'assignee'    => $assignee,
            'createdAt'   => $list->dt_created,
            'numPedido'   => $list->numPedido ? mb_strtoupper($list->numPedido) : null,
            'rubrica'     => $list->rubrica,
            'city'        => $list->lexp,
            'group1'      => $list->group1,
            'group2'      => $list->group2,
            'material'    => $list->material,
            'status'      => $list->nstats,
            'statusAt'    => $list->dt_status,
            'deadline'    => $this->deadline($list->days_left),
            'parecer'     => $this->parecer($list->pze_parecer),
        ];
    }
}
