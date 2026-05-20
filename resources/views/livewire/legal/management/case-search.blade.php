<div>
    <h4 class="fw-bold mb-4" style="color: var(--legal-primary)">
        <i class="bi bi-search me-2"></i>Busca de Casos
    </h4>

    {{-- Busca Principal --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-lg-6">
                    <input type="text" class="form-control form-control-lg"
                           placeholder="Pesquisar processo, empresa, parte adversa, nº caso..."
                           wire:model.debounce.400ms="search" autofocus />
                </div>
                <div class="col-6 col-lg-2">
                    <select class="form-select" wire:model="sourceTypeFilter">
                        <option value="">Tipo</option>
                        <option value="injunction">Liminar</option>
                        <option value="sentence">Sentença</option>
                        <option value="subsidy">Subsídio</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <input type="text" class="form-control" placeholder="Área..." wire:model.debounce.400ms="areaFilter" />
                </div>
                <div class="col-6 col-lg-2">
                    <input type="text" class="form-control" placeholder="Regional..." wire:model.debounce.400ms="regionalFilter" />
                </div>
            </div>
        </div>
    </div>

    {{-- Resultados --}}
    <div wire:loading.class="opacity-50">
        @forelse($cases as $case)
            @php
                $active   = $case->demands->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])->count();
                $overdue  = $case->demands->filter(fn($d) => $d->source_due_at && \Carbon\Carbon::parse($d->source_due_at)->isPast())->count();
                $inField  = $case->demands->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count();
                $closed   = $case->demands->whereIn('internal_status', ['closed_internal', 'closed_external'])->count();
                $isOpen   = $selectedCaseId === $case->id;
            @endphp

            <div class="card border-0 shadow-sm mb-3 {{ $isOpen ? 'border-primary border' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">{{ $case->case_number ?? $case->process_number ?? 'S/N' }}</span>
                                @if($active > 0)
                                    <span class="badge bg-primary">{{ $active }} ativas</span>
                                @endif
                                @if($overdue > 0)
                                    <span class="badge bg-danger">{{ $overdue }} vencidas</span>
                                @endif
                            </div>
                            <div class="fw-semibold">{{ $case->company_name }}</div>
                            @if($case->legal_responsible_name || $case->law_firm_name)
                                <div class="small text-muted">
                                    {{ $case->legal_responsible_name }}
                                    @if($case->law_firm_name) — {{ $case->law_firm_name }} @endif
                                </div>
                            @endif

                            {{-- Demandas resumidas --}}
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($case->demands->groupBy('source_type') as $type => $typeDemands)
                                    <span class="badge bg-secondary">
                                        {{ $type }} ({{ $typeDemands->count() }})
                                    </span>
                                @endforeach
                                @if($overdue > 0)
                                    <span class="small text-danger">🔴 {{ $overdue }} vencida(s)</span>
                                @endif
                                @if($inField > 0)
                                    <span class="small text-warning">🟡 {{ $inField }} em campo</span>
                                @endif
                                @if($closed > 0)
                                    <span class="small text-success">🟢 {{ $closed }} encerrada(s)</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-end gap-2">
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
                        <hr>
                        <ul class="nav nav-tabs mb-3">
                            @foreach([['demands', 'Demandas Ativas'], ['history', 'Histórico Completo'], ['timeline', 'Timeline Consolidada'], ['data', 'Dados do Caso']] as [$key, $label])
                                <li class="nav-item">
                                    <button class="nav-link {{ $caseTab === $key ? 'active' : '' }}"
                                            wire:click="$set('caseTab', '{{ $key }}')">{{ $label }}</button>
                                </li>
                            @endforeach
                        </ul>

                        @if($caseTab === 'demands')
                            @forelse($selectedCase->demands->whereNotIn('internal_status', ['cancelled', 'ignored']) as $demand)
                                <div class="card border shadow-none mb-2">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="badge bg-secondary me-1">{{ $demand->source_type }}</span>
                                                <span class="fw-semibold small">{{ $demand->source_case_number ?? $demand->source_process_number ?? 'S/N' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <x-legal.due-date-chip :date="$demand->source_due_at" />
                                                <x-legal.status-badge :status="$demand->internal_status" />
                                                <a href="{{ route('legal.demand.detail', $demand->uuid) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    Ver Detalhes →
                                                </a>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Controlador: {{ $demand->controller?->name ?? '—' }}
                                            &bull; Campo: {{ $demand->currentAssignee?->name ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <x-legal.empty-state icon="bi-inbox" message="Nenhuma demanda ativa neste caso." />
                            @endforelse

                        @elseif($caseTab === 'history')
                            @forelse($selectedCase->demands as $demand)
                                <div class="mb-2 border rounded p-2 small">
                                    <span class="badge bg-secondary me-1">{{ $demand->source_type }}</span>
                                    <x-legal.status-badge :status="$demand->internal_status" />
                                    <span class="ms-1">{{ $demand->source_case_number ?? $demand->source_process_number ?? 'S/N' }}</span>
                                    <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="btn btn-link btn-sm p-0 ms-2">Ver</a>
                                </div>
                            @empty
                                <x-legal.empty-state icon="bi-inbox" message="Sem histórico." />
                            @endforelse

                        @elseif($caseTab === 'timeline')
                            @php
                                $allEvents = $selectedCase->demands->flatMap(fn($d) => $d->events)->sortByDesc('occurred_at');
                            @endphp
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
                                    ⚠ Este caso possui demandas com vinculação incerta (confidence &lt; 80%).
                                    Recomenda-se revisão manual do agrupamento.
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <x-legal.empty-state icon="bi-search" message="Nenhum caso encontrado."
                                 subtext="Tente buscar pelo número do processo, CNPJ, ou nome da parte." />
        @endforelse

        <x-legal.pagination :paginator="$cases" />
    </div>

    <x-show-loading />
</div>
