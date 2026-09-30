<?php

namespace App\Services\Quality;

use App\Enum\{QualityProcessState as S, QualityStageKind, QualityStageStatus, QualityStageType};
use App\Models\{Company, Note, QualityMember, QualityProcess, QualityStage, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Dados das páginas de trabalho do N1 e do N2 (o que fazer agora, com quem está, parado há quanto tempo). */
class QualityBoard
{
    public function __construct(private readonly QualityRoles $roles, private readonly QualityDesignerResolver $designers)
    {
    }

    private function active(User $user): Builder
    {
        return QualityProcess::query()->visibleTo($user)->active()->with(['Note', 'Company', 'CurrentDesigner', 'N1User']);
    }

    private function inState(User $user, array $states): Collection
    {
        return $this->active($user)->whereIn('state', array_map(fn (S $s) => $s->value, $states))->orderBy('state_changed_at')->get();
    }

    public const N1_TABS = [
        'despachar'    => ['step' => 1, 'label' => 'Despachar',         'states' => [S::AWAITING_N1_DISPATCH], 'action' => true,  'help' => 'Obras enviadas pela Gestão, ainda sem usuário. Escolha o usuário e despache: a atividade é criada na pilha dele.'],
        'com-usuarios' => ['step' => 2, 'label' => 'Com os usuários',   'states' => [S::AWAITING_DESIGNER],   'action' => false, 'help' => 'Despachadas e ainda não finalizadas. Acompanhe o tempo e reatribua as que pararam.'],
        'analisar'     => ['step' => 3, 'label' => 'Analisar',           'states' => [S::AWAITING_N1_REVIEW],  'action' => true,  'help' => 'O usuário finalizou. Aprove (segue ao N2) ou rejeite (volta ao usuário) informando categoria e subcategoria.'],
        'devolvidos'   => ['step' => 4, 'label' => 'Devolvidos pelo N2', 'states' => [S::N2_RETURNED],         'action' => true,  'help' => 'O N2 devolveu. Reencaminhe ao mesmo usuário, troque-o ou questione o N2.'],
        'no-n2'        => ['step' => 5, 'label' => 'No N2',              'states' => [S::AWAITING_N2_REVIEW, S::CLOSING, S::SAP_FAILED], 'action' => false, 'help' => 'Aprovadas por você, aguardando a decisão do N2. Se estiverem paradas, combine com o N2 na aba Discussão da obra.'],
    ];

    public const N2_TABS = [
        'decidir'  => ['step' => 1, 'label' => 'Decidir',              'states' => [S::AWAITING_N2_REVIEW],     'action' => true,  'help' => 'Aprovadas pelo N1. Aprove (1º ciclo abre o 2º; 2º ciclo conclui) ou devolva ao N1 com categoria e subcategoria.'],
        'encerrar' => ['step' => 2, 'label' => 'Encerramento pendente', 'states' => [S::CLOSING, S::SAP_FAILED], 'action' => true,  'help' => 'A aprovação final ficou sem concluir o encerramento. Abra a obra e conclua o encerramento.'],
        'com-n1'   => ['step' => 3, 'label' => 'Devolvidos, com o N1', 'states' => [S::N2_RETURNED],            'action' => false, 'help' => 'Você devolveu; o N1 precisa reencaminhar ou questionar. Se estiver parado, cobre o N1 na aba Discussão.'],
    ];

    /** @return array<int, string> */
    private function values(array $states): array
    {
        return array_map(fn (S $s) => $s->value, $states);
    }

    /**
     * Abas do espaço de trabalho: contagem e "mais antiga" de cada etapa, numa única consulta.
     *
     * @return array<string, array<string, mixed>>
     */
    public function tabs(User $user, array $definitions): array
    {
        $stats = $this->active($user)->getQuery()->reorder()->selectRaw('state, COUNT(*) as total, MIN(state_changed_at) as oldest')->groupBy('state')->get()->keyBy('state');

        return collect($definitions)->map(function (array $tab, string $key) use ($stats) {
            $rows   = collect($this->values($tab['states']))->map(fn ($state) => $stats->get($state))->filter();
            $oldest = $rows->pluck('oldest')->filter()->min();

            return $tab + ['key' => $key, 'count' => (int) $rows->sum('total'), 'oldest' => $oldest ? \Illuminate\Support\Carbon::parse($oldest) : null];
        })->all();
    }

    /** Contagens leves para o menu da Gestão. @return array<string, int> */
    public function managementCounts(User $user): array
    {
        $base = $this->active($user)->getQuery()->reorder();

        return [
            'andamento' => (clone $base)->count(),
            'n2'        => (clone $base)->whereIn('state', $this->values([S::AWAITING_N2_REVIEW, S::CLOSING, S::SAP_FAILED]))->count(),
            'paradas'   => (clone $base)->where('state_changed_at', '<', now()->subDays(5))->count(),
        ];
    }

    /** Lista de uma aba, com busca, empresa, usuário, filtros da Nota e ordenação. */
    public function listQuery(User $user, array $definitions, string $tab, array $input): Builder
    {
        $query = $this->active($user)->with(['Rejections' => fn ($r) => $r->with(['Items.Category', 'Items.Subcategory'])->latest('id')->limit(1)])
            ->whereIn('state', $this->values($definitions[$tab]['states']));

        $filters = app(QualityNoteFilters::class);
        $term    = trim((string) ($input['q'] ?? ''));

        $query->when(!empty($input['notes']), fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(fn ($w) => $w->whereIn('note', $input['notes'])->orWhereIn('numPedido', $input['notes']))))
            ->when($term !== '', fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(fn ($w) => $w->where('note', 'like', "%{$term}%")->orWhere('numPedido', 'like', "%{$term}%")->orWhere('material', 'like', "%{$term}%"))))
            ->when(!empty($input['company_id']), fn ($q) => $q->where('company_id', $input['company_id']))
            ->when(!empty($input['designer_id']), fn ($q) => $q->where('current_designer_id', $input['designer_id']))
            ->when(!empty($input['phase']), fn ($q) => $q->where('phase', $input['phase']))
            ->when($filters->active($input), fn ($q) => $q->whereHas('Note', fn ($n) => $filters->apply($n, $input)));

        return match ($input['ordem'] ?? 'antigas') {
            'recentes' => $query->orderByDesc('state_changed_at'),
            'nota'     => $query->orderBy(Note::query()->select('note')->whereColumn('notes.id', 'quality_processes.note_id')->limit(1)),
            default    => $query->orderBy('state_changed_at'),
        };
    }

    /** Aba inicial: a primeira etapa que pede ação e tem itens; senão a primeira com itens; senão a primeira. */
    public function defaultTab(array $tabs): string
    {
        $pick = collect($tabs)->first(fn ($t) => $t['action'] && $t['count'] > 0) ?? collect($tabs)->first(fn ($t) => $t['count'] > 0) ?? collect($tabs)->first();

        return $pick['key'];
    }

    /** @return array<string, mixed> empresas e usuários disponíveis para os filtros e ações em massa do N1. */
    public function n1Context(User $user): array
    {
        $companyIds = $this->roles->isSupport($user) ? Company::query()->pluck('id')->all() : $this->roles->companyIds($user, QualityMember::N1);
        $companies  = Company::query()->whereIn('id', $companyIds)->orderBy('name')->get();

        $byPhase = $companies->mapWithKeys(fn (Company $company) => [$company->id => collect(QualityStageType::cases())->mapWithKeys(fn (QualityStageType $phase) => [$phase->value => $this->designers->eligibleForCompany($company->id, $phase)])]);
        $names   = collect(QualityStageType::cases())->mapWithKeys(fn (QualityStageType $phase) => [$phase->value => app(QualityActivities::class)->serviceFor($phase)?->service]);

        return [
            'companies' => $companies,
            // usuários da empresa HABILITADOS na atividade de cada ciclo (nunca outros usuários)
            'designersByPhase' => $byPhase,
            'activityNames'    => $names->all(),
            // união (apenas para filtrar listas por usuário)
            'designers' => $byPhase->map(fn ($phases) => $phases->flatten(1)->unique('id')->sortBy('name')->values()),
        ];
    }

    /** @return array<string, mixed> */
    public function n1Users(User $user): array
    {
        $companyIds = $this->roles->isSupport($user) ? Company::query()->pluck('id')->all() : $this->roles->companyIds($user, QualityMember::N1);
        $companies  = Company::query()->whereIn('id', $companyIds)->orderBy('name')->get();
        $open       = $this->inState($user, [S::AWAITING_DESIGNER])->groupBy('current_designer_id');

        $executions = QualityStage::query()
            ->where('kind', QualityStageKind::EXECUTION->value)->where('status', QualityStageStatus::COMPLETED->value)
            ->whereHas('Process', fn ($p) => $p->whereIn('company_id', $companyIds))
            ->whereNotNull('dispatched_at')->whereNotNull('completed_at')->get()->groupBy('executed_by');

        $byCompany = $companies->map(function (Company $company) use ($open, $executions) {
            $users = $this->designers->eligibleForCompany($company->id, QualityStageType::PROJECT)
                ->merge($this->designers->eligibleForCompany($company->id, QualityStageType::BUDGET))->unique('id')->values();

            return [
                'company' => $company,
                'users'   => $users->map(function (User $u) use ($open, $executions) {
                    $done = $executions->get($u->id, collect());

                    return [
                        'user'      => $u,
                        'open'      => $open->get($u->id, collect()),
                        'rounds'    => $done->count(),
                        'avg_hours' => $done->isEmpty() ? null : round($done->avg(fn ($st) => $st->dispatched_at->diffInMinutes($st->completed_at)) / 60, 1),
                    ];
                }),
            ];
        });

        return ['byCompany' => $byCompany];
    }

    /** @return array<string, mixed> */
    public function n2(User $user): array
    {
        $decide  = $this->inState($user, [S::AWAITING_N2_REVIEW]);
        $closing = $this->inState($user, [S::CLOSING, S::SAP_FAILED]);
        $waiting = $this->inState($user, [S::N2_RETURNED]);
        $active  = $this->active($user)->get();

        return [
            'decide'    => $decide,
            'closing'   => $closing,
            'waitingN1' => $waiting,
            'byCompany' => $active->groupBy('company_id')->map(fn (Collection $group) => [
                'company'   => $group->first()->Company,
                'total'     => $group->count(),
                'withUsers' => $group->where('state', S::AWAITING_DESIGNER)->count(),
                'atN1'      => $group->whereIn('state', [S::AWAITING_N1_DISPATCH, S::AWAITING_N1_REVIEW, S::N2_RETURNED])->count(),
                'atN2'      => $group->whereIn('state', [S::AWAITING_N2_REVIEW, S::CLOSING, S::SAP_FAILED])->count(),
            ])->sortBy(fn ($row) => $row['company']?->name)->values(),
            'completed' => QualityProcess::query()->visibleTo($user)->where('state', S::COMPLETED->value)->count(),
        ];
    }
}
