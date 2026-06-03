<div class="lcs-page">
    <x-show-loading />

    <style>
        .lcs-page {
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        #f6f7fb;
            padding: 1.5rem 0;
        }
        .lcs-header {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .22);
            margin-bottom: 1.5rem;
        }
        .filter-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            padding: .85rem 1rem;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15,23,42,.1);
        }
        .filter-card .form-label { color: #0f172a; font-weight: 700; margin-bottom: .3rem; font-size: .82rem; }
        .filter-card .form-select,
        .filter-card .form-control { color: #0f172a; border-color: #cbd5e1; background: #fff; font-size: .85rem; }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15,23,42,.08);
            overflow: hidden;
        }
        .table-card .card-header {
            padding: .9rem 1rem .85rem 1rem;
            border-bottom: 1px solid #dbe3ee;
            background: #1f3148;
            color: #fff;
            font-size: .96rem;
            letter-spacing: .01em;
        }
        .case-item { border-bottom: 1px solid #f1f5f9; padding: 1rem 1.25rem; }
        .case-item:last-child { border-bottom: none; }
        .case-item:hover { background: #f8fafc; }
        .case-item.selected { background: #eff6ff; border-left: 4px solid #3b82f6; }
        .meta-chip {
            display: inline-block;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            border-radius: 999px;
            padding: .14rem .48rem;
            font-size: .72rem;
            font-weight: 700;
        }
        .demand-card { border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; }
        .demand-card + .demand-card { margin-top: .6rem; }
        .deadline-rail {
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .deadline-fill { height: 100%; border-radius: 999px; }
        .deadline-fill.overdue { background: #ef4444; width: 100%; }
        .deadline-fill.today { background: #f59e0b; width: 100%; }
        .deadline-fill.soon { background: #3b82f6; width: 70%; }
        .deadline-fill.future { background: #16a34a; width: 45%; }
        .deadline-fill.none { background: #94a3b8; width: 25%; }
        .deadline-caption { font-size: .78rem; color: #475569; }
        .section-label {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            font-weight: 700;
        }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="lcs-header">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
                    <div class="opacity-75" style="font-size:.9rem">Busca de Casos</div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-12 col-md-5">
                            <div class="filter-card">
                                <label class="form-label">Pesquisar caso</label>
                                <input type="text" class="form-control"
                                       placeholder="Processo, CNPJ, empresa, parte adversa..."
                                       wire:model.debounce.400ms="search" autofocus />
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="filter-card">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" wire:model="sourceTypeFilter">
                                    <option value="">Todos</option>
                                    <option value="injunction">Liminar</option>
                                    <option value="sentence">Sentença</option>
                                    <option value="subsidy">Subsídio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="filter-card">
                                <label class="form-label">Área</label>
                                <input type="text" class="form-control" placeholder="Filtrar..."
                                       wire:model.debounce.400ms="areaFilter" />
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Regional</label>
                                <input type="text" class="form-control" placeholder="Filtrar..."
                                       wire:model.debounce.400ms="regionalFilter" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RESULTADOS --}}
        <div class="table-card" wire:loading.class="opacity-50">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Jurídico › Busca de Casos</span>
                @if($search || $sourceTypeFilter || $areaFilter || $regionalFilter)
                    <span class="badge bg-secondary">{{ $cases->total() }} resultado(s)</span>
                @endif
            </div>

            @forelse($cases as $case)
                @php
                    $isOpenDemand = fn($d) => !str_contains(mb_strtolower((string)($d->process_status_at_import ?? '')), 'encerrad')
                        && !in_array((string)($d->internal_status instanceof \BackedEnum ? $d->internal_status->value : $d->internal_status), ['cancelled','ignored'], true);
                    $active  = $case->demands->filter($isOpenDemand)->count();
                    $overdue = $case->demands->filter(fn($d) => $isOpenDemand($d) && $d->source_due_at && \Carbon\Carbon::parse($d->source_due_at)->isPast())->count();
                    $inField = $case->demands->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count();
                    $closed  = $case->demands->filter(fn($d) => str_contains(mb_strtolower((string)($d->process_status_at_import ?? '')), 'encerrad'))->count();
                    $isOpen  = $selectedCaseId === $case->id;
                @endphp

                <div class="case-item {{ $isOpen ? 'selected' : '' }}" wire:key="case-{{ $case->id }}">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="fw-bold" style="color:#1e3a5f">
                                    {{ $case->case_number ?? $case->process_number ?? 'S/N' }}
                                </span>
                                @if($active > 0)
                                    <span class="badge bg-primary">{{ $active }} ativas</span>
                                @endif
                                @if($overdue > 0)
                                    <span class="badge bg-danger">{{ $overdue }} vencidas</span>
                                @endif
                            </div>
                            <div class="fw-semibold small">{{ $case->company_name }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-people me-1"></i>
                                <x-legal.adverse-party-names :legal-case="$case" />
                            </div>
                            @if($case->legal_responsible_name || $case->law_firm_name)
                                <div class="small text-muted">
                                    {{ $case->legal_responsible_name }}
                                    @if($case->law_firm_name) — {{ $case->law_firm_name }} @endif
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($case->demands->groupBy('source_type') as $type => $typeDemands)
                                    @php
                                        $tl = match($type instanceof \BackedEnum ? $type->value : $type) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $type };
                                        $tb = match($type instanceof \BackedEnum ? $type->value : $type) { 'injunction' => 'bg-danger text-white', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary text-white' };
                                    @endphp
                                    <span class="badge {{ $tb }}" style="font-size:.76rem">{{ $tl }} ({{ $typeDemands->count() }})</span>
                                @endforeach
                                @if($inField > 0)
                                    <span class="badge bg-secondary" style="font-size:.76rem">{{ $inField }} em campo</span>
                                @endif
                                @if($closed > 0)
                                    <span class="badge bg-light text-muted border" style="font-size:.76rem">{{ $closed }} encerradas</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-end gap-1">
                            <div class="small text-muted">
                                Última atividade: {{ $case->last_seen_at ? \Carbon\Carbon::parse($case->last_seen_at)->format('d/m/Y') : '—' }}
                            </div>
                            <button class="btn btn-sm {{ $isOpen ? 'btn-primary' : 'btn-outline-primary' }}"
                                    wire:click="selectCase({{ $case->id }})">
                                {{ $isOpen ? '▲ Fechar' : 'Ver Caso ▼' }}
                            </button>
                        </div>
                    </div>

                    {{-- Painel expandido --}}
                    @if($isOpen && $selectedCase)
                        @php
                            $allDemands = $selectedCase->demands;
                            $openDemands = $allDemands->filter(fn($d) => !str_contains(mb_strtolower((string)($d->process_status_at_import ?? '')), 'encerrad'));
                            $closedDemands = $allDemands->count() - $openDemands->count();
                            $nearestOpenDue = $openDemands->filter(fn($d) => $d->source_due_at)->sortBy('source_due_at')->first()?->source_due_at;
                            $linkedNotesCount = $selectedCase->notes?->count() ?? 0;
                        @endphp
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3"><div class="meta-chip w-100">Demandas totais: <strong>{{ $allDemands->count() }}</strong></div></div>
                            <div class="col-6 col-md-3"><div class="meta-chip w-100">Abertas: <strong>{{ $openDemands->count() }}</strong></div></div>
                            <div class="col-6 col-md-3"><div class="meta-chip w-100">Encerradas: <strong>{{ $closedDemands }}</strong></div></div>
                            <div class="col-6 col-md-3"><div class="meta-chip w-100">Notes associadas: <strong>{{ $linkedNotesCount }}</strong></div></div>
                            <div class="col-12">
                                <div class="meta-chip w-100">
                                    Próximo prazo aberto:
                                    <strong>{{ $nearestOpenDue ? \Carbon\Carbon::parse($nearestOpenDue)->format('d/m/Y H:i') : 'Sem prazo aberto' }}</strong>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <ul class="nav nav-tabs mb-3">
                            @foreach([['demands', 'Demandas Ativas'], ['notes', 'Notes Associadas'], ['history', 'Histórico Completo'], ['timeline', 'Timeline'], ['data', 'Dados do Caso']] as [$key, $label])
                                <li class="nav-item">
                                    <button class="nav-link {{ $caseTab === $key ? 'active' : '' }}"
                                            wire:click="$set('caseTab', '{{ $key }}')">{{ $label }}</button>
                                </li>
                            @endforeach
                        </ul>

                        @if($caseTab === 'demands')
                            @forelse($selectedCase->demands->whereNotIn('internal_status', ['cancelled', 'ignored']) as $demand)
                                @php
                                    $dt  = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : $demand->source_type;
                                    $dl  = match($dt) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $dt ?? '—' };
                                    $db  = match($dt) { 'injunction' => 'bg-danger text-white', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary text-white' };
                                    $externalStatus = trim((string)($demand->source_status ?? $demand->process_status_at_import ?? ''));
                                    $internalStatusValue = (string)($demand->internal_status instanceof \BackedEnum ? $demand->internal_status->value : $demand->internal_status);
                                    $processClosed = str_contains(mb_strtolower((string)($demand->process_status_at_import ?? '')), 'encerrad');
                                @endphp
                                <div class="demand-card p-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <span class="badge {{ $db }} me-1" style="font-size:.76rem">{{ $dl }}</span>
                                                <span class="fw-semibold small">{{ $demand->source_case_number ?? $demand->source_process_number_masked ?? 'S/N' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <x-legal.due-date-chip :date="$demand->source_due_at" :executedAt="$demand->source_executed_at ?? null" />
                                                <x-legal.status-badge :status="$demand->internal_status" />
                                                <a href="{{ route('legal.demand.detail', $demand->uuid) }}"
                                                   class="btn btn-sm btn-outline-secondary">Ver Detalhes →</a>
                                            </div>
                                        </div>
                                        @php
                                            $due = $demand->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at) : null;
                                            $now = now();
                                            $deadlineClass = 'none';
                                            $deadlineText = 'Sem prazo informado';
                                            if ($due) {
                                                if ($due->isPast()) { $deadlineClass = 'overdue'; $deadlineText = 'Prazo vencido'; }
                                                elseif ($due->isToday()) { $deadlineClass = 'today'; $deadlineText = 'Vence hoje'; }
                                                elseif ($due->lte($now->copy()->addDays(3))) { $deadlineClass = 'soon'; $deadlineText = 'Vence em até 3 dias'; }
                                                elseif ($due->lte($now->copy()->addDays(7))) { $deadlineClass = 'soon'; $deadlineText = 'Vence em até 7 dias'; }
                                                else { $deadlineClass = 'future'; $deadlineText = 'Prazo futuro'; }
                                            }
                                        @endphp
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="section-label">Régua de Prazo</span>
                                                <span class="deadline-caption">{{ $deadlineText }}</span>
                                            </div>
                                            <div class="deadline-rail">
                                                <div class="deadline-fill {{ $deadlineClass }}"></div>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Controlador: {{ $demand->controller?->name ?? '—' }}
                                            &bull; Campo: {{ $demand->currentAssignee?->name ?? '—' }}
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <span class="meta-chip">Status externo: {{ $externalStatus !== '' ? $externalStatus : 'Sem status externo' }}</span>
                                            <span class="meta-chip">Status interno: {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $internalStatusValue)) }}</span>
                                            <span class="meta-chip {{ $processClosed ? 'text-danger border-danger bg-danger-subtle' : 'text-success border-success bg-success-subtle' }}">
                                                {{ $processClosed ? 'Processo encerrado' : 'Processo aberto' }}
                                            </span>
                                        </div>
                                        <div class="row g-2 mt-2 small">
                                            <div class="col-md-6"><span class="section-label d-block">Assunto</span>{{ $demand->source_subject ?: '—' }}</div>
                                            <div class="col-md-6"><span class="section-label d-block">Área solicitante</span>{{ $demand->requesting_area_name ?: '—' }}</div>
                                            <div class="col-md-6"><span class="section-label d-block">Área responsável</span>{{ $demand->responsible_area_name ?: '—' }}</div>
                                            <div class="col-md-6"><span class="section-label d-block">Responsável delegado</span>{{ $demand->delegated_responsible_name ?: '—' }}</div>
                                            <div class="col-md-6"><span class="section-label d-block">Status externo em</span>{{ $demand->source_status_at ? \Carbon\Carbon::parse($demand->source_status_at)->format('d/m/Y H:i') : '—' }}</div>
                                            <div class="col-md-6"><span class="section-label d-block">Decisão origem em</span>{{ $demand->source_decision_at ? \Carbon\Carbon::parse($demand->source_decision_at)->format('d/m/Y H:i') : '—' }}</div>
                                            @if($demand->source_description)
                                                <div class="col-12"><span class="section-label d-block">Descrição</span>{{ \Illuminate\Support\Str::limit($demand->source_description, 320) }}</div>
                                            @endif
                                        </div>
                                </div>
                            @empty
                                <div class="text-muted small py-2">Nenhuma demanda ativa neste caso.</div>
                            @endforelse

                        @elseif($caseTab === 'notes')
                            @if(($selectedCase->notes?->count() ?? 0) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Note</th>
                                                <th>Cliente</th>
                                                <th>Status</th>
                                                <th>Data status</th>
                                                <th>Vinculada em</th>
                                                <th>Contexto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedCase->notes->sortByDesc(fn($n) => $n->pivot?->linked_at) as $note)
                                                <tr>
                                                    <td>{{ $note->id }}</td>
                                                    <td>{{ $note->note ?? '—' }}</td>
                                                    <td>{{ $note->client ?? '—' }}</td>
                                                    <td><span class="badge bg-light text-dark border">{{ $note->nstats ?? $note->status ?? '—' }}</span></td>
                                                    <td>{{ $note->dt_status ? \Carbon\Carbon::parse($note->dt_status)->format('d/m/Y H:i') : '—' }}</td>
                                                    <td>{{ $note->pivot?->linked_at ? \Carbon\Carbon::parse($note->pivot->linked_at)->format('d/m/Y H:i') : '—' }}</td>
                                                    <td>{{ $note->pivot?->context ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted small py-2">Nenhuma note associada a este caso.</div>
                            @endif

                        @elseif($caseTab === 'history')
                            @forelse($selectedCase->demands as $demand)
                                @php
                                    $dt = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : $demand->source_type;
                                    $db = match($dt) { 'injunction' => 'bg-danger text-white', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary text-white' };
                                    $dl = match($dt) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $dt ?? '—' };
                                @endphp
                                <div class="mb-2 border rounded p-2 small">
                                    <span class="badge {{ $db }} me-1" style="font-size:.76rem">{{ $dl }}</span>
                                    <x-legal.status-badge :status="$demand->internal_status" />
                                    <span class="ms-1">{{ $demand->source_case_number ?? $demand->source_process_number_masked ?? 'S/N' }}</span>
                                    <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="btn btn-link btn-sm p-0 ms-2">Ver</a>
                                </div>
                            @empty
                                <div class="text-muted small py-2">Sem histórico.</div>
                            @endforelse

                        @elseif($caseTab === 'timeline')
                            @php $allEvents = $selectedCase->demands->flatMap(fn($d) => $d->events)->sortByDesc('occurred_at'); @endphp
                            <x-legal.demand-timeline :events="$allEvents" />

                        @elseif($caseTab === 'data')
                            <dl class="row small">
                                <dt class="col-sm-4 text-muted">Nº Caso</dt>
                                <dd class="col-sm-8">{{ $selectedCase->case_number ?? '—' }}</dd>
                                <dt class="col-sm-4 text-muted">Nº Processo</dt>
                                <dd class="col-sm-8">{{ $selectedCase->process_number ?? '—' }}</dd>
                                <dt class="col-sm-4 text-muted">Empresa</dt>
                                <dd class="col-sm-8">{{ $selectedCase->company_name ?? '—' }}</dd>
                                <dt class="col-sm-4 text-muted">Partes adversas</dt>
                                <dd class="col-sm-8"><x-legal.adverse-party-names :legal-case="$selectedCase" :limit="10" /></dd>
                                <dt class="col-sm-4 text-muted">Responsável Jurídico</dt>
                                <dd class="col-sm-8">{{ $selectedCase->legal_responsible_name ?? '—' }}</dd>
                                <dt class="col-sm-4 text-muted">Escritório</dt>
                                <dd class="col-sm-8">{{ $selectedCase->law_firm_name ?? '—' }}</dd>
                                <dt class="col-sm-4 text-muted">Primeira aparição</dt>
                                <dd class="col-sm-8">{{ $selectedCase->first_seen_at ? \Carbon\Carbon::parse($selectedCase->first_seen_at)->format('d/m/Y') : '—' }}</dd>
                                <dt class="col-sm-4 text-muted">Última atualização</dt>
                                <dd class="col-sm-8">{{ $selectedCase->last_seen_at ? \Carbon\Carbon::parse($selectedCase->last_seen_at)->format('d/m/Y') : '—' }}</dd>
                                @if($selectedCase->identity_confidence)
                                    <dt class="col-sm-4 text-muted">Confiança de vínculo</dt>
                                    <dd class="col-sm-8">
                                        {{ $selectedCase->identity_confidence }}%
                                        @if($selectedCase->identity_confidence >= 80)
                                            <span class="text-success small">Alta confiança</span>
                                        @else
                                            <span class="text-warning small">⚠ Baixa — revisão recomendada</span>
                                        @endif
                                    </dd>
                                @endif
                            </dl>
                            @if($selectedCase->needs_identity_review ?? false)
                                <div class="alert alert-warning small">
                                    ⚠ Este caso possui demandas com vinculação incerta (confidence &lt; 80%). Recomenda-se revisão manual.
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-2 d-block mb-2 opacity-40"></i>
                    @if($search || $sourceTypeFilter || $areaFilter || $regionalFilter)
                        Nenhum caso encontrado para os filtros aplicados.
                    @else
                        Digite acima para pesquisar casos jurídicos.
                    @endif
                </div>
            @endforelse

            <div class="card-body py-2">
                @if($cases->hasPages())
                    {{ $cases->links() }}
                @endif
            </div>
        </div>

    </div>
</div>
