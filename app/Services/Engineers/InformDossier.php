<?php

namespace App\Services\Engineers;

use App\Models\{File, Operation, Order, Partial, PepBalanceAlert, ReturnWork, WorkReport, WorkReportFlowProduction};
use App\Services\Ads\AdsDeadlinePolicy;
use App\Services\Partner\WorkReportBudgetGuard;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Dossiê interno de um informe (final ou parcial) para a Engenharia: um formato único para os dois tipos,
 * lido pelo modal `Engineers\InformDetail`. Contém saldo de PEP, evidências do fiscal e motivos de rejeição,
 * por isso NUNCA deve ser entregue a telas do parceiro (empreiteira).
 */
class InformDossier
{
    public const TYPE_FINAL = 'final';

    public const TYPE_PARTIAL = 'partial';

    public function __construct(
        private WorkReportBudgetGuard $budgetGuard,
        private AdsDeadlinePolicy $deadlinePolicy,
    ) {
    }

    public function find(string $type, int $id): array
    {
        return $type === self::TYPE_PARTIAL
            ? $this->forPartial(Partial::query()->findOrFail($id))
            : $this->forFinal(WorkReport::query()->findOrFail($id));
    }

    public function forFinal(WorkReport $report): array
    {
        $report->load([
            'Note', 'Company', 'User', 'Orders.Operations', 'Orders.Pep', 'Equipment', 'Meeters', 'Files.User', 'Files.Service',
            'Returnwork' => fn ($q) => $q->with(['User:id,name', 'Service:id,uuid,service'])->orderBy('created_at'),
            'Adsform.Files',
            'FlowProductions' => fn ($q) => $q->with([
                'Production.Service:id,uuid,service',
                'Production.User:id,name',
                'LinkedBy:id,name',
                'ReversedBy:id,name',
            ])->orderBy('linked_at')->orderBy('id'),
        ]);

        $flows  = $report->FlowProductions;
        $orders = $report->Orders;

        return [
            'type'    => self::TYPE_FINAL,
            'id'      => $report->id,
            'title'   => 'Informe final',
            'note'    => $report->Note,
            'company' => $report->Company?->name,
            'status'  => [
                'label' => $report->canceled ? 'Cancelado' : ($report->current_status_label ?: ($report->rejected ? 'Rejeitado' : 'Informado')),
                'class' => $report->canceled ? 'text-bg-dark' : ($report->current_status_class ?: ($report->rejected ? 'text-bg-danger' : 'text-bg-success')),
            ],
            'scopes'      => $report->finalScopeBadges(),
            'kpis'        => $this->finalKpis($report, $orders),
            'facts'       => $this->finalFacts($report),
            'ads'         => $this->ads($report),
            'timeline'    => $this->finalTimeline($report, $flows),
            'observation' => ['contractor' => $report->observation, 'damage' => $report->description, 'by' => $report->informer ?: $report->responsible],
            'rejections'  => $report->Returnwork->map(fn (ReturnWork $r) => [
                'at'       => $r->created_at,
                'category' => $r->category,
                'text'     => $r->text_obs,
                'by'       => $r->User?->name,
                'service'  => $r->Service?->service,
            ])->values(),
            'rejected_now' => (bool) $report->rejected,
            'activities'   => $this->activities($flows),
            'evidence'     => $this->finalEvidence($report, $flows),
            'pep'          => $this->pep($orders, PepBalanceAlert::TYPE_WORK_REPORT, $report->id, $report->Adsform?->amount),
            'operations'   => $this->operations($orders),
            'equipment'    => $report->Equipment,
            'meeters'      => $report->Meeters,
        ];
    }

