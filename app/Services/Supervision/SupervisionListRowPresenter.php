<?php

namespace App\Services\Supervision;

use App\Models\{Note, Production};
use App\Support\Ops\{Deadline, RowState};
use App\Support\SicodeRules;
use Carbon\Carbon;

/**
 * Monta, para as listas de Despacho da Fiscalização (operacional e da parceira), tudo o que uma linha mostra, já com
 * tom semântico (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; regra de prazo/cor mora aqui e em
 * `App\Support\Ops`. Regra de negócio (quais notas entram, quem pode ser atribuído) NÃO muda: vem pronta em `$state`.
 *
 * `$state` (montado pelo componente, uma vez por linha): `eval` (resultado do `BlockEvaluator`), `stack` (Production aberta
 * na pilha da empresa, ou null), `canDispatch`, `count` (vezes que passou por esta atividade), `partnerProduction`.
 */
class SupervisionListRowPresenter
{
    /** Cores do "Informado em" (dias desde o informe): verde até 20, âmbar de 21 a 27, vermelho a partir de 28. */
    public const INFORMED_OK_UNTIL = 20;

    public const INFORMED_WARN_UNTIL = 27;

    /**
     * @param string $profile `dispatch` (lista operacional) ou `partner` (parceira: valor = ADS, sem D5 passiva)
     */
    public function present(Note $list, array $state, string $serviceUuid, string $profile = 'dispatch', array $legalTags = []): array
    {
        $partner = $profile === 'partner';
        $eval    = $state['eval'];
        $stack   = $state['stack'] ?? null;

        $workReportId = (int) ($partner ? ($list->dispatch_work_report_id ?? 0) : ($list->operational_work_report_id ?? 0));
        $partialId    = (int) ($list->dispatch_partial_id ?? 0);
        $fiveNoteId   = (int) ($list->dispatch_five_note_id ?? 0);
        $passiveD5    = !$partner && $fiveNoteId > 0;

        /** @var \App\Models\WorkReport|null $workForm */
        $workForm = $list->WorkForm;
        $partial  = !$passiveD5 && (bool) $eval['isPartial'];

        $latestPartial = $passiveD5 || !$list->relationLoaded('Partials')
            ? null
            : (!$partner && $partialId > 0
                ? $list->Partials->firstWhere('id', $partialId)
                : $list->Partials->where('allow', true)->where('deny', false)->sortByDesc('created_at')->first());

        $production = $eval['production'] ?? null;
        $production = $production instanceof Production ? $production : null;

        $orders = $workForm
            ? $workForm->Orders
            : ($latestPartial ? $latestPartial->Orders : collect());

        $five = $list->FiveNote;

        // ADS: legada (OldAds) > informe > nota.
        $adsAt = null;

        if ($list->OldAds->isNotEmpty()) {
            $adsAt = $list->OldAds->last()->date;
        } elseif ($workForm?->Adsform) {
            $adsAt = $workForm->Adsform->created_at;
        } elseif ($list->Adsform) {
            $adsAt = $list->Adsform->created_at;
        }

        $informedAt = $workForm ? $workForm->informed_at : $latestPartial?->created_at;
        $informedAt = $informedAt ? Carbon::parse($informedAt) : null;
        $adsAt      = $adsAt ? Carbon::parse($adsAt) : null;

        $informedDays = $informedAt ? (int) $informedAt->copy()->startOfDay()->diffInDays(now()->startOfDay(), false) : null;
        $adsAfter     = $informedAt && $adsAt ? (int) $informedAt->copy()->startOfDay()->diffInDays($adsAt->copy()->startOfDay(), false) : null;

        $rowState = $this->rowState($stack, $eval);

        // D5 (2º ciclo do informe): as datas do informe e da ADS não são cobradas; ficam em cinza (`secondary`) com "D5" no lugar dos dias.
        $cycleTwo = (bool) ($five?->is_completed && !$five?->is_supervisioned);

        $assignee = $production?->User ? $this->shortName($production->User->name) : null;
        $company  = $production?->Company ? mb_convert_case(mb_strtolower(explode(' ', $production->Company->name)[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : null;

        $latestProduction = $state['count'] ? $production : null;

        return [
            'key'       => (string) $state['key'],
            'noteId'    => (int) $list->id,
            'note'      => $list->note,
            'isPartial' => $partial,
            'scopes'    => (!$partial && $workForm) ? collect($workForm->finalScopeBadges())->map(fn ($b) => [
                'label' => $b['label'],
                'tone'  => match ($b['class']) {
                    'text-bg-primary' => 'blue',
                    'text-bg-warning' => 'amber',
                    default           => 'muted',
                },
            ])->all() : [],
            'd5' => ($five?->is_completed && !$five?->is_supervisioned)
                ? ['tone' => 'green', 'hint' => 'D5 concluída – aguardando fiscalização']
                : null,
            'express'      => (string) $list->pze === '25' ? 'Nota expressa: prazo de execução de ' . $list->pze . ' dias' : null,
            'workReportId' => $workReportId ?: null,
            'legal'        => $legalTags[$list->note_id ?? $list->id] ?? [],
            'orders'       => $orders->pluck('ordem')->filter()->values()->all(),
            'dd'           => SicodeRules::dispatchDdFor($list, $serviceUuid, $production),
            'mmgd'         => (bool) $list->mmgd,
            'postes'       => $list->postes,
            'informedAt'   => $informedAt,
            'informedDays' => $informedDays,
            'informedTone' => $cycleTwo ? ($informedAt ? 'secondary' : 'muted') : Deadline::age($informedDays, self::INFORMED_OK_UNTIL, self::INFORMED_WARN_UNTIL),
            'informedText' => $cycleTwo && $informedAt ? 'D5' : null,
            'adsAt'        => $adsAt,
            'adsAfter'     => $adsAfter,
            'adsTone'      => $cycleTwo && $adsAt ? 'secondary' : 'muted',
            'adsText'      => $cycleTwo && $adsAt ? 'D5' : null,
            'cycleTwo'     => $cycleTwo,
            'adsTacit'     => (bool) (($workForm?->Adsform?->tacit ?? null) ?? ($list->Adsform?->tacit ?? false)),
            'numPedido'    => $list->numPedido ? mb_strtoupper($list->numPedido) : null,
            'rubrica'      => $list->rubrica,
            'city'         => $list->lexp,
            'amount'       => $partner
                ? ($workForm?->Adsform?->amount ?? $list->Adsform?->amount)
                : (float) ($list->orders?->sum('service_cost') ?? 0),
            'passes'   => (int) $state['count'],
            'lastBy'   => $latestProduction?->User ? $this->shortName($latestProduction->User->name) : 'Desconhecido',
            'status'   => $list->nstats,
            'center'   => $list->centerjob,
            'd5Days'   => $five?->completed_at ? (int) Carbon::parse($five->completed_at)->startOfDay()->diffInDays() : null,
            'deadline' => match ($list->pze_parecer) {
                'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Prazo de execução vencido'],
                'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Prazo de execução em dia'],
                default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'Sem parecer de prazo do SAP'],
            },
            'dispatchedAt'    => ($state['partnerProduction'] ?? null)?->dispatch_at,
            'state'           => $rowState,
            'reason'          => $this->reasonText($eval['reason'] ?? null, $rowState, (bool) $stack),
            'command'         => (bool) ($eval['command'] ?? false),
            'canDispatch'     => (bool) $state['canDispatch'],
            'stackOpen'       => (bool) $stack,
            'assignee'        => $assignee ?? 'Pilha',
            'assigneeCompany' => $company ?? 'Desconhecido',
            'event'           => $this->dispatchEvent($list, $workReportId, $partialId, $fiveNoteId, $partner),
        ];
    }

