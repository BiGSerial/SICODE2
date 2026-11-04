@php
    $classifier     = \App\Services\Closure\ClosureMetaClassifier::class;
    $presenter      = \App\Services\Closure\ClosureDispatchRowPresenter::class;
    $typeTone       = ['P' => 'red', 'M' => 'green', 'F' => 'blue'];
    $avgMin         = $stats['avg_min'];
    $avgLabel       = $avgMin >= 1440 ? intdiv($avgMin, 1440) . 'd ' . intdiv($avgMin % 1440, 60) . 'h' : ($avgMin >= 60 ? intdiv($avgMin, 60) . 'h ' . str_pad((string) ($avgMin % 60), 2, '0', STR_PAD_LEFT) . 'm' : $avgMin . ' min');
    $statusFilter   = $filters['status'] ?? [];
    $selectedCount  = count($selectedStateIds);
    $riByNote       = app(\App\Services\Closure\ClosureInternalReturnService::class)->activeForNotes($states->getCollection()->map(fn ($st) => $st->Order?->note_id)->filter());
    $allSelected    = $states->count() > 0 && $states->every(fn ($st) => in_array($st->id, $selectedStateIds));
    $loadingTargets = 'search,type,toggleCard,gotoPage,nextPage,previousPage,perPage,clearFilters,removeAnyFilter,applyListFilters,removeListFilter,clearListFilters,applyBulkSearch,selectAllFilteredStates';
    $initials       = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

