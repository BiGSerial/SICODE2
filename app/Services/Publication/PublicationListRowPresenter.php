<?php

namespace App\Services\Publication;

use App\Helpers\DaysLeft;
use App\Models\{Note, Production};
use App\Support\Ops\{Deadline, RowState};
use Carbon\Carbon;

/**
 * Monta, para as listas de Publicação do despacho (Main e PartnerMain), tudo o que uma linha mostra, já com tom semântico
 * (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; a regra de bloqueio e de prazo mora aqui.
 *
 * Tudo sai das relações já carregadas pelo componente (`WorkForm`, `RamalForm` e `FlowProductions.Production.User`):
 * nenhuma consulta por linha.
 */
class PublicationListRowPresenter
{
    /** Vencimento: verde acima de 15 dias, âmbar até 15, vermelho vencido (mesma regra de sempre da atividade). */
    public const DUE_WARN_DAYS = 15;

    /** Production de Publicação em vigor do informe da linha (a última marcada como atual no fluxo do informe). */
    public function currentProduction(Note $list, string $serviceUuid): ?Production
    {
        $workReport = $list->relationLoaded('WorkForm') ? $list->WorkForm : null;

        if (!$workReport || !$workReport->relationLoaded('FlowProductions')) {
            return null;
        }

        return collect($workReport->FlowProductions)
            ->where('stage', \App\Models\WorkReportFlowProduction::STAGE_PUBLICATION)
            ->where('is_current', true)
            ->pluck('Production')
            ->filter(fn ($production) => $production && (string) $production->service_id === $serviceUuid)
            ->last();
    }

    /**
     * Bloqueio da linha (mesmos níveis de sempre): 0 livre, 1 com usuário, 2 na pilha (sem usuário), 3 concluída,
     * 4 confirmada (OP20). Só o nível 4 permite despachar de novo (`command`).
     */
    public function blockLevel(Note $list, ?Production $production): int
    {
        if (!$production) {
            return 0;
        }

        return match (true) {
            (bool) $production->confirmed                                                                 => 4,
            $production->completed || ($list->RamalForm && !$list->WorkForm && $production->status == 28) => 3,
            $production->status == 1                                                                      => 2,
            default                                                                                       => 1,
        };
    }

    public function present(Note $list, string $serviceUuid): array
    {
        return $this->presentWith($list, $this->currentProduction($list, $serviceUuid));
    }

    /**
     * Linha com a Production já resolvida por quem chama (Serviços escolhe a Production pelo vínculo com o informe).
     * `$withScopes`: inclui os selos de escopo do informe final (Rede, Ligação, Geral).
     */
    public function presentWith(Note $list, ?Production $production, bool $withScopes = false): array
    {
        $block   = $this->blockLevel($list, $production);
        $command = $production && $production->confirmed;
        $wf      = $list->WorkForm;
        $ramal   = $list->RamalForm;

        [$state, $reason] = match ($block) {
            4       => [RowState::LATE, 'Informe publicado e confirmado (OP20). Pode ser despachado novamente.'],
            3       => [RowState::DONE, 'Publicação concluída; aguardando a confirmação da OP20.'],
            2       => [RowState::STACK, 'Está na pilha da empresa, ainda sem usuário.'],
            1       => [RowState::ASSIGNED, 'Atribuída a um usuário e em andamento.'],
            default => [RowState::FREE, 'Sem atividade aberta. Pode ser atribuída.'],
        };

        $source     = $wf ?: $ramal;
        $informedAt = $source ? ($source->informed_at ?: $source->created_at) : null;
        $informedAt = $informedAt ? Carbon::parse($informedAt) : null;
        $informedD  = $informedAt ? (int) $informedAt->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;

        $due       = new DaysLeft($list);
        $dueLabel  = $due->getLastDate();
        $dueAt     = $dueLabel === '---' ? null : Carbon::createFromFormat('d/m/Y', $dueLabel)->startOfDay();
        $dueRemain = $dueAt ? $due->getDaysLeft() : null;

        $assignee = null;

        if ($block) {
            $name     = $production?->User?->name;
            $parts    = $name ? explode(' ', trim($name)) : [];
            $assignee = $parts ? (count($parts) > 1 ? $parts[0] . ' ' . end($parts) : $parts[0]) : 'Sem usuário';
        }

        return [
            'key'          => (string) ($list->publication_context_key ?? $list->id),
            'noteId'       => (int) $list->id,
            'note'         => $list->note,
            'is45'         => (bool) $list->is45,
            'workReportId' => (int) ($list->dispatch_work_report_id ?? 0) ?: null,
            'ramalOnly'    => !$wf && $ramal,
            'scopes'       => $withScopes ? $this->scopes($wf) : [],
            'stackDays'    => $wf?->informed_at ? (int) Carbon::parse($wf->informed_at)->diffInDays(now(), false) : null,
            'rubrica'      => $list->rubrica,
            'material'     => $list->material,
            'pedido'       => $list->numPedido,
            'company'      => $wf ? $wf->Company?->name : $ramal?->Company?->name,
            'city'         => $list->lexp,
            'executedAt'   => $wf?->date ? Carbon::parse($wf->date) : null,
            'informedAt'   => $informedAt,
            'informedDays' => $informedD,
            'center'       => (int) $list->type_note === 1 ? $list->centerjob : null,
            'status'       => $list->nstats,
            'dueAt'        => $dueAt,
            'dueDays'      => $dueRemain,
            'dueTone'      => Deadline::remaining($dueRemain, self::DUE_WARN_DAYS),
            'productionId' => $production?->id,
            'block'        => $block,
            'state'        => $state,
            'reason'       => $reason,
            'command'      => (bool) $command,
            'canDispatch'  => !$block || $command,
            'canSelect'    => !$block,
            'assignee'     => $assignee,
        ];
    }

