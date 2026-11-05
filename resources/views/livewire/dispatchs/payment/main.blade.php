@php
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($typeNote) || $not_assigned || $filter_d5 || !empty($multiSearch);
@endphp

{{-- Lista operacional: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, tipo, atalhos, filtros) → painel com a tabela. --}}
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
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou ordem" label="Buscar nota ou ordem">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="paymentTypeNote" label="Tipo de nota" wire:model="typeNote" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <x-ops.toggle label="Só livres" icon="ri-user-unfollow-line" :on="$not_assigned" wire:click.prevent="filterStatus" title="Mostra só o que ainda não foi atribuído a ninguém" />
            <x-ops.toggle label="Somente D5" icon="ri-file-list-3-line" :on="$filter_d5" wire:click.prevent="filterD5" title="Mostra só notas que têm D5" />

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
                        <col style="width: 76px">
                        <col style="width: 124px">
                        <col style="width: 104px">
                        <col style="width: 108px">
                        <col style="width: 140px">
                        <col style="width: 76px">
                        <col style="width: 128px">
                        <col style="width: 92px">
                        <col style="width: 108px">
                        <col style="width: 92px">
                        <col style="width: 92px">
                        <col style="width: 124px">
                        <col style="width: 104px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectall" wire:click="setSelectAll" @checked($this->checkAllSelect($lists)) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Ordem</th>
                            <th>Valor ADS</th>
                            <th>Operações 30 · 40 · 50</th>
                            <th>Centro</th>
                            <th class="is-left">Empresa</th>
                            <th>Final OP20</th>
                            <th>Saldo PEP</th>
                            <th>ADS</th>
                            <th>Fiscalizado</th>
                            <th>Vencimento</th>
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
                                    <div class="ops-cell-stack is-center">
                                        <x-ops.badge :tone="$row['isPartial'] ? 'amber' : 'green'" :label="$row['isPartial'] ? 'Parcial' : 'Final'" :hint="$row['isPartial'] ? 'Informe parcial' : 'Informe final'" />
                                        @foreach ($row['scopes'] as $scope)
                                            <x-ops.badge sm :tone="$scope['tone']" :label="$scope['label']" hint="Escopo do informe final" />
                                        @endforeach
                                    </div>
                                </td>

                                <td class="copy-text" data-value="{{ $row['note'] }}">
                                    <div class="ops-cell-stack is-center">
                                        @if ($row['d5'])
                                            <x-ops.badge class="ops-note-badge ops-clickable" :tone="$row['d5']['tone']" :hint="$row['d5']['hint']"><strong>D5</strong> {{ $row['note'] }}</x-ops.badge>
                                        @else
                                            <span class="ops-note">{{ $row['note'] }}</span>
                                        @endif
                                        @if ($row['workReportId'])
                                            <span class="ops-sub">Informe #{{ $row['workReportId'] }}</span>
                                        @endif
                                        @if ($row['partialId'])
                                            <span class="ops-sub">Parcial #{{ $row['partialId'] }}</span>
                                        @endif
                                        <x-work-report.canceled-badge :report="$row['productionId'] ? ($canceledByProduction[$row['productionId']] ?? null) : null" />
                                        <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-payment-main-' . $row['key']" />
                                    </div>
                                </td>

                                <td><div class="ops-cell-stack is-center">@forelse ($row['orders'] as $order)<span class="fw-semibold">{{ $order['ordem'] }}</span>@empty<span class="ops-empty">—</span>@endforelse</div></td>

                                <td><x-ops.money :value="$row['adsAmount']" /></td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        @forelse ($row['orders'] as $order)
                                            <span class="d-inline-flex gap-1">
                                                @foreach (['op30', 'op40', 'op50'] as $op)
                                                    @if ($order[$op])
                                                        <x-ops.badge sm mono tone="muted" :label="$order[$op]" :hint="'Operação 00' . substr($op, 2)" />
                                                    @else
                                                        <span class="ops-empty ops-badge ops-badge--sm">—</span>
                                                    @endif
                                                @endforeach
                                            </span>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </div>
                                </td>

                                <td><div class="ops-cell-stack is-center">@forelse ($row['orders'] as $order)<span>{{ $order['center'] ?? '—' }}</span>@empty<span class="ops-empty">—</span>@endforelse</div></td>

                                <td class="is-left">
                                    <span class="fw-semibold d-block text-truncate" title="{{ $row['company'] }}">{{ $row['company'] ?: '—' }}</span>
                                    <span class="ops-sub text-truncate">{{ $row['city'] ?: '' }}</span>
                                </td>

                                <td><x-ops.date :date="$row['finalOp20']" /></td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        @forelse ($row['orders'] as $order)
                                            <span class="ops-clickable" @if ($order['pep']) wire:dblclick="$emit('openPepFinancialModal', '{{ $order['pep'] }}')"  title="Duplo clique para consultar o PEP {{ $order['pep'] }}" @endif>
                                                <x-ops.money :value="$order['saldo']" signed />
                                            </span>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td><x-ops.date :date="$row['adsAt']" :tone="$row['adsTone']" :sub="$row['adsLabel']" :hint="$row['adsHint']" /></td>
                                <td><x-ops.date :date="$row['fiscalAt']" :tone="$row['fiscalTone']" :hint="$row['isPartial'] ? 'Fiscalização da parcial + 5 dias (verde com 5 dias ou mais, âmbar abaixo disso, vermelho vencido)' : 'Data da fiscalização'" /></td>
                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['dueLabel']" :hint="$row['dueHint']" /></td>

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', @js($row['payload']))"
                                            title="{{ $row['stackOpen'] ? 'Assumir ou atribuir a Nota/OV da pilha da empresa' : 'Atribuir a um responsável' }}">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                                        </button>
                                        @if ($row['command'] && $row['assignee'])
                                            <span class="ops-sub mt-1">{{ $row['assignee'] }}</span>
                                        @endif
                                    @else
                                        <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['assignee'] ?? \App\Support\Ops\RowState::label($row['state'])" :hint="\App\Support\Ops\RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="is-right">Total da página</td>
                            <td><x-ops.money :value="$rows->sum('adsAmount')" /></td>
                            <td colspan="10"></td>
                        </tr>
                    </tfoot>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="advanceSearch">Notas ou ordens</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusPayment" wire:model="bulkSearchAnyStatus">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusPayment">Buscar em qualquer status</label>
                    </div>

                    @if ($bulkSearchAnyStatus)
                        <div class="ops-warning mt-3">Você vai ignorar o filtro de status da lista. A busca continua respeitando as regras de contrato e de acesso.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger btn-dash" wire:click="clean">Limpar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-' . $service->uuid))
    @livewire('components.workform.canceled-info', key('dispatch-payment-canceled-info'))
    @livewire('components.pep.financial-modal', key('dispatch-payment-pep-financial-modal'))
</div>
