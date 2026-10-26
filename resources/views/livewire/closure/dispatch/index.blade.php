<div class="closure-dash">
    @include('livewire.closure._styles')

    @php
        $selectAllLimit = min((int) ($counts['selectable'] ?? 0), \App\Http\Livewire\Closure\Dispatch\Index::SELECT_ALL_LIMIT);
        $typeColors    = ['P' => 'var(--dash-red)', 'M' => 'var(--dash-green)', 'F' => 'var(--dash-blue)'];
        $activeCard    = $onlyUndispatched ? 'undispatched' : (count($types) === 1 ? $types[0] : 'all');
        $hasFilters    = $search !== '' || $types || $status || $filterCompanyId || count($regions) || count($regionals) || count($cityCodes) || count($statusUsers) || count($bulkTerms) || $filterAssigneeId || $onlyUndispatched || $competence;
        $sortIcon      = $sortField === 'competence' ? ($sortDir === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line') : 'ri-expand-up-down-line';
    @endphp

    <style>
        [x-cloak] { display: none !important; }
        .closure-dash .dispatch-settings-backdrop { position: fixed; inset: 0; background: rgba(16, 32, 51, .45); z-index: 1040; }
        .closure-dash .dispatch-settings-panel { position: fixed; top: 0; right: 0; bottom: 0; width: min(420px, 100%); background: #fff; z-index: 1050; box-shadow: -12px 0 30px rgba(16, 32, 51, .2); overflow-y: auto; }
        .closure-dash .stat-card { background: #fff; border: 1px solid var(--dash-line); border-left: 4px solid var(--accent, var(--dash-teal)); border-radius: 8px; cursor: pointer; padding: 10px 14px; text-align: left; transition: box-shadow .15s, transform .15s; width: 100%; }
        .closure-dash .stat-card:hover { box-shadow: 0 8px 20px rgba(16, 32, 51, .1); transform: translateY(-1px); }
        .closure-dash .stat-card.is-active { background: color-mix(in srgb, var(--accent, var(--dash-teal)) 10%, #fff); box-shadow: 0 0 0 2px var(--accent, var(--dash-teal)); }
        .closure-dash .stat-card .stat-value { color: var(--dash-ink); font-size: 1.5rem; font-weight: 850; line-height: 1.1; }
        .closure-dash .stat-card .stat-label { color: var(--dash-muted); font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .closure-dash .type-pill { align-items: center; background: #fff; border: 1px solid var(--dash-line); border-radius: 999px; color: var(--dash-ink); cursor: pointer; display: inline-flex; font-size: .8rem; font-weight: 700; gap: 6px; padding: 4px 12px 4px 5px; }
        .closure-dash .type-pill .type-dot { height: 22px; width: 22px; font-size: .7rem; }
        .closure-dash .type-pill.is-active { background: var(--c); border-color: var(--c); color: #fff; }
        .closure-dash .type-pill.is-active .type-dot { background: rgba(255, 255, 255, .25) !important; }
        .closure-dash .filter-bar .form-select, .closure-dash .filter-bar .form-control { font-size: .85rem; }
        .closure-dash .sort-btn { background: none; border: 0; color: inherit; font: inherit; padding: 0; text-transform: inherit; white-space: nowrap; }
        .closure-dash .sort-btn i { opacity: .6; }
        .closure-dash .select-banner { background: #eef5f6; border-bottom: 1px solid var(--dash-line); font-size: .82rem; padding: 8px 16px; text-align: center; }
        .closure-dash .skeleton-row td { background-image: linear-gradient(90deg, #eef2f6 25%, #f8fafb 50%, #eef2f6 75%); background-size: 200% 100%; animation: closure-shimmer 1.2s infinite; height: 38px; }
        @keyframes closure-shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }
        @media (max-width: 1399.98px) { .closure-dash .hide-xl { display: none; } }
    </style>

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Despacho de Encerramento</h2>
                <div class="dash-subtitle">Selecione Ordens de várias Notas e despache em um modal, com prévia de elegibilidade por Ordem.</div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.dashboard') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-bar-chart-2-line"></i> Painel analítico</a>
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
                <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click="openSettings"><i class="ri-settings-3-line"></i> Configurações</button>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        @foreach ([
            ['all', 'Total', $counts['total'], 'var(--dash-teal)'],
            ['P', 'Passivo', $counts['P'], 'var(--dash-red)'],
            ['M', 'Meta atual', $counts['M'], 'var(--dash-green)'],
            ['F', 'Meta futura', $counts['F'], 'var(--dash-blue)'],
            ['undispatched', 'Não despachadas', $counts['undispatched'], '#f59e0b'],
        ] as [$card, $label, $value, $color])
            <div class="col-6 col-md-4 col-xl" wire:key="stat-{{ $card }}">
                <button type="button" class="stat-card @if ($activeCard === $card) is-active @endif" style="--accent: {{ $color }};" wire:click="setCard('{{ $card }}')" aria-pressed="{{ $activeCard === $card ? 'true' : 'false' }}">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-value">{{ number_format($value, 0, ',', '.') }}</div>
                </button>
            </div>
        @endforeach
    </div>

    <div class="table-card p-3 mb-3 filter-bar" id="closure-dispatch-filters" x-data="closureFilterPanel()"
        data-options="{{ json_encode(['options' => $filterOptions, 'sel' => ['regions' => $regions, 'regionals' => $regionals, 'cityCodes' => $cityCodes, 'statusUsers' => $statusUsers]], JSON_UNESCAPED_UNICODE) }}">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="input-group" style="max-width: 260px;">
                <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                <input id="closure-dispatch-search" type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Nota ou Ordem" aria-label="Buscar Nota ou Ordem">
            </div>
            <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />
            <div class="d-flex gap-1" role="group" aria-label="Filtrar por tipo">
                @foreach (\App\Services\Closure\ClosureMetaClassifier::TYPES as $type)
                    <button type="button" wire:key="pill-{{ $type }}" class="type-pill @if (in_array($type, $types, true)) is-active @endif" style="--c: {{ $typeColors[$type] }};" wire:click="toggleType('{{ $type }}')" aria-pressed="{{ in_array($type, $types, true) ? 'true' : 'false' }}" title="{{ \App\Services\Closure\ClosureMetaClassifier::label($type) }}">
                        <span class="type-dot {{ \App\Services\Closure\ClosureMetaClassifier::badgeClass($type) }}">{{ $type }}</span>{{ $counts[$type] }}
                    </button>
                @endforeach
            </div>
            <div class="form-check form-switch mb-0 ms-1">
                <input class="form-check-input" type="checkbox" role="switch" id="closure-dispatch-undispatched" wire:model="onlyUndispatched">
                <label class="form-check-label small" for="closure-dispatch-undispatched">Só não despachadas</label>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash d-lg-none" @click="more = !more" :aria-expanded="more ? 'true' : 'false'" aria-controls="closure-dispatch-filter-grid"><i class="ri-filter-3-line"></i> <span x-text="more ? 'Menos filtros' : 'Mais filtros'">Mais filtros</span></button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearFilters" @disabled(!$hasFilters)><i class="ri-close-circle-line"></i> Limpar filtros</button>
            </div>
        </div>

        <div id="closure-dispatch-filter-grid" class="filter-grid mt-3" :class="{ 'is-open': more }">
            <div class="filter-field"><label>Região</label><x-closure.multi-select label="Região" field="regions" options="opts.regions" /></div>
            <div class="filter-field"><label>Regional</label><x-closure.multi-select label="Regional" field="regionals" options="regionalOptions()" /></div>
            <div class="filter-field"><label>Município</label><x-closure.multi-select label="Município" field="cityCodes" options="cityOptions()" /></div>
            <div class="filter-field"><label>Status usuário</label><x-closure.multi-select label="Status usuário" field="statusUsers" options="opts.statusUsers" /></div>
            <div class="filter-field">
                <label for="closure-dispatch-filter-company">Empresa (informe)</label>
                <select id="closure-dispatch-filter-company" class="form-select form-select-sm" wire:model="filterCompanyId" aria-label="Empresa (informe)" title="Empresa da relação com o informe">
                    <option value="">Todas</option>
                    @foreach ($this->companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="closure-dispatch-competence">Competência</label>
                <select id="closure-dispatch-competence" class="form-select form-select-sm" wire:model="competence" aria-label="Competência">
                    <option value="">Todas</option>
                    @foreach ($competences as $period => $total)
                        <option value="{{ $period }}">{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($period) }} ({{ number_format($total, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="closure-dispatch-status">Status operacional</label>
                <select id="closure-dispatch-status" class="form-select form-select-sm" wire:model="status" aria-label="Status operacional">
                    <option value="">Todos</option>
                    <option value="NONE">Não despachada</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="closure-dispatch-responsible">Responsável</label>
                <select id="closure-dispatch-responsible" class="form-select form-select-sm" wire:model="filterAssigneeId" aria-label="Responsável">
                    <option value="">Todos</option>
                    @foreach ($responsibles as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-closure.bulk-search-result :report="$this->bulkReport()" class="mt-3" />
        <div class="active-filters mt-3" id="closure-dispatch-active-filters">
            <span class="small fw-semibold text-muted">Filtros ativos</span>
            @forelse ($chips as $chip)
                <button type="button" class="filter-chip" wire:key="chip-{{ $chip['kind'] }}-{{ md5($chip['value']) }}" wire:click="removeFilter('{{ $chip['kind'] }}', @js($chip['value']))" title="Remover filtro"><span>{{ $chip['label'] }}</span><i class="ri-close-line" aria-hidden="true"></i></button>
            @empty
                <span class="small text-muted">nenhum</span>
            @endforelse
            <span class="ms-auto small text-muted"><strong>{{ number_format($orders->total(), 0, ',', '.') }}</strong> Ordem(ns)</span>
        </div>
    </div>

    {{-- Seleção 100% no browser (sem round-trip Livewire por checkbox); o modal abre direto via Livewire.emitTo. --}}
    <div x-data="closureDispatchSelection({ limit: {{ \App\Http\Livewire\Closure\Dispatch\Index::SELECT_ALL_LIMIT }} })">
    <div class="table-card mb-3">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens</h3>
                <div class="chart-subtitle">
                    {{ number_format($orders->total(), 0, ',', '.') }} Ordem(ns). A seleção vale entre páginas e Notas.
                    <span wire:loading wire:target="search,types,status,filterCompanyId,filterAssigneeId,onlyUndispatched,competence,sortBy,setCard,toggleType,perPage,gotoPage,nextPage,previousPage,clearFilters,applyMultiFilters,removeFilter"><span class="spinner-border spinner-border-sm ms-1" aria-hidden="true"></span> Atualizando...</span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center gap-1 small text-muted">
                    <label for="closure-dispatch-per-page-top" class="mb-0">Itens por página</label>
                    <select id="closure-dispatch-per-page-top" class="form-select form-select-sm w-auto" wire:model="perPage" aria-label="Itens por página">
                        @foreach (\App\Http\Livewire\Closure\Dispatch\Index::PER_PAGE_OPTIONS as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" @click="togglePage()" x-text="allVisible() ? 'Limpar página' : 'Selecionar página'">Selecionar página</button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" @click="selectAllFiltered()" :disabled="loading">Selecionar todas filtradas ({{ number_format($selectAllLimit, 0, ',', '.') }})</button>
                <button type="button" id="closure-dispatch-open" class="btn btn-primary btn-sm btn-dash" x-cloak x-show="selected.length > 0" :disabled="selected.length === 0" @click="openModal(selected)"><i class="ri-send-plane-line"></i> Despachar selecionadas (<span x-text="selected.length">0</span>)</button>
                <button type="button" class="btn btn-link btn-sm text-decoration-none text-muted p-0" x-cloak x-show="selected.length > 0" @click="clear()">Limpar seleção</button>
            </div>
        </div>
        @if ((int) ($counts['selectable'] ?? 0) > $orders->count())
            <div class="select-banner" x-cloak x-show="allVisible() && selected.length < {{ $selectAllLimit }}">
                <span x-text="pageCount() + ' Ordens desta página selecionadas.'"></span>
                <a href="#" class="fw-semibold" @click.prevent="selectAllFiltered()">Selecionar as {{ number_format($selectAllLimit, 0, ',', '.') }} filtradas</a>
            </div>
        @endif
        <x-closure.signal-legend class="px-3 pb-2" />
        <div class="table-responsive dash-scroll" wire:loading.class="is-loading-soft" wire:target="search,types,status,filterCompanyId,filterAssigneeId,onlyUndispatched,competence,sortBy,setCard,toggleType,perPage,gotoPage,nextPage,previousPage,clearFilters,applyMultiFilters,removeFilter">
            <table class="table table-sm align-middle mb-0 dash-table">
                <colgroup>
                    <col style="width: 44px;"><col style="width: 56px;"><col style="width: 120px;"><col style="width: 120px;"><col style="width: 104px;">
                    <col style="width: 20%;"><col style="width: 14%;"><col style="width: 122px;"><col style="width: 100px;" class="hide-xl"><col style="width: 84px;" class="hide-xl">
                    <col style="width: 150px;"><col style="width: 13%;"><col style="width: 52px;"><col style="width: 140px;"><col style="width: 92px;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3"><span class="visually-hidden">Selecionar</span></th>
                        <th class="text-center">Tipo</th>
                        <th>Nota</th>
                        <th>Ordem</th>
                        <th aria-sort="{{ $sortField === 'competence' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}"><button type="button" class="sort-btn" wire:click="sortBy('competence')" title="Ordenar por competência">Competência <i class="{{ $sortIcon }}"></i></button></th>
                        <th>Obra / Município</th>
                        <th title="Empresa da relação com o informe">Empresa</th>
                        <th class="text-end" title="Valor da ADS do informe a que a Ordem pertence">Valor da obra (ADS)</th>
                        <th class="hide-xl">Status SAP</th>
                        <th class="hide-xl" title="Operações confirmadas / total">Ops.</th>
                        <th>Status operac.</th>
                        <th>Responsável</th>
                        <th class="text-center" title="Prioridade"><i class="ri-flag-2-line"></i></th>
                        <th>Fim real OP20</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $fim        = $order->fim_real_op20 ? \Carbon\Carbon::parse($order->fim_real_op20) : null;
                            $sig        = \App\Services\Closure\ClosureSignal::forRow($order);
                            $rowClass   = $sig ? 'signal-row signal-' . $sig['key'] : '';
                            // Ordem que já tem posse (despachada) nunca é despachada de novo: mudar de responsável é pela Transferência/Controle.
                            $dispatched = $order->state_id !== null;
                            $heldBy     = $order->assignee_name ?: ($order->holder_company_name ? 'a pilha de ' . $order->holder_company_name : 'outro responsável');
                            $lockReason = 'Já despachada: está com ' . $heldBy . '. Para mudar o responsável use a Transferência.';
                        @endphp
                        <tr wire:key="closure-dispatch-order-{{ $order->id }}" @class([$rowClass => $rowClass !== '', 'is-dispatched' => $dispatched]) @if ($sig) data-signal="{{ $sig['key'] }}" title="{{ $sig['label'] }}: {{ $sig['hint'] }}" @endif data-order-row="{{ $order->id }}" data-ordem="{{ $order->ordem }}" data-note="{{ $order->note_number }}" data-company="{{ $order->inform_company_id }}" data-type="{{ \App\Services\Closure\ClosureMetaClassifier::letter($order->meta_type) }}" data-comp="{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($order->competence) }}" :class="{ 'is-selected': isSelected({{ $order->id }}) }">
                            <td class="ps-3">
                                @if ($dispatched)
                                    <input type="checkbox" class="form-check-input" disabled title="{{ $lockReason }}" aria-label="Ordem {{ $order->ordem }} já despachada: {{ $lockReason }}">
                                @else
                                    <input type="checkbox" class="form-check-input" data-order-check="{{ $order->id }}" aria-label="Selecionar Ordem {{ $order->ordem }}" :checked="isSelected({{ $order->id }})" @change="toggle({{ $order->id }}, $event.target.checked)">
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="type-dot badge {{ \App\Services\Closure\ClosureMetaClassifier::badgeClass($order->meta_type) }}" data-bs-toggle="tooltip" title="{{ \App\Services\Closure\ClosureMetaClassifier::label($order->meta_type) }}{{ $fim ? ' · ref. ' . $fim->locale('pt_BR')->translatedFormat('M/Y') : '' }}">{{ \App\Services\Closure\ClosureMetaClassifier::letter($order->meta_type) ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="text-truncate d-inline-block align-middle" style="max-width: 100%;" title="{{ $order->note_number }}">{{ $order->note_number }}</span>
                                @if ($order->partial_id)
                                    <span class="badge bg-light text-dark border" title="Parcial #{{ $order->partial_id }}">Parcial</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $order->ordem }}</td>
                            <td class="small text-nowrap">{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($order->competence) }}</td>
                            <td>
                                <div class="text-truncate" title="{{ $order->descricao }}">{{ $order->descricao ?: '—' }}</div>
                                <div class="small text-muted text-truncate" title="{{ $order->city_name }}">{{ $order->city_name ?: '—' }}</div>
                            </td>
                            <td class="small"><div class="text-truncate" title="{{ $order->inform_company_name ? 'Empresa do informe: ' . $order->inform_company_name : 'Sem informe/parcial vinculado' }}{{ $order->holder_company_name ? ' · Com a Ordem agora: ' . $order->holder_company_name : '' }}">{{ $order->inform_company_name ?: '—' }}</div></td>
                            <td class="small text-end text-nowrap" title="Valor da ADS do informe a que a Ordem pertence">{{ \App\Services\Closure\ClosureInformValue::format($order->inform_value) }}</td>
                            <td class="text-muted small hide-xl"><div class="text-truncate" title="{{ $order->statusSist }}">{{ $order->statusSist ?: '—' }}</div></td>
                            <td class="small hide-xl" title="Operações confirmadas / total">{{ (int) $order->ops_conf }}/{{ (int) $order->ops_total }}</td>
                            <td>
                                @if ($order->state_id)
                                    <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                        <span class="badge {{ \App\Enum\ClosureOperationalStatus::badgeClassFor($order->operational_status) }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($order->operational_status) }}</span>
                                    </span>

                                    @if ($sig = ($depositSignals[$order->state_id] ?? null))
                                        <div class="mt-1"><span class="st-chip" style="background:#fee2e2;color:#b91c1c;" title="Depósito {{ $sig->deposit_code }}{{ $sig->deposit_label ? ' · ' . $sig->deposit_label : '' }} bloqueado desde {{ $sig->created_at?->format('d/m/Y') }}{{ $sig->origin === 'CALENDAR' ? ' (inventário)' : '' }}. Volta ao status anterior quando o depósito for liberado."><i class="ri-lock-line"></i> Depósito {{ $sig->deposit_code }}</span></div>
                                    @endif
                                @else
                                    <span class="d-inline-flex align-items-center gap-1 text-muted small"><span class="status-dot" style="background: #cbd5e1;"></span>Não despachada</span>
                                @endif
                            </td>
                            <td class="small"><div class="text-truncate" title="{{ $order->assignee_name }}">{{ $order->assignee_name ?: '—' }}</div></td>
                            <td class="text-center">@if ($order->is_priority)<i class="ri-flag-2-fill text-warning" title="Prioridade" aria-label="Prioridade"></i>@else<span class="text-muted">—</span>@endif</td>
                            <td class="small">
                                @if ($fim)
                                    <div>{{ $fim->format('d/m/Y') }}</div>
                                    <div class="text-muted text-truncate">{{ $fim->locale('pt_BR')->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="pe-3 text-end text-nowrap">
                                @if ($dispatched)
                                    <span class="d-inline-block" tabindex="0" title="{{ $lockReason }}"><button type="button" class="icon-btn" disabled aria-label="Ordem {{ $order->ordem }} já despachada: {{ $lockReason }}"><i class="ri-send-plane-line"></i></button></span>
                                @else
                                    <button type="button" class="icon-btn" @click="openModal([{{ $order->id }}])" data-bs-toggle="tooltip" title="Despachar esta Ordem" aria-label="Despachar Ordem {{ $order->ordem }}"><i class="ri-send-plane-line"></i></button>
                                @endif
                                <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'dispatch']) }}" class="icon-btn" data-bs-toggle="tooltip" title="Abrir obra" aria-label="Abrir obra da Ordem {{ $order->ordem }}"><i class="ri-external-link-line"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15">
                                <div class="dash-empty">
                                    <i class="ri-inbox-2-line"></i>
                                    <div class="fw-semibold mt-2">Nenhuma Ordem encontrada</div>
                                    <div class="small">Ajuste a busca ou os filtros para ver Ordens.</div>
                                    @if ($hasFilters)
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-dash mt-2" wire:click="clearFilters">Limpar filtros</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-3 small text-muted" id="closure-dispatch-footer-info">
                <span class="d-inline-flex align-items-center gap-2">
                    <label for="closure-dispatch-per-page" class="mb-0">Itens por página</label>
                    <select id="closure-dispatch-per-page" class="form-select form-select-sm w-auto" wire:model="perPage">
                        @foreach (\App\Http\Livewire\Closure\Dispatch\Index::PER_PAGE_OPTIONS as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </span>
                <span>Exibindo {{ number_format((int) $orders->firstItem(), 0, ',', '.') }}–{{ number_format((int) $orders->lastItem(), 0, ',', '.') }} de {{ number_format($orders->total(), 0, ',', '.') }}</span>
            </div>
            <div>{{ $orders->links() }}</div>
        </div>
    </div>

    </div>

    @livewire('closure.dispatch.dispatch-modal')

    @if ($showSettings)
        <div class="dispatch-settings-backdrop" wire:click="closeSettings"></div>
        <div class="dispatch-settings-panel p-4" id="closure-dispatch-settings" role="dialog" aria-label="Configurações do Encerramento" x-data @keydown.escape.window="$wire.closeSettings()">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h3 class="chart-title mb-0">Configurações do Encerramento</h3>
                <button type="button" class="btn-close" aria-label="Fechar" wire:click="closeSettings"></button>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="closure-setting-future" wire:model.defer="allowFutureCompletion">
                <label class="form-check-label" for="closure-setting-future">Permitir concluir obras de Meta Futura <span class="d-block small text-muted">Elas vão para o status Aguardando até a meta chegar.</span></label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="closure-setting-self-assign" wire:model.defer="selfAssignEnabled">
                <label class="form-check-label" for="closure-setting-self-assign">Habilitar lista de auto-atribuição no Serviço</label>
            </div>
            <div class="mb-4">
                <div class="fw-semibold mb-1">Tipos liberados para auto-atribuição</div>
                @foreach (\App\Services\Closure\ClosureMetaClassifier::TYPES as $type)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="closure-setting-type-{{ $type }}" value="{{ $type }}" wire:model.defer="selfAssignTypes">
                        <label class="form-check-label" for="closure-setting-type-{{ $type }}">{!! \App\Services\Closure\ClosureMetaClassifier::badge($type) !!} {{ \App\Services\Closure\ClosureMetaClassifier::label($type) }}</label>
                    </div>
                @endforeach
            </div>
            <div class="mb-4">
                <div class="fw-semibold mb-1">Caixas das atividades</div>
                <div class="small text-muted mb-2">Escolha quais status do usuário (SAP) aparecem na caixa de Medição e na de Fiscalização.</div>
                <a href="{{ route('closure.status_map') }}" class="btn btn-outline-secondary btn-sm"><i class="ri-route-line"></i> Mapa de status por serviço</a>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" wire:click="saveSettings">Salvar</button>
                <button type="button" class="btn btn-outline-secondary btn-dash" wire:click="closeSettings">Cancelar</button>
            </div>
        </div>
    @endif

    @once
        <script>
            window.closureDispatchSelection = window.closureDispatchSelection || function (config) {
                return {
                    selected: [],
                    loading: false,
                    tick: 0,
                    meta: {},
                    init() {
                        const bump = () => { this.tick++; this.remember(); };
                        this.remember();
                        // Após cada re-render da lista, reavalia "selecionar página" (as caixas novas já nascem marcadas pelo :checked).
                        if (window.Livewire && window.Livewire.hook) { window.Livewire.hook('message.processed', bump); }
                        // Depois do despacho, ficam selecionadas só as Ordens recusadas.
                        if (window.Livewire) {
                            window.Livewire.on('closureOrdersDispatched', (refused) => {
                                this.selected = (refused || []).map(Number);
                            });
                        }
                    },
                    // Dados das linhas da página (data-attributes) guardados por id: resumo do modal sem ida ao servidor.
                    remember() {
                        this.$root.querySelectorAll('[data-order-row]').forEach((tr) => {
                            const id = Number(tr.dataset.orderRow);
                            this.meta[id] = { id, ordem: tr.dataset.ordem || null, note: tr.dataset.note || null, type: tr.dataset.type || null, comp: tr.dataset.comp || '—', company: tr.dataset.company || null };
                        });
                    },
                    isSelected(id) { return this.selected.includes(id); },
                    toggle(id, on) {
                        this.selected = on ? (this.selected.includes(id) ? this.selected : [...this.selected, id]) : this.selected.filter((x) => x !== id);
                    },
                    pageIdsOnScreen() {
                        this.tick;
                        return Array.from(this.$root.querySelectorAll('[data-order-check]')).map((el) => Number(el.dataset.orderCheck));
                    },
                    pageCount() { return this.pageIdsOnScreen().length; },
                    allVisible() {
                        const ids = this.pageIdsOnScreen();
                        return ids.length > 0 && ids.every((id) => this.selected.includes(id));
                    },
                    togglePage() {
                        const ids = this.pageIdsOnScreen();
                        this.selected = this.allVisible()
                            ? this.selected.filter((id) => !ids.includes(id))
                            : [...new Set([...this.selected, ...ids])];
                    },
                    async selectAllFiltered() {
                        this.loading = true;
                        try {
                            const briefs = await this.$wire.filteredOrderBriefs();
                            (briefs || []).forEach((b) => { this.meta[Number(b.id)] = b; });
                            this.selected = (briefs || []).map((b) => Number(b.id));
                        } finally {
                            this.loading = false;
                        }
                    },
                    clear() { this.selected = []; },
                    openModal(ids) {
                        if (!ids.length) { return; }
                        this.remember();
                        // Abre já no cliente (0 rede): o modal escuta este evento; a prévia de elegibilidade vem depois.
                        window.dispatchEvent(new CustomEvent('closure-dispatch-open', { detail: { rows: ids.map((id) => this.meta[id] || { id }) } }));
                    },
                };
            };
        </script>
    @endonce
</div>