    /** @return array<int, array{label: string, tone: string, publishable: bool}> */
    private function scopes($workReport): array
    {
        if (!$workReport) {
            return [['label' => 'Geral', 'tone' => 'muted', 'publishable' => false]];
        }

        return collect($workReport->finalScopeBadges())->map(fn ($b) => [
            'label' => $b['label'],
            'tone'  => match ($b['class']) {
                'text-bg-primary' => 'blue',
                'text-bg-warning' => 'amber',
                default           => 'muted',
            },
            'publishable' => $b['scope'] === 'network',
        ])->all();
    }

    /**
     * Linha do Controle de Publicação (uma Production já despachada). Sem cor de situação: o estado vive no selo de Status;
     * a única marca de linha é a prioridade.
     */
    public function presentControl(Production $list): array
    {
        $note     = $list->Note;
        $workForm = $note->WorkForm;
        $ramal    = $note->RamalForm;
        $orders   = $workForm?->Orders?->count() ? $workForm->Orders : ($ramal?->Orders ?? collect());
        $days     = fn ($date) => $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays(now()->startOfDay()) : null;

        $due       = new DaysLeft($note);
        $dueLabel  = $due->getLastDate();
        $dueAt     = $dueLabel === '---' ? null : Carbon::createFromFormat('d/m/Y', $dueLabel)->startOfDay();
        $dueRemain = $dueAt ? $due->getDaysLeft() : null;

        return [
            'id'           => (int) $list->id,
            'noteId'       => (int) $list->note_id,
            'note'         => $note->note,
            'is45'         => (bool) $note->is45,
            'priority'     => (bool) $list->priority,
            'ri'           => (bool) $list->d5,
            'block'        => (bool) $list->block,
            'ramalOnly'    => !$workForm && $ramal,
            'inReview'     => (bool) ($workForm && $workForm->rejected),
            'orders'       => $orders->pluck('ordem')->filter()->values()->all(),
            'rubrica'      => $note->rubrica,
            'material'     => $note->material,
            'city'         => $note->lexp,
            'company'      => $list->Company?->name,
            'user'         => $list->User,
            'hasUser'      => (bool) $list->user_id,
            'status'       => (int) $list->status,
            'completed'    => (bool) $list->completed,
            'dispatchAt'   => $list->dispatch_at ? Carbon::parse($list->dispatch_at) : null,
            'dispatchDays' => $days($list->dispatch_at),
            'attAt'        => $list->att_at ? Carbon::parse($list->att_at) : null,
            'attDays'      => $days($list->att_at),
            'dueAt'        => $dueAt,
            'dueDays'      => $dueRemain,
            'dueTone'      => Deadline::remaining($dueRemain, self::DUE_WARN_DAYS),
        ];
    }
}
