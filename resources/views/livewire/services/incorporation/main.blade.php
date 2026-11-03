@php
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasCriteria();
@endphp

{{-- Lista operacional de Serviços: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, filtros) → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs disponíveis para você assumir. Quem já está com a nota aparece na última coluna."
        :updated-at="$updatedAt">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, pedido, material ou grupo 2" label="Buscar nota, pedido, material ou grupo 2" />

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

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="10" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Sem notas para exibir em ' . $service->service"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Quando houver notas liberadas, elas aparecem aqui.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
                @livewire('components.manualnote.manualnote', ['service' => $service->uuid], key('incorporation-manualnote'))
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        @can('management')
                            <col style="width: 120px">
                        @endcan
                        <col style="width: 104px">
                        <col style="width: 112px">
                        <col style="width: 110px">
                        <col style="width: 170px">
                        <col style="width: 92px">
                        <col style="width: 92px">
                        @can('management')
                            <col style="width: 240px">
                        @endcan
                        <col style="width: 76px">
                        <col style="width: 118px">
                        <col style="width: 70px">
                        <col style="width: 108px">
                        <col style="width: 130px">
                    </colgroup>
                    <thead>
                        <tr>
                            @can('management')
                                <th>Nota</th>
                            @endcan
                            <th>Criado em</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th class="is-left">Município</th>
                            <th>Grupo 1</th>
                            <th>Grupo 2</th>
                            @can('management')
                                <th class="is-left">Descrição</th>
                            @endcan
                            <th>Status</th>
                            <th>Data do status</th>
                            <th>Dias</th>
                            <th>Situação</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['id'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                @can('management')
                                    <td><x-ops.note :note="$row['note']" /></td>
                                @endcan
                                <td><x-ops.date :date="$row['createdAt']" hint="Data de criação da nota" /></td>
                                <td>{{ $row['order'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td class="is-left">{{ $row['city'] ?: '—' }}</td>
                                <td>{{ $row['group1'] ?: '—' }}</td>
                                <td>{{ $row['group2'] ?: '—' }}</td>
                                @can('management')
                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                @endcan
                                <td>{{ $row['status'] ?: '—' }}</td>
                                <td><x-ops.date :date="$row['statusAt']" time hint="Data e hora do status atual da nota" /></td>
                                <td title="Dias desde a criação da nota">{{ $row['age'] ?? '—' }}</td>
                                <td><x-ops.badge :tone="$row['parecer']['tone']" :label="$row['parecer']['label']" :hint="$row['parecer']['hint']" /></td>
                                <td>
                                    @if ($row['state'] === 'free')
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="to_accompany({{ $row['id'] }})"
                                            title="Enviar esta Nota/OV para o seu acompanhamento">
                                            <i class="ri-hand-coin-line" aria-hidden="true"></i> Assumir
                                        </button>
                                    @else
                                        <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['assignee']" :hint="\App\Support\Ops\RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
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