    /**
     * Situação da linha (as 5 do padrão). Com Production aberta na pilha da empresa a linha fica livre (a empresa pode assumir);
     * informe rejeitado conta como "SAP pendente"/problema (vermelho), como diz a legenda.
     */
    private function rowState(?Production $stack, array $eval): string
    {
        if ($stack) {
            return RowState::FREE;
        }

        if (str_starts_with((string) ($eval['reason'] ?? ''), 'workform_rejeitado')) {
            return RowState::LATE;
        }

        return RowState::fromBlock((int) $eval['block']);
    }

    /** Evento do despachante (mesmos contextos de antes): informe da linha ou, sem informe elegível, a nota/parcial/D5. */
    private function dispatchEvent(Note $list, int $workReportId, int $partialId, int $fiveNoteId, bool $partner): array
    {
        if (!$partner && $workReportId > 0) {
            return ['name' => 'openForWorkReports', 'payload' => [$workReportId]];
        }

        $context = $partner
            ? [
                'note_id'        => $list->id,
                'work_report_id' => $workReportId,
                'partial_id'     => $partialId,
                'five_note_id'   => $fiveNoteId,
            ]
            : [
                'note_id'         => $list->id,
                'work_report_id'  => null,
                'five_note_id'    => $fiveNoteId ?: null,
                'partial_id'      => $partialId ?: null,
                'bulk_any_status' => true,
            ];

        return ['name' => 'openForNotes', 'payload' => [$context]];
    }

    /** Texto em português para o tooltip (antes mostrava o código interno do avaliador). */
    private function reasonText(?string $reason, string $state, bool $stack): string
    {
        if ($stack) {
            return 'Disponível na pilha da empresa para atribuição individual.';
        }

        $reason = (string) $reason;

        return match (true) {
            str_starts_with($reason, 'workform_rejeitado')             => 'Informe rejeitado: aguarda correção da empreiteira.',
            str_ends_with($reason, '_aguardando_sap')                  => 'Concluída há menos de 12 h; aguardando o SAP atualizar.',
            $reason === 'producao_status_guarda'                       => 'Está na pilha da empresa, ainda sem usuário.',
            $reason === 'producao_em_andamento'                        => 'Atribuída a um usuário e em andamento.',
            $reason === 'fivenote_prioritario_producao_aberta_dfive'   => 'D5 em andamento com um usuário.',
            $reason === 'fivenote_prioritario_antes_da_producao_dfive' => 'D5 concluída antes da atividade e o SAP ainda não atualizou.',
            $reason === 'workform_reinformado_pos_producao_concluida'  => 'Informe reenviado depois da atividade: pode ser atribuído de novo.',
            in_array($reason, ['producao_concluida'], true)            => 'Concluída; aguardando o SAP atualizar.',
            $state === RowState::LATE                                  => 'Concluída há 12 h ou mais e o SAP ainda não atualizou.',
            default                                                    => RowState::all()[$state]['hint'],
        };
    }

    private function shortName(?string $name): string
    {
        $parts = collect(explode(' ', trim((string) $name)))->filter()->values();

        if ($parts->isEmpty()) {
            return 'Desconhecido';
        }

        $short = $parts->count() > 1 ? $parts->first() . ' ' . $parts->last() : $parts->first();

        return mb_convert_case(mb_strtolower($short, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
