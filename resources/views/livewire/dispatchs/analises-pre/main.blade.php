@php
    use App\Support\Ops\RowState;

    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || $not_assigned || !empty($multiSearch);
@endphp

{{-- Lista operacional: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, atalhos, filtros) → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="'Despacho · ' . $service->service . ($contractCompanyName ? ' · ' . $contractCompanyName : '')"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs prontas para atribuição. Marque as linhas, escolha o responsável e acompanhe os prazos pela cor."
        :updated-at="$update ? $last_update : null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, pedido ou material" label="Buscar nota, pedido ou material">
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
                        @foreach ([38, 132, 104, 112, 96, 128, 140, 76, 76, 96, 120, 110, 100, 116] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectall" wire:click="setSelectAll" @checked($this->checkAllSelect($lists)) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Nota</th>
                            <th>Data do status</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th class="is-left">Município</th>
                            <th class="is-left">Material</th>
                            <th>Grp1</th>
                            <th>Grp2</th>
                            <th>Retorno</th>
                            <th>Status</th>
                            <th>Prazo real</th>
                            <th>Situação</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['key'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                <td>
                                    <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['key'] }}" wire:model.defer="selected" @disabled(!$row['canDispatch']) aria-label="Selecionar {{ $row['note'] }}" @change="recount()">
                                </td>

                                <td>
                                    <x-ops.note :note="$row['note']">
                                        @if ($row['legal'])
                                            <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-analises-pre-main-' . $row['key']" />
                                        @endif
                                    </x-ops.note>
                                </td>

                                <td><x-ops.date :date="$row['dtStatus']" time /></td>
                                <td>{{ $row['numPedido'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                <td>{{ $row['group1'] ?: '—' }}</td>
                                <td>{{ $row['group2'] ?: '—' }}</td>

                                <td>
                                    @if ($row['returns'] > 0)
                                        <div class="ops-cell-stack is-center" title="Quantas vezes esta Nota/OV já passou por este serviço e quem foi o último responsável.">
                                            <x-ops.badge sm tone="muted" :label="$row['returns']" hint="Passagens anteriores por este serviço" />
                                            @if ($row['assignee'])
                                                <span class="ops-sub">{{ $row['assignee'] }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <span>{{ $row['status'] ?: '—' }}</span>
                                        @if ($row['statusSub'])
                                            <span class="ops-sub">{{ $row['statusSub'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['elapsed'] !== null ? $row['elapsed'] . ' d' : null" :hint="\App\Services\Dispatch\PreAnalysisListRowPresenter::DEADLINE_HINT" /></td>

                                <td><x-ops.badge :tone="$row['parecer']['tone']" :label="$row['parecer']['label']" :hint="$row['parecer']['hint']" /></td>

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $row['noteId'] }}])"
                                            title="{{ $row['stackOpen'] ? 'Assumir ou atribuir a Nota/OV da pilha da empresa' : 'Atribuir a um responsável' }}">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                                        </button>
                                        @if ($row['command'] && $row['assignee'])
                                            <span class="ops-sub mt-1">{{ $row['assignee'] }}</span>
                                        @endif
                                    @else
                                        <x-ops.badge :tone="RowState::tone($row['state'])" :label="$row['assignee'] ?? RowState::label($row['state'])" :hint="$row['reason'] ?? RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
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
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="go_att_mass"><i class="ri-user-add-line" aria-hidden="true"></i> Atribuir selecionadas</button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click.prevent="export_excel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar selecionadas</button>
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
                    <p class="ops-modal-note">Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula.</p>
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

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-' . $service->uuid))
</div>
