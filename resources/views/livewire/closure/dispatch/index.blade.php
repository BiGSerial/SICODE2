@php
    $selectAllLimit = min((int) ($counts['selectable'] ?? 0), \App\Http\Livewire\Closure\Dispatch\Index::SELECT_ALL_LIMIT);
    $classifier     = \App\Services\Closure\ClosureMetaClassifier::class;
    $typeTone       = ['P' => 'red', 'M' => 'green', 'F' => 'blue'];
    $sortIcon       = $sortField === 'competence' ? ($sortDir === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line') : 'ri-expand-up-down-line';
    $loadingTargets = 'search,toggleCard,sortBy,perPage,gotoPage,nextPage,previousPage,clearFilters,removeCriterion,applyListFilters,removeListFilter,clearListFilters,applyBulkSearch';
    $anyCard        = $types !== [] || $onlyUndispatched || $onlyBlocked;
@endphp

{{-- Lista operacional do Despacho de Encerramento (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). Seleção no navegador (Alpine), entre páginas. --}}
<div class="closure-dash my-panel ops-list" x-data="closureDispatchSelection({ limit: {{ \App\Http\Livewire\Closure\Dispatch\Index::SELECT_ALL_LIMIT }} })">

    <x-ops.hero eyebrow="Despacho · Encerramento" title="Despacho de Encerramento" subtitle="Selecione Ordens de várias Notas e despache em um modal, com prévia de elegibilidade por Ordem.">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.dashboard') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-bar-chart-2-line" aria-hidden="true"></i> Painel analítico</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
            <a href="{{ route('closure.settings') }}" class="btn btn-outline-light btn-sm btn-dash"><i class="ri-settings-3-line" aria-hidden="true"></i> Configurações</a>
        </div>
    </x-ops.hero>

    {{-- Cartões = filtros que ligam e desligam (clicar de novo desfaz). Passivo/Meta somam entre si; "Total" limpa os recortes. --}}
    <div class="ops-stats">
        <x-ops.stat wire:key="stat-all" wire:click="toggleCard('all')" tone="teal" label="Total" :value="number_format($counts['total'], 0, ',', '.')" :active="!$anyCard" title="Todas as Ordens do recorte atual" />
        @foreach (['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura'] as $type => $label)
            <x-ops.stat wire:key="stat-{{ $type }}" wire:click="toggleCard('{{ $type }}')" :tone="$typeTone[$type]" :label="$label" :value="number_format($counts[$type], 0, ',', '.')" :active="in_array($type, $types, true)" title="{{ $classifier::label($type) }}: clique para mostrar só estas; clique de novo para desfazer" />
        @endforeach
        <x-ops.stat wire:key="stat-undispatched" wire:click="toggleCard('undispatched')" tone="amber" label="Não despachadas" :value="number_format($counts['undispatched'], 0, ',', '.')" :active="$onlyUndispatched" title="Ordens que ainda não têm responsável" />
        @if ($activeDeposits->isNotEmpty())
            <x-ops.stat wire:key="stat-blocked" wire:click="toggleCard('blocked')" tone="red" icon="ri-lock-line" label="Depósito bloqueado" :value="number_format($counts['blocked'], 0, ',', '.')" :active="$onlyBlocked" title="Ordens cujo centro de trabalho (operação 0010) pertence a um depósito bloqueado" />
        @endif
    </div>

    @if ($activeDeposits->isNotEmpty())
        <div class="ops-notice ops-notice--danger" role="status">
            <i class="ri-lock-line" aria-hidden="true"></i>
            <div>
                <strong>{{ $activeDeposits->count() === 1 ? 'Depósito bloqueado' : 'Depósitos bloqueados' }}:</strong>
                @foreach ($activeDeposits as $deposit)
                    <x-ops.badge tone="red" mono :hint="$deposit->deposit_label . ' · desde ' . $deposit->created_at?->format('d/m/Y') . ($deposit->origin === 'CALENDAR' ? ' (inventário)' : '')">{{ $deposit->deposit_code }}</x-ops.badge>
                @endforeach
                As Ordens afetadas trazem o selo do depósito na coluna Status operac.
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm btn-dash" wire:click="toggleCard('blocked')">{{ $onlyBlocked ? 'Ver todas as Ordens' : 'Ver só as afetadas (' . number_format($counts['blocked'], 0, ',', '.') . ')' }}</button>
        </div>
    @endif

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" id="closure-dispatch-search" placeholder="Nota ou Ordem" label="Buscar Nota ou Ordem" />
            <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($hasCriteria)
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="array_merge($criteriaChips, $this->listFilterChips())" :remove-action="'removeAnyFilter'" clear-action="clearFilters" />
            </div>
        @endif

        <x-closure.bulk-search-result :report="$this->bulkReport()" />
    </section>

    <section class="mp-panel" aria-label="Ordens para despacho">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Ordens</h2>
                <div class="mp-panel-sub">{{ number_format($orders->total(), 0, ',', '.') }} {{ $orders->total() === 1 ? 'Ordem' : 'Ordens' }}. A seleção vale entre páginas e Notas.</div>
            </div>
            <div class="ops-panel-actions">
                <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" @click="togglePage()" x-text="allVisible() ? 'Limpar página' : 'Selecionar página'">Selecionar página</button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" @click="selectAllFiltered()" :disabled="loading">Selecionar todas filtradas ({{ number_format($selectAllLimit, 0, ',', '.') }})</button>
            </div>
        </div>

        @if ((int) ($counts['selectable'] ?? 0) > $orders->count())
            <div class="ops-notice" x-cloak x-show="allVisible() && selected.length < {{ $selectAllLimit }}">
                <span x-text="pageCount() + ' Ordens desta página selecionadas.'"></span>
                <a href="#" class="fw-semibold" @click.prevent="selectAllFiltered()">Selecionar as {{ number_format($selectAllLimit, 0, ',', '.') }} filtradas</a>
            </div>
        @endif

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $orders->count()))" :cols="8" />

        @if ($orders->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Nenhuma Ordem para despachar no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : 'Quando houver Ordens elegíveis ao Encerramento, elas aparecem aqui.'"
                    icon="ri-inbox-2-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 38px"><col style="width: 52px"><col style="width: 100px"><col style="width: 104px"><col style="width: 98px">
                        <col style="width: 166px"><col style="width: 110px"><col style="width: 120px"><col style="width: 90px"><col style="width: 56px">
                        <col style="width: 150px"><col style="width: 110px"><col style="width: 40px"><col style="width: 100px"><col style="width: 130px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><span class="visually-hidden">Selecionar</span></th>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Ordem</th>
                            <th aria-sort="{{ $sortField === 'competence' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}"><button type="button" class="ops-sort" wire:click="sortBy('competence')" title="Ordenar por competência">Competência <i class="{{ $sortIcon }}" aria-hidden="true"></i></button></th>
                            <th class="is-left">Obra / Município</th>
                            <th class="is-left" title="Empresa da relação com o informe">Empresa</th>
                            <th class="is-right" title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                            <th>Status SAP</th>
                            <th title="Operações confirmadas / total">Ops.</th>
                            <th class="is-left">Status operac.</th>
                            <th class="is-left">Responsável</th>
                            <th title="Prioridade"><i class="ri-flag-2-line" aria-hidden="true"></i></th>
                            <th>Fim real OP20</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $row = \App\Services\Closure\ClosureDispatchRowPresenter::present($order, $depositSignals, $centerSignals);
                                $fim = $order->fim_real_op20 ? \Carbon\Carbon::parse($order->fim_real_op20) : null;
                                $letter = $classifier::letter($order->meta_type);
                            @endphp
                            <tr wire:key="closure-dispatch-order-{{ $order->id }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free', 'ops-row-priority' => (bool) $order->is_priority, 'is-dispatched' => $row['dispatched']])
                                @if ($row['signal']) data-signal="{{ $row['signal']['key'] }}" title="{{ $row['signal']['label'] }}: {{ $row['signal']['hint'] }}" @endif
                                data-order-row="{{ $order->id }}" data-ordem="{{ $order->ordem }}" data-note="{{ $order->note_number }}" data-company="{{ $order->inform_company_id }}" data-type="{{ $letter }}" data-comp="{{ $classifier::competenceLabel($order->competence) }}"
                                :class="{ 'is-selected': isSelected({{ $order->id }}) }">
                                <td>
                                    @if ($row['dispatched'])
                                        <input type="checkbox" class="form-check-input" disabled title="{{ $row['lockReason'] }}" aria-label="Ordem {{ $order->ordem }} já despachada: {{ $row['lockReason'] }}">
                                    @else
                                        <input type="checkbox" class="form-check-input" data-order-check="{{ $order->id }}" aria-label="Selecionar Ordem {{ $order->ordem }}" :checked="isSelected({{ $order->id }})" @change="toggle({{ $order->id }}, $event.target.checked)">
                                    @endif
                                </td>
                                <td>
                                    @if ($letter)
                                        <x-ops.badge :tone="$typeTone[$letter] ?? 'muted'" mono :label="$letter" :hint="$classifier::label($order->meta_type) . ($fim ? ' · ref. ' . $fim->locale('pt_BR')->translatedFormat('M/Y') : '')" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td class="copy-text" data-value="{{ $order->note_number }}">
                                    <div class="ops-cell-stack is-center">
                                        <span class="ops-note">{{ $order->note_number }}</span>
                                        @if ($order->partial_id)
                                            <x-ops.badge sm tone="amber" label="Parcial" :hint="'Parcial #' . $order->partial_id" />
                                        @endif
                                    </div>
                                </td>
                                <td class="copy-text" data-value="{{ $order->ordem }}"><span class="ops-note">{{ $order->ordem }}</span></td>
                                <td><span class="ops-sub text-body">{{ $classifier::competenceLabel($order->competence) }}</span></td>
                                <td class="is-left">
                                    <div class="text-truncate" title="{{ $order->descricao }}">{{ $order->descricao ?: '—' }}</div>
                                    <span class="ops-sub text-truncate" title="{{ $order->city_name }}">{{ $order->city_name ?: '—' }}</span>
                                </td>
                                <td class="is-left"><div class="text-truncate" title="{{ $order->inform_company_name ? 'Empresa do informe: ' . $order->inform_company_name : 'Sem informe/parcial vinculado' }}{{ $order->holder_company_name ? ' · Com a Ordem agora: ' . $order->holder_company_name : '' }}">{{ $order->inform_company_name ?: '—' }}</div></td>
                                <x-closure.order-cost :planned="$order->custPlanejado" :actual="$order->custRealizado" />
                                <td><x-ops.badge tone="muted" mono :hint="'Status do sistema (SAP)'" :label="$order->statusSist ?: '—'" /></td>
                                <td title="Operações confirmadas / total">{{ (int) $order->ops_conf }}/{{ (int) $order->ops_total }}</td>
                                <td class="is-left">
                                    <div class="ops-cell-stack">
                                        @if ($row['status'])
                                            <x-ops.badge :tone="$row['status']['tone']" :label="$row['status']['label']" :hint="$row['status']['hint']" />
                                            <span class="ops-sub">{{ $row['status']['hint'] }}</span>
                                        @else
                                            <x-ops.badge tone="muted" label="Não despachada" :hint="\App\Services\Closure\ClosureDispatchRowPresenter::statusHint(null)" />
                                        @endif
                                        @if ($row['deposit'])
                                            <x-ops.badge sm :tone="$row['deposit']['tone']" :icon="$row['deposit']['locked'] ? 'ri-lock-line' : 'ri-error-warning-line'" :label="$row['deposit']['label']" :hint="$row['deposit']['hint']" />
                                        @endif
                                    </div>
                                </td>
                                <td class="is-left"><div class="text-truncate" title="{{ $order->assignee_name }}">{{ $order->assignee_name ?: '—' }}</div></td>
                                <td>
                                    @if ($order->is_priority)
                                        <i class="ri-flag-2-fill ops-priority-flag" title="Prioridade" aria-label="Prioridade"></i>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td><x-ops.date :date="$fim" :sub="$fim?->locale('pt_BR')->diffForHumans()" hint="Fim real da operação 0020 (OP20) confirmada" /></td>
                                <td>
                                    <div class="ops-buttons">
                                        @if ($row['dispatched'])
                                            <span class="d-inline-block" tabindex="0" title="{{ $row['lockReason'] }}"><button type="button" class="btn btn-primary btn-sm ops-action" disabled aria-label="Ordem {{ $order->ordem }} já despachada: {{ $row['lockReason'] }}"><i class="ri-send-plane-line" aria-hidden="true"></i> Despachar</button></span>
                                        @else
                                            <button type="button" class="btn btn-primary btn-sm ops-action" @click="openModal([{{ $order->id }}])" title="Despachar esta Ordem" aria-label="Despachar Ordem {{ $order->ordem }}"><i class="ri-send-plane-line" aria-hidden="true"></i> Despachar</button>
                                        @endif
                                        <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'dispatch']) }}" class="btn btn-outline-secondary btn-sm ops-action" title="Abrir a obra desta Ordem" aria-label="Abrir obra da Ordem {{ $order->ordem }}"><i class="ri-external-link-line" aria-hidden="true"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$orders" :per-page="$perPage" :sizes="\App\Http\Livewire\Closure\Dispatch\Index::PER_PAGE_OPTIONS" />
    </section>

    <x-ops.selection-bar clear="clear()">
        <button type="button" id="closure-dispatch-open" class="btn btn-light btn-sm btn-dash" @click="openModal(selected)"><i class="ri-send-plane-line" aria-hidden="true"></i> Despachar selecionadas (<span x-text="selected.length">0</span>)</button>
    </x-ops.selection-bar>

    @livewire('closure.dispatch.dispatch-modal')
</div>
