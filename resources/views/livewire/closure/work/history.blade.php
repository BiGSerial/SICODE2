@php
    $presenter      = \App\Services\Closure\ClosureDispatchRowPresenter::class;
    $loadingTargets = 'search,type,gotoPage,nextPage,previousPage,clearFilters,removeAnyFilter,applyListFilters,removeListFilter,clearListFilters,applyBulkSearch';
@endphp

{{-- Meu histórico (Serviço): Ordens suas já finalizadas, aguardando o mês da meta, encerradas ou canceladas. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero eyebrow="Serviço · Encerramento" title="Meu histórico — Encerramento" subtitle="Ordens suas já finalizadas, aguardando o mês da meta, encerradas ou canceladas. O tipo mostrado é o congelado no encerramento.">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Ordem ou nota" label="Buscar por Ordem ou Nota" />
            <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />
            <x-ops.segmented name="closureHistType" label="Tipo de Ordem" wire:model="type" :options="['all' => 'Todos', 'P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura']" />

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

    <section class="mp-panel" aria-label="Meu histórico">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Histórico</h2>
                <div class="mp-panel-sub">{{ number_format($states->total(), 0, ',', '.') }} {{ $states->total() === 1 ? 'Ordem' : 'Ordens' }}, da mais recente para a mais antiga.</div>
            </div>
            <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $states->count()))" :cols="8" />

        @if ($states->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Nenhuma Ordem no seu histórico'" :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : 'Quando você finalizar Ordens, elas aparecem aqui.'" icon="ri-history-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                <table class="table ops-table">
                    <colgroup>@include('livewire.closure.work._ops-order-cols')<col style="width: 230px"><col style="width: 130px"><col style="width: 110px"></colgroup>
                    <thead>
                        <tr>
                            @include('livewire.closure.work._ops-order-head')
                            <th class="is-left">Situação</th>
                            <th title="Quando a Ordem entrou nesta situação">Desde</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($states as $state)
                            @php $signal = \App\Services\Closure\ClosureSignal::forState($state); @endphp
                            <tr wire:key="closure-history-{{ $state->id }}" @class(['ops-row-done' => $signal && $signal['key'] === 'done', 'ops-row-late' => $signal && $signal['key'] === 'overdue']) @if ($signal) data-signal="{{ $signal['key'] }}" title="{{ $signal['label'] }}: {{ $signal['hint'] }}" @endif>
                                @include('livewire.closure.work._ops-order-cells')
                                <td class="is-left">
                                    <div class="ops-cell-stack">
                                        <x-ops.badge :tone="\App\Enum\ClosureOperationalStatus::toneFor($state->operational_status)" :label="\App\Enum\ClosureOperationalStatus::labelFor($state->operational_status)" :hint="$presenter::statusHint($state->operational_status)" />
                                        <span class="ops-sub">{{ $presenter::statusHint($state->operational_status) }}</span>
                                    </div>
                                </td>
                                <td><x-ops.date :date="$state->entered_current_status_at" time hint="Quando a Ordem entrou nesta situação" /></td>
                                <td><a href="{{ route('closure.order.detail', ['order' => $state->order_id, 'ctx' => 'service']) }}" class="btn btn-outline-secondary btn-sm ops-action"><i class="ri-file-list-3-line" aria-hidden="true"></i> Ordem</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$states" />
    </section>
</div>