{{-- Lista de controle do Encerramento (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10): o que já foi despachado, de todos os usuários e empresas. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Controle · Encerramento" title="Controle de Encerramento" subtitle="Ordens já despachadas, de todos os usuários e empresas: acompanhe, priorize, atribua e transfira.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <span class="ops-mode-pill" title="Esta é uma lista de controle: aqui não se despacha, só se acompanha e se corrige."><i class="ri-radar-line" aria-hidden="true"></i> Lista de controle</span>
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.main') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-send-plane-line" aria-hidden="true"></i> Lista para despacho</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        </div>
    </x-ops.hero>

    @if ($dueDeposits > 0)
        <div class="ops-notice ops-notice--warn" role="status">
            <i class="ri-calendar-event-line" aria-hidden="true"></i>
            <div><strong>{{ $dueDeposits }} depósito(s) em data de inventário</strong> aguardando a confirmação do bloqueio. As Ordens dos centros de trabalho vinculados só passam a Depósito Bloqueado depois que você confirmar.</div>
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.deposits') }}" class="btn btn-warning btn-sm btn-dash">Abrir calendário <i class="ri-arrow-right-line" aria-hidden="true"></i></a>
        </div>
    @endif

    @if ($activeDeposits->isNotEmpty())
        <div class="ops-notice ops-notice--danger" role="status">
            <i class="ri-lock-line" aria-hidden="true"></i>
            <div>
                <strong>{{ $activeDeposits->count() === 1 ? 'Depósito bloqueado' : 'Depósitos bloqueados' }}:</strong>
                @foreach ($activeDeposits as $deposit)
                    <x-ops.badge tone="red" mono :hint="$deposit->deposit_label . ' · desde ' . $deposit->created_at?->format('d/m/Y') . ($deposit->origin === 'CALENDAR' ? ' (inventário)' : '')">{{ $deposit->deposit_code }}</x-ops.badge>
                @endforeach
                As Ordens travadas aparecem como "Depósito Bloqueado"; as que só estão no alcance trazem um selo âmbar.
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm btn-dash" wire:click="toggleCard('DEPOSIT_BLOCKED')">{{ $statusFilter === ['DEPOSIT_BLOCKED'] ? 'Ver todas' : 'Ver só as travadas (' . number_format($stats['deposit'], 0, ',', '.') . ')' }}</button>
        </div>
    @endif

    {{-- Cartões = filtros de status que ligam e desligam (clicar de novo desfaz). Contam no mesmo recorte, sem o filtro de status. --}}
    <div class="ops-stats">
        <x-ops.stat wire:key="stat-all" wire:click="toggleCard('all')" tone="teal" icon="ri-list-check" label="Total na fila" :value="number_format($stats['total'], 0, ',', '.')" :active="$statusFilter === [] && !$onlyPriority" title="Todas as Ordens do recorte atual, em qualquer status" />
        <x-ops.stat wire:key="stat-progress" wire:click="toggleCard('IN_PROGRESS')" tone="blue" icon="ri-user-settings-line" label="Em andamento" :value="number_format($stats['progress'], 0, ',', '.')" :active="$statusFilter === ['IN_PROGRESS']" title="O responsável está trabalhando nelas" />
        <x-ops.stat wire:key="stat-available" wire:click="toggleCard('AVAILABLE')" tone="amber" icon="ri-user-unfollow-line" label="Sem responsável" :value="number_format($stats['available'], 0, ',', '.')" :active="$statusFilter === ['AVAILABLE']" title="Na pilha da empresa, esperando alguém assumir" />
        <x-ops.stat wire:key="stat-priority" wire:click="toggleCard('priority')" tone="red" icon="ri-flag-2-line" label="Prioritárias" :value="number_format($stats['priority'], 0, ',', '.')" :active="$onlyPriority" title="Ordens marcadas como prioridade" />
        <x-ops.stat wire:key="stat-deposit" wire:click="toggleCard('DEPOSIT_BLOCKED')" tone="red" icon="ri-lock-line" label="Depósito bloqueado" :value="number_format($stats['deposit'], 0, ',', '.')" :active="$statusFilter === ['DEPOSIT_BLOCKED']" title="Travadas porque o depósito do centro de trabalho está bloqueado" />
        <x-ops.stat wire:key="stat-avg" tone="muted" icon="ri-time-line" label="Tempo médio no status" :value="$avgLabel" disabled title="Média de tempo da fila no status atual" />
    </div>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" id="closure-control-search" placeholder="Ordem ou nota" label="Buscar por Ordem ou Nota" />
            <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />
            <x-ops.segmented name="closureControlType" label="Tipo de Ordem" wire:model="type" :options="['all' => 'Todos', 'P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura']" />

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
                <x-ops.chips :chips="array_merge($criteriaChips, $this->listFilterChips())" remove-action="removeAnyFilter" clear-action="clearFilters" />
            </div>
        @endif

        <x-closure.bulk-search-result :report="$this->bulkReport()" />
    </section>

    <section class="mp-panel" aria-label="Fila operacional">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Fila operacional</h2>
                <div class="mp-panel-sub">{{ number_format($states->total(), 0, ',', '.') }} {{ $states->total() === 1 ? 'Ordem' : 'Ordens' }}. O status SAP é informativo; as ações não o alteram.</div>
            </div>
            <div class="ops-panel-actions">
                <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleStates">{{ $allSelected ? 'Limpar página' : 'Selecionar página' }}</button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="selectAllFilteredStates" wire:loading.attr="disabled" wire:target="selectAllFilteredStates">Selecionar todas filtradas ({{ number_format(min($states->total(), \App\Http\Livewire\Closure\Dispatch\Control::SELECT_ALL_LIMIT), 0, ',', '.') }})</button>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $states->count()))" :cols="8" />

        @if ($states->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Nenhuma Ordem despachada no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : 'Quando houver Ordens despachadas, elas aparecem aqui.'"
                    icon="ri-inbox-2-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 38px"><col style="width: 52px"><col style="width: 150px"><col style="width: 84px"><col style="width: 190px"><col style="width: 120px">
                        <col style="width: 92px"><col style="width: 64px"><col style="width: 170px"><col style="width: 210px"><col style="width: 96px"><col style="width: 56px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" @checked($allSelected) wire:click="toggleVisibleStates" aria-label="Selecionar ou limpar as Ordens da página"></th>
                            <th>Tipo</th>
                            <th class="is-left">Ordem / Nota</th>
                            <th title="Mês de competência da meta">Compet.</th>
                            <th class="is-left">Obra / Município</th>
                            <th class="is-right" title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                            <th>Status SAP</th>
                            <th title="Operações confirmadas / total">Ops.</th>
                            <th class="is-left">Responsável</th>
                            <th class="is-left">Status da atividade</th>
                            <th title="Há quanto tempo a Ordem está neste status">Tempo no status</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($states as $state)
                            @php
                                $selected = in_array($state->id, $selectedStateIds);
                                $order    = $state->Order;
                                $ops      = $order?->Operations ?? collect();
                                $conf     = $ops->filter(fn ($op) => $this->isConfirmedOperation($op->status))->count();
                                $holder   = $state->CurrentHolderUser?->name;
                                $letter   = $classifier::letter($state->meta_type);
                                $deposit  = $presenter::deposit($state->id, $state->operational_status, $state->cen_trab, $depositSignals, $centerSignals);
                                $ri       = $riByNote->get($order?->note_id);
                                $since    = $state->entered_current_status_at;
                            @endphp
                            <tr wire:key="closure-work-state-{{ $state->id }}" @class(['is-selected' => $selected, 'ops-row-priority' => (bool) $state->priority])>
                                <td><input type="checkbox" class="form-check-input" @checked($selected) wire:click="toggleState({{ $state->id }})" aria-label="Selecionar Ordem {{ $order?->ordem }}"></td>
                                <td>
                                    @if ($letter)
                                        <x-ops.badge :tone="$typeTone[$letter] ?? 'muted'" mono :label="$letter" :hint="$classifier::label($state->meta_type)" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td class="is-left copy-text" data-value="{{ $order?->ordem }}">
                                    <div class="ops-note">{{ $order?->ordem ?? '—' }} @if ($state->priority)<i class="ri-flag-2-fill ops-priority-flag" title="Prioritária" aria-label="Prioritária"></i>@endif</div>
                                    <span class="ops-sub">Nota {{ $order?->Note?->note ?? '—' }}</span>
                                </td>
                                <td><span class="ops-sub text-body">{{ $classifier::competenceLabel($state->competence ?? null) }}</span></td>
                                <td class="is-left">
                                    <div class="text-truncate fw-semibold" title="{{ $order?->Note?->lexp }}">{{ $order?->Note?->lexp ?: '—' }}</div>
                                    <span class="ops-sub text-truncate" title="{{ $order?->Note?->pep }}">{{ $order?->Note?->pep ?: '—' }}</span>
                                </td>
                                <x-closure.order-cost :planned="$order?->custPlanejado" :actual="$order?->custRealizado" />
                                <td><x-ops.badge tone="muted" mono :label="$this->sapHeadline($order?->statusSist)" :hint="'Status do sistema (SAP): ' . ($order?->statusSist ?: '—')" /></td>
                                <td><button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click="showOperations({{ $state->id }})" title="Ver as operações da Ordem" aria-label="Ver operações da Ordem {{ $order?->ordem }}">{{ $conf }}/{{ $ops->count() }}</button></td>
                                <td class="is-left">
                                    <div class="ops-person">
                                        <span class="ops-avatar" aria-hidden="true">{{ $holder ? $initials($holder) : '—' }}</span>
                                        <div class="min-w-0">
                                            <div class="text-truncate fw-semibold" title="{{ $holder ?? 'Sem responsável' }}">{{ $holder ?? 'Sem responsável' }}</div>
                                            <span class="ops-sub text-truncate">{{ $state->CurrentHolderCompany?->name ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="is-left">
                                    <div class="ops-cell-stack">
                                        <x-ops.badge :tone="\App\Enum\ClosureOperationalStatus::toneFor($state->operational_status)" :label="\App\Enum\ClosureOperationalStatus::labelFor($state->operational_status)" :hint="$presenter::statusHint($state->operational_status)" />
                                        <span class="ops-sub">{{ $presenter::statusHint($state->operational_status) }}</span>
                                        @if ($deposit)
                                            <x-ops.badge sm :tone="$deposit['tone']" :icon="$deposit['locked'] ? 'ri-lock-line' : 'ri-error-warning-line'" :label="$deposit['label']" :hint="$deposit['hint']" />
                                        @endif
                                        @if ($ri)
                                            <x-ops.badge sm tone="red" icon="ri-lock-line" label="RI com o Desenho" :hint="'Retorno interno enviado em ' . $ri->sent_at?->format('d/m/Y') . '. As Ordens da obra ficam travadas até o Desenho concluir.'" />
                                        @endif
                                    </div>
                                </td>
                                <td><span class="ops-sub text-body" title="{{ $this->holderLabel($state) }}{{ $since ? ' · desde ' . $since->format('d/m/Y H:i') : '' }}">{{ $this->elapsedLabel($state) }}</span></td>
                                <td>@include('livewire.closure.dispatch._control-actions')</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$states" :per-page="$perPage" :sizes="\App\Http\Livewire\Closure\Work\Index::PER_PAGE_OPTIONS" />
    </section>

    @include('livewire.closure.dispatch._selection-bar')

    @include('livewire.closure.dispatch._control-batch-modal')
    @include('livewire.closure.dispatch._control-modals')

    @livewire('closure.internal-returns.modal', [], key('closure-ri-modal'))
</div>