    public function forPartial(Partial $partial): array
    {
        $partial->load([
            'Note.Orders', 'Orders.Operations', 'Orders.Pep', 'Company', 'User', 'Engineer', 'Supervisor', 'Payer',
            'Files.User', 'Files.Service', 'productions.Service:id,uuid,service', 'productions.User:id,name',
        ]);

        $orders = $partial->Orders->isNotEmpty() ? $partial->Orders : ($partial->Note?->Orders ?? collect());
        $orders->load(['Operations', 'Pep']);

        [$label, $class] = match (true) {
            (bool) $partial->deny                       => ['Rejeitado', 'text-bg-danger'],
            $partial->payment && $partial->allow        => ['Pago', 'text-bg-success'],
            $partial->supervision && !$partial->payment => ['Em medição', 'text-bg-info'],
            $partial->allow && !$partial->supervision   => ['Em fiscalização', 'text-bg-info'],
            default                                     => ['Aguardando aprovação', 'text-bg-warning'],
        };

        $activities = $partial->productions->map(fn ($p) => $this->activityRow($p, null, $p->att_at))->values();

        return [
            'type'    => self::TYPE_PARTIAL,
            'id'      => $partial->id,
            'title'   => 'Informe parcial',
            'note'    => $partial->Note,
            'company' => $partial->Company?->name,
            'status'  => ['label' => $label, 'class' => $class],
            'scopes'  => [['label' => $partial->scopeLabel(), 'class' => 'text-bg-secondary']],
            'kpis'    => [
                ['label' => 'Valor ADS', 'value' => 'R$ ' . number_format((float) $partial->value, 2, ',', '.')],
                ['label' => 'Enviado em', 'value' => $partial->created_at?->format('d/m/Y H:i')],
                ['label' => 'Decisão', 'value' => $partial->decision_at?->format('d/m/Y H:i') ?? 'Em aprovação'],
                ['label' => 'Saldo PEP', 'value' => $this->balanceLabel($orders), 'tone' => $this->balanceTone($orders, (float) $partial->value)],
            ],
            'facts' => [
                ['label' => 'Cliente', 'value' => $partial->Note?->client],
                ['label' => 'Rubrica', 'value' => $partial->Note?->rubrica],
                ['label' => 'Município', 'value' => $partial->Note?->lexp],
                ['label' => 'Material', 'value' => $partial->Note?->material],
                ['label' => 'Responsável (empreiteira)', 'value' => $partial->responsible],
                ['label' => 'Enviado por', 'value' => $partial->User?->name],
                ['label' => 'Ordens', 'value' => $orders->pluck('ordem')->filter()->unique()->implode(', ')],
            ],
            'ads'      => null,
            'timeline' => [
                $this->step('Enviado', $partial->created_at, $partial->responsible, 'ri-send-plane-line', true),
                $this->step($partial->deny ? 'Rejeitado pela engenharia' : 'Decisão da engenharia', $partial->decision_at, $partial->Engineer?->name, $partial->deny ? 'ri-close-circle-line' : 'ri-check-double-line', $partial->allow || $partial->deny, $partial->deny ? 'danger' : null),
                $this->step('Fiscalização', $partial->supervision_at, $partial->Supervisor?->name, 'ri-shield-check-line', (bool) $partial->supervision),
                $this->step('Medição', $partial->payment_at, $partial->Payer?->name, 'ri-money-dollar-circle-line', (bool) $partial->payment),
            ],
            'observation' => ['contractor' => $partial->observation, 'damage' => null, 'by' => $partial->responsible, 'engineer' => $partial->engineer_info, 'engineer_by' => $partial->Engineer?->name],
            'rejections'  => $partial->deny ? collect([[
                'at'       => $partial->decision_at,
                'category' => 'Rejeição da engenharia',
                'text'     => $partial->engineer_info,
                'by'       => $partial->Engineer?->name,
                'service'  => null,
            ]]) : collect(),
            'rejected_now' => (bool) $partial->deny,
            'activities'   => $activities,
            'evidence'     => $this->partialEvidence($partial),
            'pep'          => $this->pep($orders, PepBalanceAlert::TYPE_PARTIAL, $partial->id, (float) $partial->value),
            'operations'   => $this->operations($orders),
            'equipment'    => collect(),
            'meeters'      => collect(),
        ];
    }

    // ---------------------------------------------------------------- final

    private function finalKpis(WorkReport $report, Collection $orders): array
    {
        $ads = $report->Adsform;

        return [
            ['label' => 'Execução', 'value' => $report->date?->format('d/m/Y')],
            ['label' => 'Informado em', 'value' => $report->informed_at?->format('d/m/Y H:i') ?? '—'],
            ['label' => 'Valor ADS', 'value' => $ads ? 'R$ ' . number_format((float) $ads->amount, 2, ',', '.') : 'Sem ADS'],
            ['label' => 'Saldo PEP', 'value' => $this->balanceLabel($orders), 'tone' => $this->balanceTone($orders, (float) ($ads?->amount ?? 0))],
        ];
    }

