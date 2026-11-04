@php
    $selectedCount  = count($selectedStateIds);
    $allSelected    = $states->count() > 0 && $states->every(fn ($st) => in_array($st->id, $selectedStateIds));
    $presenter      = \App\Services\Closure\ClosureDispatchRowPresenter::class;
    $loadingTargets = 'search,type,toggleCard,clearFilters,removeAnyFilter,applyListFilters,removeListFilter,clearListFilters,applyBulkSearch';
@endphp

{{-- Atividades disponíveis (Serviço): lista operacional para assumir Ordens da pilha (auto-atribuição). --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero eyebrow="Serviço · Encerramento" title="Atividade Encerramento" subtitle="Ordens disponíveis para você assumir. Ao assumir, a Ordem vai para a sua Minha atividade e sai desta lista para os demais.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            @if ($enabled)
                <x-ops.badge tone="green" icon="ri-user-follow-line" :label="'Auto-atribuição liberada (' . implode('/', $allowedTypes) . ')'" hint="Tipos que o despacho liberou para você assumir" />
            @endif
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'minhas']) }}" class="btn btn-light btn-sm btn-dash"><i class="ri-user-settings-line" aria-hidden="true"></i> Minha atividade</a>
            @if ($enabled)
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
            @endif
        </div>
    </x-ops.hero>

    @if (!$enabled)
        <section class="mp-panel" id="closure-available-disabled">
            <div class="mp-panel-body">
                <x-closure.empty-state icon="ri-lock-line" title="Escolha de atividades desabilitada" text="O despachante ainda não liberou a escolha de atividades de Encerramento. As Ordens chegam até você por despacho. Você será levado para Minha atividade em instantes." />
                <div class="text-center mt-2"><a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'minhas']) }}" class="btn btn-primary btn-sm btn-dash">Ir para Minha atividade agora</a></div>
            </div>
        </section>
        <script>
            setTimeout(function () { window.location.href = @json(\App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'minhas'])); }, 5000);
        </script>
    @else
        <div class="ops-stats">
            <x-ops.stat wire:key="vstat-all" wire:click="toggleCard('all')" tone="teal" icon="ri-inbox-line" label="Disponíveis" :value="number_format($states->count(), 0, ',', '.')" :active="!$onlyPriority" title="Ordens da pilha que você pode assumir" />
            <x-ops.stat wire:key="vstat-priority" wire:click="toggleCard('priority')" tone="red" icon="ri-flag-2-line" label="Prioritárias" :value="number_format($priorityCount, 0, ',', '.')" :active="$onlyPriority" title="Ordens marcadas como prioridade" />
        </div>

        <section class="ops-toolbar" aria-label="Filtros da lista">
            <div class="ops-toolbar-row">
                <x-ops.search wire:model.debounce.500ms="search" placeholder="Ordem ou nota" label="Buscar por Ordem ou Nota" />
                <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />
                <x-ops.segmented name="closureAvailType" label="Tipo de Ordem" wire:model="type" :options="['all' => 'Todos'] + array_intersect_key(['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura'], array_flip($allowedTypes))" />

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

        <section class="mp-panel" aria-label="Ordens disponíveis">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Ordens disponíveis</h2>
                    <div class="mp-panel-sub">{{ number_format($states->count(), 0, ',', '.') }} {{ $states->count() === 1 ? 'Ordem' : 'Ordens' }} na pilha da sua empresa, dos tipos liberados.</div>
                </div>
                <div class="ops-panel-actions">
                    <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleStates">{{ $allSelected ? 'Limpar visíveis' : 'Selecionar visíveis' }}</button>
                </div>
            </div>

            <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $states->count()))" :cols="8" />

            @if ($states->isEmpty())
                <div class="mp-panel-body">
                    <x-closure.empty-state :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Não há Ordens disponíveis'" :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : 'Não há Ordens disponíveis para você neste momento.'" icon="ri-inbox-2-line" />
                    @if ($hasCriteria)
                        <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                    @endif
                </div>
            @else
                <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                    <table class="table ops-table">
                        <colgroup>@include('livewire.closure.work._ops-order-cols', ['withSelect' => true])<col style="width: 120px"><col style="width: 110px"></colgroup>
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" @checked($allSelected) wire:click="toggleVisibleStates" aria-label="Selecionar ou limpar as Ordens visíveis"></th>
                                @include('livewire.closure.work._ops-order-head')
                                <th>Prioridade</th>
                                <th title="Há quanto tempo a Ordem está na pilha">Tempo na pilha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($states as $state)
                                <tr wire:key="closure-available-{{ $state->id }}" @class(['is-selected' => in_array($state->id, $selectedStateIds), 'ops-row-priority' => (bool) $state->priority, 'ops-row-stack' => !$state->priority])>
                                    <td><input type="checkbox" class="form-check-input" @checked(in_array($state->id, $selectedStateIds)) wire:click="toggleState({{ $state->id }})" aria-label="Selecionar Ordem {{ $state->Order?->ordem }}"></td>
                                    @include('livewire.closure.work._ops-order-cells')
                                    <td>@if ($state->priority)<x-ops.badge tone="red" icon="ri-flag-2-fill" label="Prioritária" hint="Marcada como prioridade pelo despacho" />@else<span class="ops-sub text-body">Normal</span>@endif</td>
                                    <td><span class="ops-sub text-body">{{ $state->entered_current_status_at?->diffForHumans(null, true) ?? '—' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($selectedCount > 0)
            <div class="ops-selection" id="closure-available-bulk">
                <div class="ops-selection-count"><strong>{{ $selectedCount }}</strong> {{ $selectedCount === 1 ? 'selecionada' : 'selecionadas' }}</div>
                <div class="ops-selection-actions">
                    <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="askClaim"><i class="ri-user-follow-line" aria-hidden="true"></i> Assumir selecionadas</button>
                </div>
                <button type="button" class="ops-selection-clear" wire:click="clearSelectionNow" aria-label="Limpar seleção"><i class="ri-close-line"></i></button>
            </div>
        @endif

        @if ($showReceipt && $receipt)
            <x-ops.modal title="Resultado da atribuição" :eyebrow="count($receipt['successes']) . ' assumida(s), ' . count($receipt['refusals']) . ' recusada(s)'" close="closeReceipt" size="modal-lg">
                <div class="row g-3" id="closure-available-receipt">
                    <div class="col-md-6">
                        <div class="ops-modal-card">
                            <div class="ops-modal-card-head tone-green"><i class="ri-checkbox-circle-line" aria-hidden="true"></i> Assumidas ({{ count($receipt['successes']) }})</div>
                            <div class="ops-modal-card-body"><ul class="mb-0 ps-3">@forelse ($receipt['successes'] as $o)<li>{{ $o }}</li>@empty<li class="text-muted">Nenhuma.</li>@endforelse</ul></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ops-modal-card">
                            <div class="ops-modal-card-head tone-red"><i class="ri-close-circle-line" aria-hidden="true"></i> Recusadas ({{ count($receipt['refusals']) }})</div>
                            <div class="ops-modal-card-body"><ul class="mb-0 ps-3">@forelse ($receipt['refusals'] as $r)<li><strong>{{ $r['order'] }}</strong>: {{ $r['reason'] }}</li>@empty<li class="text-muted">Nenhuma.</li>@endforelse</ul></div>
                        </div>
                    </div>
                </div>
                <x-slot name="footer"><button type="button" class="btn btn-primary" wire:click="closeReceipt">Fechar</button></x-slot>
            </x-ops.modal>
        @endif
    @endif
</div>
