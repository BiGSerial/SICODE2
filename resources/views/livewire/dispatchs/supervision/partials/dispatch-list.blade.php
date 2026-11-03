@php
    $partner = $partner ?? false;
    $showPartnerDates = $partner && ($showPartnerDates ?? false);
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($typeNote) || $not_assigned || $filter_d5 || !empty($multiSearch);
@endphp

{{-- Lista operacional da Fiscalização (Despacho e Parceira): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, tipo, atalhos, filtros) → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="'Despacho · ' . $service->service . ($contractCompanyName ? ' · ' . $contractCompanyName : '')"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Informes, parciais e D5 prontos para fiscalização. Marque as linhas, escolha o responsável e acompanhe os prazos pela cor."
        :updated-at="$lastUpdate">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            <button type="button" class="btn btn-light btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#add_mass_dds">
                <i class="ri-links-line" aria-hidden="true"></i> Associar DD
            </button>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, ordem, pedido…" label="Buscar nota, ordem ou pedido">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="supervisionTypeNote" label="Tipo de nota" wire:model="typeNote" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Quando houver informes liberados para fiscalização, eles aparecem aqui.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        @foreach ($showPartnerDates ? [34, 64, 112, 88, 76, 60, 50, 88, 96, 80, 70, 96, 86, 88, 80, 52, 76, 88, 76] : [34, 66, 118, 92, 80, 64, 52, 92, 100, 84, 76, 100, 90, 92, 84, 56, 80, 96] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model.defer="selectAll" wire:click="setSelectAll" @checked($this->checkAllSelect($lists)) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Ordem</th>
                            <th>DD</th>
                            <th>MMGD</th>
                            <th>Postes</th>
                            <th>Informado em</th>
                            <th>Dt ADS</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th>Município</th>
                            <th>{{ $partner ? 'Valor ADS' : 'Custo' }}</th>
                            <th>Fiscalizações</th>
                            <th>Status</th>
                            <th>Dias D5</th>
                            <th>Situação</th>
                            @if ($showPartnerDates)<th>Despachado em</th>@endif
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="supervision-row-{{ $row['key'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
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

                                <td>
                                    @if ($row['d5'])
                                        <x-ops.note :note="$row['note']" kind="d5" :tone="$row['d5']['tone']" :hint="$row['d5']['hint']" :sub="$row['workReportId'] ? 'Informe #' . $row['workReportId'] : null">
                                            @if ($row['express'])
                                                <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" :hint="$row['express']" />
                                            @endif
                                            <x-legal.note-demand-tags :demands="$row['legal']" :row-key="'dispatchs-supervision-main-' . $row['key']" />
                                        </x-ops.note>
                                    @else
                                        <x-ops.note :note="$row['note']" :sub="$row['workReportId'] ? 'Informe #' . $row['workReportId'] : null">
                                            @if ($row['express'])
                                                <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" :hint="$row['express']" />
                                            @endif
                                            <x-legal.note-demand-tags :demands="$row['legal']" :row-key="'dispatchs-supervision-main-' . $row['key']" />
                                        </x-ops.note>
                                    @endif
                                </td>

                                <td><div class="ops-cell-stack is-center">@forelse ($row['orders'] as $order)<span class="fw-semibold">{{ $order }}</span>@empty<span class="ops-empty">—</span>@endforelse</div></td>

                                <td>@if ($row['dd'])<span class="fw-semibold">{{ $row['dd'] }}</span>@else<span class="ops-empty">—</span>@endif</td>

                                <td>@if ($row['mmgd'])<x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />@else<span class="ops-empty">—</span>@endif</td>

                                <td>@if ($row['postes'] !== null)<span class="fw-semibold">{{ $row['postes'] }}</span>@else<span class="ops-empty">—</span>@endif</td>

                                <td>
                                    <x-ops.date :date="$row['informedAt']" :tone="$row['informedTone']"
                                        :sub="$row['informedDays'] !== null ? $row['informedDays'] . ' dia(s)' : null"
                                        hint="Dias desde o informe (verde até 20 dias, âmbar de 21 a 27, vermelho a partir de 28)" />
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <x-ops.date :date="$row['adsAt']"
                                            :sub="$row['adsAfter'] !== null ? $row['adsAfter'] . ' dia(s)' : null"
                                            :hint="$row['adsAt'] ? 'Entrega da ADS em ' . $row['adsAt']->format('d/m/Y H:i:s') . ($row['adsAfter'] !== null ? ' · ' . $row['adsAfter'] . ' dia(s) após o informe' : '') : null" />
                                        @if ($row['adsTacit'])
                                            <x-ops.badge sm tone="amber" label="Tácito" hint="ADS entregue de forma tácita" />
                                        @endif
                                    </div>
                                </td>

                                <td>@if ($row['numPedido'])<span>{{ $row['numPedido'] }}</span>@else<span class="ops-empty">—</span>@endif</td>

                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['rubrica'] }}">{{ $row['rubrica'] ?: '—' }}</span></td>

                                <td class="is-left">
                                    @if (!empty($row['city']))
                                        <span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] }}</span>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click.prevent="$emit('editMunicipio', '{{ $row['noteId'] }}')" title="Informar o município que falta nesta nota">
                                            <i class="ri-edit-line" aria-hidden="true"></i> Informar
                                        </button>
                                    @endif
                                </td>

                                <td><x-ops.money :value="$row['amount']" /></td>

                                <td>
                                    @if ($row['passes'])
                                        <div class="ops-cell-stack is-center" title="Quantas vezes esta Nota/OV já passou por esta atividade e quem a encerrou por último">
                                            <x-ops.badge sm tone="muted" :label="$row['passes'] . 'x'" hint="Vezes que passou por esta atividade" />
                                            <span class="ops-sub">{{ $row['lastBy'] }}</span>
                                        </div>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <span class="fw-semibold">{{ $row['status'] ?: '—' }}</span>
                                        @if ($row['center'])<span class="ops-sub">{{ $row['center'] }}</span>@endif
                                    </div>
                                </td>

                                <td>
                                    @if ($row['d5Days'] !== null)
                                        <x-ops.badge sm tone="teal" :label="$row['d5Days'] . ' d'" hint="Dias desde que a D5 foi concluída" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td><x-ops.badge sm :tone="$row['deadline']['tone']" :label="$row['deadline']['label']" :hint="$row['deadline']['hint']" /></td>

                                @if ($showPartnerDates)
                                    <td>
                                        <x-ops.date :date="$row['dispatchedAt']"
                                            :sub="$row['dispatchedAt'] ? $row['dispatchedAt']->copy()->startOfDay()->diffInDays(now()->startOfDay()) . ' dia(s)' : null"
                                            hint="Data em que a atividade foi despachada para a empreiteira e dias desde então" />
                                    </td>
                                @endif

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', '{{ $row['event']['name'] }}', @js($row['event']['payload']))"
                                            title="{{ $row['stackOpen'] ? 'Assumir ou atribuir a Nota/OV da pilha da empresa' : 'Atribuir a um responsável' }}">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                                        </button>
                                    @else
                                        <div class="ops-cell-stack is-center">
                                            <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['assignee']" :hint="$row['reason']" icon="ri-user-line" />
                                            <span class="ops-sub">{{ $row['assigneeCompany'] }}</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="12" class="is-right">Total da página</td>
                            <td><x-ops.money :value="$rows->sum('amount')" /></td>
                            <td colspan="{{ $showPartnerDates ? 6 : 5 }}"></td>
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
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <h1 class="modal-title" id="buscarMultiLabel">
                        <span class="modal-eyebrow">Busca</span>
                        Buscar em massa
                    </h1>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="advanceSearch">Notas ou ordens</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusSupervision" wire:model="bulkSearchAnyStatus">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusSupervision">Buscar em qualquer status</label>
                    </div>

                    @if ($bulkSearchAnyStatus)
                        <div class="ops-warning mt-3">Você vai ignorar o filtro de status da lista. A busca continua respeitando as regras de contrato e de acesso.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-outline-danger" wire:click="clean">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="add_mass_dds" tabindex="-1" aria-labelledby="addMassDdsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <h1 class="modal-title" id="addMassDdsModalLabel">
                        <span class="modal-eyebrow">Associar Nota/OV e DD</span>
                        {{ $service->service }}
                    </h1>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar" wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <div class="ops-modal-note">
                        Informe <strong>Nota/OV</strong> e <strong>DD</strong> na mesma linha, uma associação por linha, só com números. Separadores aceitos: espaço, TAB, ponto e vírgula ou vírgula.
                        Exemplos: <code>4001123232 14034330</code>, <code>4001123232;14034330</code>, <code>4001123232,14034330</code>.
                        A associação informada aqui é soberana: se a DD já estiver em outra Nota/OV, o sistema move a DD para a Nota/OV informada. Se já estiver na própria Nota/OV, o vínculo é mantido.
                    </div>

                    <label for="supervisionMassDdInput" class="form-label">Notas/OVs e DDs</label>
                    <textarea class="form-control" id="supervisionMassDdInput" rows="10"
                        placeholder="4001123232 14034330&#10;4001123233 14034331&#10;4001123234 14034332"
                        wire:model.defer="enter_dd"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click.prevent="closeall">Fechar</button>
                    <button type="button" class="btn btn-outline-danger" wire:click.prevent="$set('enter_dd', '')">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="mass_modal"><i class="ri-links-line" aria-hidden="true"></i> Associar DD</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-' . $service->uuid))
</div>