    private function finalFacts(WorkReport $report): array
    {
        return [
            ['label' => 'Rubrica', 'value' => $report->Note?->rubrica],
            ['label' => 'Município', 'value' => $report->Note?->lexp],
            ['label' => 'Ordens', 'value' => $report->Orders->pluck('ordem')->filter()->implode(', ')],
            ['label' => 'Mudança no projeto', 'value' => $report->changes ? 'Sim' : 'Não'],
            ['label' => 'Primeiro informe', 'value' => $report->created_at?->format('d/m/Y H:i:s')],
            ['label' => 'Último informe', 'value' => $report->informed_at?->format('d/m/Y H:i:s')],
            ['label' => 'Número DD', 'value' => $report->dd],
            ['label' => 'Equipe WPA', 'value' => $report->team],
            ['label' => 'Encarregado responsável', 'value' => $report->responsible],
            ['label' => 'Responsável pelo informe', 'value' => $report->informer],
        ];
    }

    /** ADS do informe final: prazo, chegada e se foi tácita. */
    private function ads(WorkReport $report): array
    {
        $ads   = $report->Adsform;
        $state = $this->deadlinePolicy->stateForWorkReport($report);
        $dueAt = $ads?->tacit_due_at ?: $report->ads_due_at
            ?: ($report->informed_at ? $this->deadlinePolicy->dueAt($report->informed_at, $state, $report->id) : null);
        $dueAt     = $dueAt ? Carbon::parse($dueAt) : null;
        $arrivedAt = $ads ? ($ads->tacit ? $ads->tacit_delivered_at : $ads->created_at) : null;
        $lateDays  = $dueAt ? $this->deadlinePolicy->lateDays($dueAt, $arrivedAt, now(), $state, $report->id) : 0;

        return [
            'informed_at' => $report->informed_at,
            'arrived'     => (bool) $ads,
            'arrived_at'  => $arrivedAt,
            'due_at'      => $dueAt,
            'tacit'       => (bool) $ads?->tacit,
            'late_days'   => $lateDays,
            'penalty'     => $lateDays > 0 ? $this->deadlinePolicy->penaltyPercentage($lateDays) : 0,
            'amount'      => $ads?->amount,
            'contract'    => $ads?->contract,
            'center'      => $ads?->center,
            'author'      => $ads?->name,
            'obs'         => $ads?->obs,
            'files'       => $ads?->Files ?? collect(),
            'acceptance'  => $report->acceptance_accepted ? ['at' => $report->acceptance_at, 'name' => $report->acceptance_name] : null,
        ];
    }

    private function finalTimeline(WorkReport $report, Collection $flows): array
    {
        $steps = [$this->step('Informado pela empreiteira', $report->informed_at ?? $report->created_at, $report->informer ?: $report->responsible, 'ri-send-plane-line', true)];

        foreach ($report->Returnwork as $return) {
            $steps[] = $this->step('Rejeitado: ' . ($return->category ?: 'sem categoria'), $return->created_at, $return->User?->name, 'ri-close-circle-line', true, 'danger');
        }

        foreach ($flows->groupBy('stage') as $stage => $rows) {
            $first   = $rows->first();
            $current = $rows->firstWhere('is_current', true) ?? $rows->last();
            $steps[] = $this->step($first->stageLabel(), $current->Production?->completed_at ?? $current->linked_at, $current->Production?->User?->name, 'ri-shield-check-line', (bool) $current->Production?->completed_at);
        }

        if ($report->canceled) {
            $steps[] = $this->step('Cancelado', $report->canceled_at, null, 'ri-forbid-line', true, 'danger');
        }

        return $steps;
    }

    private function activities(Collection $flows): Collection
    {
        return $flows->map(fn (WorkReportFlowProduction $f) => $this->activityRow($f->Production, $f, $f->linked_at))->values();
    }

    private function activityRow($production, ?WorkReportFlowProduction $flow, $assignedAt): array
    {
        return [
            'stage'          => $flow?->stageLabel(),
            'stage_class'    => $flow?->stageBadgeClass(),
            'production'     => $production?->id ?? $flow?->production_id,
            'service'        => $production?->Service?->service,
            'responsible'    => $production?->User?->name,
            'assigned_at'    => $production?->att_at ?? $assignedAt,
            'completed_at'   => $production?->completed_at,
            'origin'         => $flow?->sourceLabel(),
            'current'        => $flow?->is_current,
            'reversed_at'    => $flow?->reversed_at,
            'reverse_reason' => $flow?->reverse_reason,
        ];
    }

    // -------------------------------------------------------------- evidence

