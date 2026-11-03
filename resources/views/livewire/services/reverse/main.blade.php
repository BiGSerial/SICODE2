@php
    $manage = auth()->user()->can('management');
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || $not_assigned;
    $widths = array_merge([120, 100, 110, 110, 130], $manage ? [80] : [], [80], $manage ? [160] : [], [100, 130, 90, 100, 110]);
@endphp

{{-- Lista operacional de Serviços (auto-atribuição): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Fila de distribuição do fluxo reverso. Assuma as notas livres e acompanhe os prazos pela cor."
        :updated-at="$update">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou grupo" label="Buscar nota ou grupo" />

            <x-ops.toggle label="Só livres" icon="ri-user-unfollow-line" :on="$not_assigned" wire:click.prevent="filterStatus" title="Mostra só o que ainda não foi atribuído a ninguém" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="Lista para {{ $service->service }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Lista para {{ mb_strtolower($service->service) }}</h2>
                <div class="mp-panel-sub">
                    {{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}@if ($statusCodes) · status {{ $statusCodes }}@endif
                </div>
            </div>
            <span class="mp-pill" wire:loading.delay>Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Sem notas em ' . $service->service . ' no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Quando houver notas liberadas, elas aparecem aqui.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        @foreach ($widths as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Criado em</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th class="is-left">Município</th>
                            @if ($manage)
                                <th>Grp1</th>
                            @endif
                            <th>Grp2</th>
                            @if ($manage)
                                <th class="is-left">Descrição</th>
                            @endif
                            <th>Status</th>
                            <th>Data</th>
                            <th>Prazo real</th>
                            <th>Situação</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['key'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                <td>
                                    @if ($row['note'])
                                        <x-ops.note :note="$row['note']" />
                                    @else
                                        <span class="ops-empty" title="O número da nota só aparece para a gestão e para quem está com ela">—</span>
                                    @endif
                                </td>

                                <td><x-ops.date :date="$row['createdAt']" /></td>
                                <td>{{ $row['numPedido'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                @if ($manage)
                                    <td>{{ $row['group1'] ?: '—' }}</td>
                                @endif
                                <td>{{ $row['group2'] ?: '—' }}</td>
                                @if ($manage)
                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                @endif
                                <td>{{ $row['status'] ?: '—' }}</td>
                                <td><x-ops.date :date="$row['statusAt']" time /></td>
                                <td><x-ops.badge :tone="$row['deadline']['tone']" :label="$row['deadline']['label']" :hint="$row['deadline']['hint']" /></td>
                                <td><x-ops.badge :tone="$row['parecer']['tone']" :label="$row['parecer']['label']" :hint="$row['parecer']['hint']" /></td>

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="to_accompany({{ $row['noteId'] }})"
                                            title="Assumir esta Nota/OV: ela vai para o seu acompanhamento e ninguém mais pode atribuí-la">
                                            <i class="ri-hand-coin-line" aria-hidden="true"></i> Assumir
                                        </button>
                                    @else
                                        <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['assignee'] ?? \App\Support\Ops\RowState::label($row['state'])" :hint="\App\Support\Ops\RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$lists" :per-page="$perPage" />
    </section>
</div>
