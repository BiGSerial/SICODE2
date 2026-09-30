<?php

namespace App\Http\Controllers;

use App\Enum\{QualityProcessState, QualityProcessStatus, QualityStageType};
use App\Models\{Company, Note, QualityMember, QualityPoolRule, QualityProcess, QualityRejectionCategory, QualitySetting, Service, User};
use App\Services\Quality\{QualityActivities, QualityBoard, QualityBulkSearch, QualityDesignerResolver, QualityEligibilityService, QualityNoteFilters, QualityQueues, QualityRoles, QualityWorkflowException, QualityWorkflowService};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class QualityController extends Controller
{
    public function __construct(
        private readonly QualityRoles $roles,
        private readonly QualityWorkflowService $workflow,
    ) {
    }

    /** Cada nível tem a sua página inicial: Gestão → visão geral; N1 → Minhas obras; N2 → Decisões. */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->roles->canAccess($user), 403);

        return redirect()->route(match (true) {
            $this->roles->canManage($user) => 'quality.dashboard',
            $this->roles->isN1($user)      => 'quality.n1',
            default                        => 'quality.n2',
        });
    }

    // ------------------------------------------------------------------ Páginas de trabalho por nível

    /** Espaço de trabalho do N1: uma página (aba) por etapa, com busca, filtros, paginação e ações em massa. */
    public function n1(Request $request, QualityBoard $board, QualityBulkSearch $bulk, ?string $tab = null)
    {
        $user = $request->user();
        abort_unless($this->roles->isN1($user), 403);

        $tabs = $board->tabs($user, QualityBoard::N1_TABS);
        $tab  = $tab ?: $board->defaultTab($tabs);
        $request->route()?->setParameter('tab', $tab);

        $scope = 'n1.' . $tab;
        $notes = $bulk->get($scope);
        $query = $board->listQuery($user, QualityBoard::N1_TABS, $tab, $request->all() + ['notes' => $notes]);

        return view('quality.n1.tab', [
            'bulkScope'         => $scope, 'bulkNotes' => $notes,
            'bulkReport'        => $notes ? $bulk->missingReport($notes, $this->foundNotes($query), $user) : [],
            'tabs'              => $tabs, 'tab' => $tab, 'current' => $tabs[$tab],
            'rows'              => $query->paginate(20)->withQueryString(),
            'noteFilterOptions' => app(QualityNoteFilters::class)->options(),
        ] + $board->n1Context($user));
    }

    public function n1Users(Request $request, QualityBoard $board)
    {
        abort_unless($this->roles->isN1($request->user()), 403);

        return view('quality.n1.users', $board->n1Users($request->user()) + Arr::only($board->n1Context($request->user()), ['designersByPhase', 'activityNames']));
    }

    /** Despacho em massa do N1: várias Notas de uma vez para um usuário da empresa. */
    public function n1Dispatch(Request $request)
    {
        $data = $request->validate(['process_ids' => ['required_unless:all_filtered,1', 'array'], 'process_ids.*' => ['integer'], 'all_filtered' => ['nullable', 'boolean'], 'tab' => ['nullable', 'string'], 'filter_query' => ['nullable', 'string'], 'designer_id' => ['nullable', 'uuid'], 'observation' => ['nullable', 'string', 'max:5000']], ['process_ids.required_unless' => 'Marque ao menos uma obra.']);
        abort_unless($this->roles->isN1($request->user()), 403);

        $ids    = $this->bulkIds($request, $data);
        $result = $this->workflow->assignMany($ids, $request->user(), $data['designer_id'] ?? null, $data['observation'] ?? null);

        return $this->massResult($result, 'despachada(s) ao usuário');
    }

    /** Reatribuição em massa: só o N1 troca o usuário de atividades paradas. */
    public function n1Reassign(Request $request)
    {
        $data = $request->validate(['process_ids' => ['required_unless:all_filtered,1', 'array'], 'process_ids.*' => ['integer'], 'all_filtered' => ['nullable', 'boolean'], 'tab' => ['nullable', 'string'], 'filter_query' => ['nullable', 'string'], 'designer_id' => ['required', 'uuid'], 'reason' => ['nullable', 'string', 'max:2000']], ['process_ids.required_unless' => 'Marque ao menos uma atividade.', 'designer_id.required' => 'Escolha o novo usuário.']);
        abort_unless($this->roles->isN1($request->user()), 403);

        $result = ['done' => 0, 'failed' => []];

        foreach ($this->bulkIds($request, $data) as $id) {
            try {
                $this->workflow->reassignDesigner(QualityProcess::findOrFail($id), $request->user(), $data['designer_id'], $data['reason'] ?? null);
                $result['done']++;
            } catch (QualityWorkflowException $e) {
                $result['failed'][$id] = $e->getMessage();
            }
        }

        return $this->massResult($result, 'reatribuída(s)');
    }

    /** Espaço de trabalho do N2: abas por etapa e uma visão por empresa. */
    public function n2(Request $request, QualityBoard $board, QualityBulkSearch $bulk, ?string $tab = null)
    {
        $user = $request->user();
        abort_unless($this->roles->isN2($user), 403);

        $tabs = $board->tabs($user, QualityBoard::N2_TABS);
        $tab  = $tab ?: $board->defaultTab($tabs);
        $request->route()?->setParameter('tab', $tab);

        if ($tab === 'empresas') {
            return view('quality.n2.companies', ['tabs' => $tabs, 'tab' => $tab] + $board->n2($user));
        }

        $scope = 'n2.' . $tab;
        $notes = $bulk->get($scope);
        $query = $board->listQuery($user, QualityBoard::N2_TABS, $tab, $request->all() + ['notes' => $notes]);

        return view('quality.n2.tab', [
            'bulkScope'         => $scope, 'bulkNotes' => $notes,
            'bulkReport'        => $notes ? $bulk->missingReport($notes, $this->foundNotes($query), $user) : [],
            'tabs'              => $tabs, 'tab' => $tab, 'current' => $tabs[$tab],
            'rows'              => $query->paginate(20)->withQueryString(),
            'noteFilterOptions' => app(QualityNoteFilters::class)->options(),
            'companies'         => $this->companiesFor($request),
        ]);
    }

    /** Ids marcados ou, em "selecionar todas do filtro", todas as obras da aba com os filtros atuais (limite de 500). */
    private function bulkIds(Request $request, array $data): array
    {
        if (empty($data['all_filtered'])) {
            return array_values(array_unique(array_map('intval', $data['process_ids'] ?? [])));
        }

        abort_unless(isset(QualityBoard::N1_TABS[$data['tab'] ?? '']), 422);
        parse_str($data['filter_query'] ?? '', $input);
        $input['notes'] = app(QualityBulkSearch::class)->get('n1.' . $data['tab']);

        return app(QualityBoard::class)->listQuery($request->user(), QualityBoard::N1_TABS, $data['tab'], $input)->reorder('quality_processes.id')->limit(500)->pluck('quality_processes.id')->all();
    }

    /** Notas (e pedidos) presentes num resultado de lista de processos. */
    private function foundNotes($query): \Illuminate\Support\Collection
    {
        return (clone $query)->with('Note')->get()->flatMap(fn ($process) => [$process->Note?->note, $process->Note?->numPedido])->filter()->values();
    }

    private function massResult(array $result, string $verb): RedirectResponse
    {
        $back = back();

        if ($result['failed']) {
            $notes = \App\Models\QualityProcess::with('Note')->whereIn('id', array_keys($result['failed']))->get()->keyBy('id');
            $back  = $back->withErrors(['workflow' => collect($result['failed'])->map(fn ($message, $id) => 'Nota ' . ($notes[$id]->Note?->note ?? $id) . ': ' . $message)->values()->all()]);
        }

        return $result['done'] ? $back->with('success', "{$result['done']} obra(s) {$verb}.") : $back;
    }

    /** Quem é Gestão e também N1/N2 alterna o modo de visão da Qualidade. */
    public function viewAs(Request $request): RedirectResponse
    {
        $data = $request->validate(['mode' => ['required', 'in:manager,member']]);
        abort_unless($this->roles->isDualMode($request->user()), 403);
        session(['quality.view_as' => $data['mode']]);

        return redirect()->route('quality.dashboard');
    }

    /** "Buscar em massa": guarda (ou limpa) a lista de Notas coladas para o escopo da tela. */
    public function bulkSearch(Request $request, QualityBulkSearch $bulk): RedirectResponse
    {
        $data  = $request->validate(['scope' => ['required', 'regex:/^(pool|n1\.(despachar|com-usuarios|analisar|devolvidos|no-n2)|n2\.(decidir|encerrar|com-n1))$/'], 'notes' => ['nullable', 'string', 'max:200000']]);
        $user  = $request->user();
        $scope = $data['scope'];

        abort_unless(match (true) {
            $scope === 'pool'              => $this->roles->canDispatch($user),
            str_starts_with($scope, 'n1.') => $this->roles->isN1($user),
            default                        => $this->roles->isN2($user),
        }, 403);

        $notes = $request->boolean('clear') ? [] : $bulk->parse($data['notes'] ?? '');
        $bulk->put($scope, $notes);

        return back()->with('success', $notes ? count($notes) . ' Nota(s) na busca em massa.' : 'Busca em massa limpa.');
    }

    // ------------------------------------------------------------------ Consultas

    public function dashboard(Request $request, QualityQueues $queues, QualityEligibilityService $eligibility)
    {
        if (!$this->roles->canManage($request->user())) {
            return redirect()->route('quality.index');
        }

        $user    = $request->user();
        $visible = QualityProcess::query()->visibleTo($user)
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('phase'), fn ($q) => $q->where('phase', $request->phase))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('dispatched_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('dispatched_at', '<=', $request->to));

        $active = fn (array $states, ?QualityStageType $phase = null) => (clone $visible)->active()
            ->whereIn('state', array_map(fn ($s) => $s->value, $states))
            ->when($phase, fn ($q) => $q->where('phase', $phase->value))->count();

        $counts = [
            'pool'          => $this->roles->canDispatch($user) ? $eligibility->query()->count() : null,
            'n1_dispatch'   => $active([QualityProcessState::AWAITING_N1_DISPATCH, QualityProcessState::N2_RETURNED]),
            'n1_review'     => $active([QualityProcessState::AWAITING_N1_REVIEW]),
            'designer'      => $active([QualityProcessState::AWAITING_DESIGNER]),
            'n2_review'     => !$this->roles->isN2($user) ? null : $active([QualityProcessState::AWAITING_N2_REVIEW, QualityProcessState::CLOSING, QualityProcessState::SAP_FAILED]),
            'pending_close' => !$this->roles->isN2($user) ? null : $active([QualityProcessState::SAP_FAILED]),
            'phase_project' => (clone $visible)->active()->where('phase', QualityStageType::PROJECT->value)->count(),
            'phase_budget'  => (clone $visible)->active()->where('phase', QualityStageType::BUDGET->value)->count(),
            'rejected'      => (clone $visible)->whereHas('Rejections')->count(),
            'completed'     => (clone $visible)->where('status', QualityProcessStatus::COMPLETED->value)->count(),
        ];

        $completed = (clone $visible)->whereNotNull('completed_at')->get(['company_id', 'dispatched_at', 'completed_at']);
        $metrics   = [
            'average_hours'     => $completed->isEmpty() ? null : round($completed->avg(fn ($item) => $item->dispatched_at->diffInMinutes($item->completed_at)) / 60, 1),
            'volume_by_company' => $completed->groupBy('company_id')->map->count(),
        ];
        $with   = ['Note', 'Company', 'CurrentDesigner', 'N1User', 'Rejections'];
        $recent = (clone $visible)->with($with)->latest('updated_at')->limit(8)->get();
        // obras paradas há mais tempo (atenção da Gestão): ativas, mais antigas no estado atual
        $stalled      = (clone $visible)->active()->with($with)->where('state_changed_at', '<', now()->subDays(2))->orderBy('state_changed_at')->limit(8)->get();
        $stalledTotal = (clone $visible)->active()->where('state_changed_at', '<', now()->subDays(5))->count();
        $byCompany    = (clone $visible)->active()->get(['company_id', 'state', 'state_changed_at'])->groupBy('company_id')->map(fn ($group) => [
            'total'     => $group->count(),
            'withUsers' => $group->where('state', QualityProcessState::AWAITING_DESIGNER)->count(),
            'atN1'      => $group->whereIn('state', [QualityProcessState::AWAITING_N1_DISPATCH, QualityProcessState::AWAITING_N1_REVIEW, QualityProcessState::N2_RETURNED])->count(),
            'atN2'      => $group->whereIn('state', [QualityProcessState::AWAITING_N2_REVIEW, QualityProcessState::CLOSING, QualityProcessState::SAP_FAILED])->count(),
            'late'      => $group->filter(fn ($p) => $p->state_changed_at && $p->state_changed_at->lt(now()->subDays(5)))->count(),
        ]);

        return view('quality.dashboard', [
            'stalled'   => $stalled, 'stalledTotal' => $stalledTotal, 'byCompany' => $byCompany,
            'counts'    => $counts, 'metrics' => $metrics, 'recent' => $recent,
            'companies' => $this->companiesFor($request), 'queues' => $queues->counts($user), 'phases' => QualityStageType::cases(),
        ]);
    }

    public function pool(Request $request, QualityEligibilityService $eligibility, QualityActivities $activities, QualityNoteFilters $filters, QualityBulkSearch $bulk)
    {
        $bulkNotes = $bulk->get('pool');
        $query     = $filters->apply($eligibility->query($request->only(['note']) + ['notes' => $bulkNotes]), $request->all());
        $report    = $bulkNotes ? $bulk->missingReport($bulkNotes, (clone $query)->get(['note', 'numPedido'])->flatMap(fn ($n) => [$n->note, $n->numPedido]), null, true) : [];

        return view('quality.pool', [
            'bulkNotes'         => $bulkNotes, 'bulkReport' => $report,
            'pool'              => $query->paginate(20)->withQueryString(),
            'noteFilterOptions' => $filters->options(),
            'companies'         => Company::query()->whereIn('id', QualityMember::query()->where('role', QualityMember::N1)->where('active', true)->pluck('company_id'))->orderBy('name')->get(),
            'rules'             => $eligibility->rules(),
            'fixedRules'        => QualityEligibilityService::FIXED_RULES,
            'missingActivities' => $activities->missing(),
        ]);
    }

    public function queue(Request $request, QualityQueues $queues, QualityNoteFilters $filters)
    {
        $user               = $request->user();
        $query              = QualityProcess::query()->visibleTo($user)->with(['Note', 'Company', 'CurrentDesigner', 'N1User']);
        [$key, $definition] = $queues->apply($query, $user, $request->get('fila'));

        $query->when($request->filled('note'), fn ($q) => $q->whereHas('Note', fn ($n) => $n->where('note', 'like', '%' . $request->note . '%')->orWhere('numPedido', 'like', '%' . $request->note . '%')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($filters->active($request->all()), fn ($q) => $q->whereHas('Note', fn ($n) => $filters->apply($n, $request->all())));

        return view('quality.queue', [
            'noteFilterOptions' => $filters->options(),
            'processes'         => $query->latest('updated_at')->paginate(25)->withQueryString(),
            'key'               => $key, 'definition' => $definition, 'queues' => $queues->counts($user), 'definitions' => $queues->definitions($user),
            'companies'         => $this->companiesFor($request),
        ]);
    }

    public function history(Request $request, QualityNoteFilters $filters)
    {
        $processes = QualityProcess::query()->visibleTo($request->user())->with(['Note', 'Company', 'CurrentDesigner', 'N1User'])
            ->when($request->filled('note'), fn ($q) => $q->whereHas('Note', fn ($n) => $n->where('note', 'like', '%' . $request->note . '%')->orWhere('numPedido', 'like', '%' . $request->note . '%')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('phase'), fn ($q) => $q->where('phase', $request->phase))
            ->when($filters->active($request->all()), fn ($q) => $q->whereHas('Note', fn ($n) => $filters->apply($n, $request->all())))
            ->latest('updated_at')->paginate(25)->withQueryString();

        return view('quality.history', ['processes' => $processes, 'companies' => $this->companiesFor($request), 'noteFilterOptions' => $filters->options()]);
    }

    public function show(Request $request, QualityProcess $qualityProcess, QualityDesignerResolver $designers)
    {
        $this->authorize('view', $qualityProcess);
        $user    = $request->user();
        $process = $qualityProcess->load(['Note', 'Production.Service', 'Company', 'OriginalDesigner', 'CurrentDesigner', 'N1User', 'DispatchedBy', 'CompletedBy', 'Stages.Files.File', 'Stages.AssignedUser', 'Stages.DispatchedBy', 'Stages.ExecutedBy', 'Stages.ApprovedBy', 'Rejections.Items.Category', 'Rejections.Items.Subcategory', 'Rejections.Author', 'Rejections.Designer', 'Events.Actor', 'Events.Target']);

        $isActive = $process->status === QualityProcessStatus::ACTIVE;
        $state    = $process->state;
        $can      = [
            'reassign' => $isActive && $state === QualityProcessState::AWAITING_DESIGNER && $this->roles->canActAsN1($user, $process),
            'assign'   => $isActive && in_array($state, [QualityProcessState::AWAITING_N1_DISPATCH, QualityProcessState::N2_RETURNED], true) && $this->roles->canActAsN1($user, $process),
            'contest'  => $isActive && $state === QualityProcessState::N2_RETURNED && $this->roles->canActAsN1($user, $process),
            'review1'  => $isActive && $state === QualityProcessState::AWAITING_N1_REVIEW && $this->roles->canActAsN1($user, $process),
            'review2'  => $isActive && $state === QualityProcessState::AWAITING_N2_REVIEW && $this->roles->canActAsN2($user, $process),
            'retry'    => $isActive && in_array($state, [QualityProcessState::SAP_FAILED, QualityProcessState::CLOSING], true) && $this->roles->canActAsN2($user, $process),
            'comment'  => $isActive,
        ];

        $categories = QualityRejectionCategory::query()->parents()->where('active', true)->forType($process->phase->value)
            ->with(['Children' => fn ($q) => $q->where('active', true)->forType($process->phase->value)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('quality.show', [
            'process'       => $process, 'can' => $can, 'categories' => $categories,
            'activityNames' => collect(QualityStageType::cases())->mapWithKeys(fn ($phase) => [$phase->value => app(QualityActivities::class)->serviceFor($phase)?->service])->all(),
            'discussion'    => $process->Events->where('type', \App\Enum\QualityEventType::COMMENT_ADDED)->values(),
            'designers'     => ($can['assign'] || $can['reassign']) ? $designers->eligible($process) : collect(),
            'lastRejection' => $process->Rejections->first(),
            'currentStage'  => $process->Stages->whereIn('status.value', ['PENDING', 'IN_PROGRESS'])->last(),
        ]);
    }

    // ------------------------------------------------------------------ Ações do fluxo

    public function dispatch(Request $request, QualityEligibilityService $eligibility, QualityNoteFilters $filters, QualityBulkSearch $bulk)
    {
        $data = $request->validate([
            'note_ids'   => ['required_unless:all_filtered,1', 'array'], 'note_ids.*' => ['integer'], 'all_filtered' => ['nullable', 'boolean'], 'filter_query' => ['nullable', 'string'],
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
        ], ['note_ids.required_unless' => 'Marque ao menos uma Nota.'] + $this->messages());

        if (!empty($data['all_filtered'])) {
            // "selecionar todas do filtro": refaz a consulta do Pool com os mesmos filtros e a busca em massa da sessão
            parse_str($data['filter_query'] ?? '', $input);
            $ids = $filters->apply($eligibility->query(['note' => $input['note'] ?? null, 'notes' => $bulk->get('pool')]), $input)->reorder('notes.id')->limit(QualityBulkSearch::LIMIT)->pluck('notes.id')->all();
        } else {
            $ids = $data['note_ids'];
        }

        return $this->run(fn () => $this->workflow->dispatch($ids, Company::findOrFail($data['company_id']), $request->user()), fn ($processes) => count($processes) . ' Nota(s) despachada(s) ao N1 da empresa.');
    }

    public function assign(Request $request, QualityProcess $qualityProcess)
    {
        $this->authorize('actAsN1', $qualityProcess);
        $data = $request->validate(['designer_id' => ['required', 'uuid'], 'observation' => ['nullable', 'string', 'max:5000']], $this->messages());

        return $this->run(fn () => $this->workflow->assignDesigner($qualityProcess, $request->user(), $data['designer_id'], $data['observation'] ?? null), 'Atividade despachada ao usuário.');
    }

    public function approve(Request $request, QualityProcess $qualityProcess)
    {
        $data = $request->validate(['observation' => ['nullable', 'string', 'max:5000']], $this->messages());

        return $this->run(fn () => $this->workflow->approve($qualityProcess, $request->user(), $data['observation'] ?? null), 'Aprovação registrada.');
    }

    public function reject(Request $request, QualityProcess $qualityProcess)
    {
        $data = $request->validate([
            'reasons'                  => ['required', 'array', 'min:1'],
            'reasons.*.category_id'    => ['required', 'integer', 'exists:quality_rejection_categories,id'],
            'reasons.*.subcategory_id' => ['nullable', 'integer', 'exists:quality_rejection_categories,id'],
            'reasons.*.observation'    => ['nullable', 'string', 'max:2000'],
            'observation'              => ['nullable', 'string', 'max:5000'],
        ], $this->messages());

        return $this->run(fn () => $this->workflow->reject($qualityProcess, $request->user(), array_values($data['reasons'] ?? []), $data['observation'] ?? null), 'Rejeição registrada e processo devolvido.');
    }

    public function contest(Request $request, QualityProcess $qualityProcess)
    {
        $data = $request->validate(['justification' => ['required', 'string', 'max:5000']], ['justification.required' => 'Explique por que a devolução está sendo questionada.']);

        return $this->run(fn () => $this->workflow->contest($qualityProcess, $request->user(), $data['justification']), 'Devolução questionada: o N2 vai reanalisar.');
    }

    public function comment(Request $request, QualityProcess $qualityProcess)
    {
        $this->authorize('comment', $qualityProcess);
        $data = $request->validate(['comment' => ['required', 'string', 'max:5000'], 'visibility' => ['nullable', 'in:INTERNAL,ALL']], $this->messages());

        return $this->run(fn () => $this->workflow->addComment($qualityProcess, $request->user(), $data['comment'], $data['visibility'] ?? 'INTERNAL'), 'Comentário registrado.');
    }

    public function retryClosing(Request $request, QualityProcess $qualityProcess)
    {
        return $this->run(fn () => $this->workflow->retryClosing($qualityProcess, $request->user()), 'Encerramento concluído.');
    }

    // ------------------------------------------------------------------ Motivos de rejeição

    public function categories(Request $request)
    {
        $categories = QualityRejectionCategory::query()->parents()->with(['Children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhereHas('Children', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->when($request->filled('applies'), fn ($q) => $request->applies === 'PROJECT' ? $q->where('applies_project', true) : $q->where('applies_budget', true))
            ->when($request->get('active') !== null && $request->get('active') !== '', fn ($q) => $q->where('active', (bool) $request->active))
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('quality.categories', ['categories' => $categories, 'parents' => QualityRejectionCategory::query()->parents()->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $data = $this->validateCategory($request);
        QualityRejectionCategory::create($data);

        return back()->with('success', 'Motivo cadastrado.');
    }

    public function updateCategory(Request $request, QualityRejectionCategory $category)
    {
        $data = $this->validateCategory($request, $category);
        $category->update($data);

        return back()->with('success', 'Motivo atualizado.');
    }

    public function toggleCategory(QualityRejectionCategory $category)
    {
        $category->update(['active' => !$category->active]);

        return back()->with('success', $category->active ? 'Motivo ativado.' : 'Motivo desativado.');
    }

    // ------------------------------------------------------------------ Configurações

    public function settings(QualityEligibilityService $eligibility, QualityActivities $activities)
    {
        return view('quality.settings', [
            'rules'      => $eligibility->rules(),
            'fixedRules' => QualityEligibilityService::FIXED_RULES,
            'columns'    => (new Note())->getFillable(),
            'conditions' => \App\Http\Livewire\Config\Services\Addstatus::CONDITIONS,
            'services'   => Service::query()->orderBy('service')->get(),
            'activities' => $activities->map(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'activity_project' => ['required', 'exists:services,uuid'],
            'activity_budget'  => ['required', 'exists:services,uuid', 'different:activity_project'],
        ], $this->messages());

        QualitySetting::updateOrCreate(['key' => QualityActivities::KEY], ['value' => ['PROJECT' => $data['activity_project'], 'BUDGET' => $data['activity_budget']], 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Configurações salvas.');
    }

    public function storeRule(Request $request)
    {
        $columns    = (new Note())->getFillable();
        $conditions = array_keys(\App\Http\Livewire\Config\Services\Addstatus::CONDITIONS);
        $data       = $request->validate([
            'column_search'  => ['required', Rule::in($columns)], 'condition' => ['required', Rule::in($conditions)], 'value' => ['required', 'string', 'max:500'],
            'column_search2' => ['nullable', Rule::in($columns)], 'condition2' => ['nullable', Rule::in($conditions)], 'value2' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        foreach (['value', 'value2'] as $key) {
            $condition = $data[$key === 'value' ? 'condition' : 'condition2'] ?? null;

            if (!empty($data[$key]) && in_array($condition, ['Em', 'NaoEstaEm'], true)) {
                $data[$key] = json_encode(array_values(array_filter(array_map('trim', explode(',', $data[$key])), 'strlen')));
            }
        }

        if (empty($data['column_search2']) || empty($data['condition2']) || empty($data['value2'])) {
            $data['column_search2'] = $data['condition2'] = $data['value2'] = null;
        }

        QualityPoolRule::create($data + ['exclusion' => $request->boolean('exclusion'), 'exclusion2' => $request->boolean('exclusion2'), 'created_by' => $request->user()->id]);

        return back()->with('success', 'Critério do Pool adicionado.');
    }

    public function destroyRule(QualityPoolRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Critério removido.');
    }

    // ------------------------------------------------------------------ Equipe (N1 / N2 por empresa)

    public function team(Request $request)
    {
        $companyId = $request->get('company_id');
        $members   = QualityMember::query()->with(['User', 'Company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('company_id')->orderBy('role')->get()->sortBy(fn ($m) => ($m->Company?->name ?? '') . $m->role . ($m->User?->name ?? ''));

        return view('quality.team', [
            'members'    => $members,
            'companies'  => Company::query()->orderBy('name')->get(),
            'companyId'  => $companyId,
            'candidates' => $companyId ? User::query()->where('company_id', $companyId)->orderBy('name')->get() : collect(),
        ]);
    }

    public function storeMember(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'uuid', 'exists:users,id'], 'company_id' => ['required', 'uuid', 'exists:companies,id'], 'role' => ['required', 'in:N1,N2']], $this->messages());
        QualityMember::updateOrCreate(['user_id' => $data['user_id'], 'company_id' => $data['company_id'], 'role' => $data['role']], ['active' => true, 'created_by' => $request->user()->id]);

        return back()->with('success', 'Usuário cadastrado como ' . $data['role'] . ' da empresa.');
    }

    public function toggleMember(QualityMember $member)
    {
        $member->update(['active' => !$member->active]);

        return back()->with('success', $member->active ? 'Vínculo reativado.' : 'Vínculo desativado.');
    }

    public function destroyMember(QualityMember $member)
    {
        $member->delete();

        return back()->with('success', 'Vínculo removido.');
    }

    // ------------------------------------------------------------------ Infra

    private function run(callable $action, string|callable $success): RedirectResponse
    {
        try {
            $result = $action();
        } catch (QualityWorkflowException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', is_callable($success) ? $success($result) : $success);
    }

    private function validateCategory(Request $request, ?QualityRejectionCategory $category = null): array
    {
        $data = $request->validate([
            'parent_id'   => ['nullable', 'integer', 'exists:quality_rejection_categories,id', Rule::notIn([$category?->id])],
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'code'        => ['nullable', 'string', 'max:60', Rule::unique('quality_rejection_categories', 'code')->ignore($category?->id)],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ], $this->messages());

        if (!empty($data['parent_id']) && QualityRejectionCategory::whereKey($data['parent_id'])->whereNotNull('parent_id')->exists()) {
            abort(422, 'Subcategoria não pode ter subcategoria.');
        }

        if ($category && $category->Children()->exists() && !empty($data['parent_id'])) {
            abort(422, 'Motivo com subcategorias não pode virar subcategoria.');
        }

        $appliesProject = $request->boolean('applies_project');
        $appliesBudget  = $request->boolean('applies_budget');

        if (!$appliesProject && !$appliesBudget) {
            abort(422, 'Escolha ao menos umo ciclo de aplicação.');
        }

        return $data + ['active' => $request->boolean('active', true), 'applies_project' => $appliesProject, 'applies_budget' => $appliesBudget, 'sort_order' => (int) ($data['sort_order'] ?? 0)];
    }

    private function companiesFor(Request $request)
    {
        $user = $request->user();

        return Company::query()->when(!$this->roles->seesAllCompanies($user), fn ($q) => $q->whereIn('id', $this->roles->companyIds($user)))->orderBy('name')->get();
    }

    private function messages(): array
    {
        return [
            'required'         => 'Preencha o campo :attribute.', 'exists' => 'Valor inválido em :attribute.', 'max' => 'O campo :attribute excede o tamanho permitido.',
            'reasons.required' => 'Informe a categoria do motivo.', 'reasons.min' => 'Informe a categoria do motivo.', 'reasons.*.category_id.required' => 'Selecione a categoria do motivo.', 'unique' => 'Já existe um motivo com este código.',
        ];
    }
}
