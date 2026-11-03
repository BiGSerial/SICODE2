@php
    use App\Support\Ops\RowState;

    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || $not_assigned || $assigned_mmgd || !empty($multiSearch);
    $canManage = auth()->user()->can('management');
    $widths = $canManage
        ? [38, 120, 70, 92, 84, 108, 96, 120, 96, 130, 76, 96, 70, 104, 108, 100, 112]
        : [38, 120, 70, 92, 84, 108, 96, 120, 96, 76, 96, 70, 104, 108, 100, 112];
@endphp

{{-- Lista operacional (Serviços, auto-atribuição): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas → painel com a tabela. --}}
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
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota" label="Buscar nota">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.toggle label="Só livres" icon="ri-user-unfollow-line" :on="$not_assigned" wire:click.prevent="filterStatus" title="Mostra só o que ainda não foi atribuído a ninguém" />
            <x-ops.toggle label="Somente MMGD" icon="ri-sun-line" :on="$assigned_mmgd" wire:click.prevent="filterMMGD" title="Mostra só notas de micro e minigeração distribuída" />

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
                            <th><input class="form-check-input" type="checkbox" wire:model="selectAll" wire:click="setSelectAllFiltered" @checked($selectAll) aria-label="Selecionar todas as notas do filtro"></th>
                            <th>Nota</th>
                            <th>MMGD</th>
                            <th>Criada em</th>
                            <th>Retorno</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th class="is-left">Município</th>
                            @if ($canManage)
                                <th>Grp1</th>
                            @endif
                            <th>Grp2</th>
                            @if ($canManage)
                                <th class="is-left">Descrição</th>
                            @endif
                            <th>Status</th>
                            <th>Pze</th>
                            <th>Data do status</th>
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

                                <td><x-ops.note :note="$row['note']" /></td>

                                <td>
                                    @if ($row['mmgd'])
                                        <x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td><x-ops.date :date="$row['dtCreated']" /></td>

                                <td>
                                    @if ($row['returns'] > 0)
                                        <x-ops.badge sm tone="amber" :label="$row['returns']" hint="Quantas vezes esta Nota/OV voltou com inconsistência neste serviço" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>{{ $row['numPedido'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                @if ($canManage)
                                    <td>{{ $row['group1'] ?: '—' }}</td>
                                @endif
                                <td>{{ $row['group2'] ?: '—' }}</td>
                                @if ($canManage)
                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                @endif
                                <td>{{ $row['status'] ?: '—' }}</td>
                                <td>{{ $row['pze'] ?: '—' }}</td>
                                <td><x-ops.date :date="$row['dtStatus']" time /></td>
                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['elapsed'] !== null ? $row['elapsed'] . ' d' : null" :hint="\App\Services\Dispatch\PreAnalysisListRowPresenter::DEADLINE_HINT" /></td>
                                <td><x-ops.badge :tone="$row['parecer']['tone']" :label="$row['parecer']['label']" :hint="$row['parecer']['hint']" /></td>

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="to_accompany({{ $row['noteId'] }})"
                                            title="Enviar para o seu acompanhamento">
                                            <i class="ri-hand-coin-line" aria-hidden="true"></i> Assumir
                                        </button>
                                    @else
                                        <x-ops.badge :tone="RowState::tone($row['state'])" :label="$row['assignee'] ?? RowState::label($row['state'])" :hint="RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
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
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="go_att_mass" wire:loading.attr="disabled" wire:target="go_att_mass"><i class="ri-hand-coin-line" aria-hidden="true"></i> Assumir selecionadas</button>
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
                    <p class="ops-modal-note">Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula. Também vale o número do pedido.</p>
                    <label class="form-label text-uppercase" for="advanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-outline-danger btn-dash" wire:click="clean">Limpar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>
</div>
