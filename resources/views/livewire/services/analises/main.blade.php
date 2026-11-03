@php
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || $not_assigned || !empty($multiSearch);
    $canManage = auth()->user()->can('management');
@endphp

{{-- Lista operacional de Serviços (auto-atribuição): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs para você assumir. Marque as linhas, assuma e acompanhe os prazos pela cor."
        :updated-at="$update ? $last_update : null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou grupo" label="Buscar nota ou grupo">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

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
                        <col style="width: 38px">
                        <col style="width: 124px">
                        <col style="width: 104px">
                        <col style="width: 108px">
                        <col style="width: 108px">
                        <col style="width: 100px">
                        @if ($canManage)
                            <col style="width: 80px">
                        @endif
                        <col style="width: 80px">
                        @if ($canManage)
                            <col style="width: 180px">
                        @endif
                        <col style="width: 110px">
                        <col style="width: 112px">
                        <col style="width: 112px">
                        <col style="width: 108px">
                        <col style="width: 112px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectAll" wire:click="setSelectAllFiltered" @checked($this->checkAllSelect()) aria-label="Selecionar todas as notas do filtro"></th>
                            <th>Nota</th>
                            <th>Criado em</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th>Município</th>
                            @if ($canManage)
                                <th>Grp1</th>
                            @endif
                            <th>Grp2</th>
                            @if ($canManage)
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
                                    <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['key'] }}" wire:model.defer="selected" aria-label="Selecionar {{ $row['note'] }}" @change="recount()">
                                </td>

                                <td>
                                    @if ($row['showNote'])
                                        <x-ops.note :note="$row['note']" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td><x-ops.date :date="$row['createdAt']" hint="Data de criação da Nota/OV" /></td>
                                <td>{{ $row['numPedido'] ?? '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td>{{ $row['city'] ?: '—' }}</td>
                                @if ($canManage)
                                    <td>{{ $row['group1'] ?: '—' }}</td>
                                @endif
                                <td>{{ $row['group2'] ?: '—' }}</td>
                                @if ($canManage)
                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                @endif
                                <td>{{ $row['nstats'] ?: '—' }}</td>
                                <td><x-ops.date :date="$row['dtStatus']" time hint="Data e hora do status atual da Nota/OV no SAP" /></td>
                                <td><x-ops.date :date="$row['deadline']['date']" :tone="$row['deadline']['tone']" :sub="$row['deadline']['sub']" :hint="$row['deadline']['hint']" /></td>
                                <td><x-ops.badge :tone="$row['sap']['tone']" :label="$row['sap']['label']" :hint="$row['sap']['hint']" /></td>

                                <td>
                                    @if ($row['canAssign'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="to_accompany({{ $row['noteId'] }})"
                                            title="Enviar para Acompanhamento: a Nota/OV passa a ser sua tarefa">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Assumir
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

    <div wire:ignore>
        <x-ops.selection-bar>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="go_att_mass"><i class="ri-user-add-line" aria-hidden="true"></i> Assumir selecionadas</button>
        </x-ops.selection-bar>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="buscarMultiLabel">Buscar várias notas</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota ou pedido por linha, ou separe por espaço, vírgula ou ponto e vírgula.</p>
                    <label class="form-label text-uppercase" for="advanceSearch">Notas ou pedidos</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>
</div>
