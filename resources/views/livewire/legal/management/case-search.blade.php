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
        .case-item { border-bottom: 1px solid #f1f5f9; padding: 1rem 1.25rem; }
        .case-item:last-child { border-bottom: none; }
        .case-item:hover { background: #f8fafc; }
        .case-item.selected { background: #eff6ff; border-left: 4px solid #3b82f6; }
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
            <div class="card-header text-bg-dark fw-bold d-flex justify-content-between align-items-center">
                <span>Jurídico › Busca de Casos</span>
                @if($search || $sourceTypeFilter || $areaFilter || $regionalFilter)
                    <span class="badge bg-secondary">{{ $cases->total() }} resultado(s)</span>
                @endif
            </div>

            @forelse($cases as $case)
                @php
                    $active  = $case->demands->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])->count();
                    $overdue = $case->demands->filter(fn($d) => $d->source_due_at && \Carbon\Carbon::parse($d->source_due_at)->isPast())->count();
                    $inField = $case->demands->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count();
                    $closed  = $case->demands->whereIn('internal_status', ['closed_internal', 'closed_external'])->count();
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
                        <hr class="my-2">
                        <ul class="nav nav-tabs mb-3">
                            @foreach([['demands', 'Demandas Ativas'], ['history', 'Histórico Completo'], ['timeline', 'Timeline'], ['data', 'Dados do Caso']] as [$key, $label])
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
                                @endphp
                                <div class="card border shadow-none mb-2">
                                    <div class="card-body py-2 px-3">
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
                                        <div class="small text-muted mt-1">
                                            Controlador: {{ $demand->controller?->name ?? '—' }}
                                            &bull; Campo: {{ $demand->currentAssignee?->name ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small py-2">Nenhuma demanda ativa neste caso.</div>
                            @endforelse

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