    /** Evidências separadas por origem: empreiteira x etapa de fiscalização/medição/publicação. */
    private function finalEvidence(WorkReport $report, Collection $flows): array
    {
        $ownIds        = $report->Files->pluck('id')->all();
        $productionIds = $flows->pluck('production_id')->filter()->unique()->all();

        $fromProductions = $productionIds
            ? File::query()->with(['User', 'Service', 'Productions:id', 'MorphProductions:id'])
                ->whereNotIn('files.id', $ownIds)
                ->where(fn ($q) => $q->whereHas('Productions', fn ($p) => $p->whereIn('productions.id', $productionIds))
                    ->orWhereHas('MorphProductions', fn ($p) => $p->whereIn('productions.id', $productionIds)))
                ->get()
            : collect();

        $stageByProduction = $flows->pluck('stage', 'production_id');
        $groups            = ['contractor' => $report->Files->values()];

        foreach ($fromProductions as $file) {
            $pid            = $file->Productions->pluck('id')->merge($file->MorphProductions->pluck('id'))->first(fn ($id) => isset($stageByProduction[$id]));
            $stage          = $stageByProduction[$pid] ?? 'other';
            $groups[$stage] = ($groups[$stage] ?? collect())->push($file);
        }

        return $this->evidenceGroups($groups);
    }

    private function partialEvidence(Partial $partial): array
    {
        return $this->evidenceGroups(['contractor' => $partial->Files->values()]);
    }

    private function evidenceGroups(array $groups): array
    {
        $labels = [
            'contractor'                                  => ['Empreiteira', 'ri-building-2-line'],
            WorkReportFlowProduction::STAGE_FISCALIZATION => ['Fiscal', 'ri-shield-check-line'],
            WorkReportFlowProduction::STAGE_PAYMENT       => ['Medição', 'ri-money-dollar-circle-line'],
            WorkReportFlowProduction::STAGE_PUBLICATION   => ['Publicação', 'ri-article-line'],
            'other'                                       => ['Outras', 'ri-folder-line'],
        ];

        $out = [];

        foreach ($labels as $key => [$label, $icon]) {
            $files = $groups[$key] ?? collect();
            $out[] = ['key' => $key, 'label' => $label, 'icon' => $icon, 'files' => $files, 'count' => $files->count()];
        }

        return $out;
    }

    // ------------------------------------------------------------ PEP / ops

    private function pep(Collection $orders, string $type, int $informId, mixed $amount): array
    {
        $peps = $orders->filter(fn (Order $o) => filled($o->pep))
            ->unique('pep')
            ->map(fn (Order $o) => [
                'code'       => trim((string) $o->pep),
                'order'      => $o->ordem,
                'carries'    => str_ends_with(trim((string) $o->pep), '-2'),
                'closed'     => str_starts_with((string) $o->statusSist, 'ENT') || str_starts_with((string) $o->statusSist, 'ENC'),
                'budget'     => $o->Pep?->orcamento,
                'real'       => $o->Pep?->real,
                'commitment' => $o->Pep?->compromisso,
                'available'  => $o->Pep?->disponivel,
                'region'     => $o->Pep?->regiao,
                'synced'     => $o->Pep !== null,
            ])->values();

        $alerts = PepBalanceAlert::query()->with('Handler:id,name')
            ->where('informe_type', $type)->where('informe_id', $informId)
            ->orderByDesc('created_at')->get();

        $shortfall = $this->budgetGuard->shortfall($orders, (float) $amount);

        return ['peps' => $peps, 'alerts' => $alerts, 'shortfall' => $shortfall, 'amount' => $amount];
    }

    private function operations(Collection $orders): array
    {
        return $orders->map(function (Order $order) {
            $ops = $order->Operations->sortBy('operacao')->values()->map(fn (Operation $op) => [
                'code'        => $op->operacao,
                'desc'        => $op->descOperacao,
                'status'      => $op->status,
                'confirmed'   => str_starts_with(strtoupper((string) $op->status), 'CONF'),
                'at'          => $op->fimReal,
                'work_center' => $op->txtCenTrab,
            ]);

            return [
                'order'     => $order->ordem,
                'pep'       => $order->pep,
                'status'    => $order->statusSist,
                'ops'       => $ops,
                'confirmed' => $ops->where('confirmed', true)->count(),
            ];
        })->values()->all();
    }

    private function balanceLabel(Collection $orders): string
    {
        $balances = $this->budgetGuard->balancesFor($orders);

        return $balances === [] ? 'Sem PEP' : 'R$ ' . number_format(array_sum($balances), 2, ',', '.');
    }

    private function balanceTone(Collection $orders, float $amount): string
    {
        $shortfall = $this->budgetGuard->shortfall($orders, $amount);

        return $shortfall ? 'danger' : 'ok';
    }

    private function step(string $label, $at, ?string $who, string $icon, bool $done, ?string $tone = null): array
    {
        return compact('label', 'at', 'who', 'icon', 'done', 'tone');
    }
}
