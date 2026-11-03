@php
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || $not_assigned || !empty($multiSearch);
    $showDispatchedAt = ($partnerView ?? false) && request()->routeIs('dispatch.partner');
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
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, pedido ou material" label="Buscar nota, pedido ou material">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="surveyNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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
                        @foreach ([38, 128, 104, 96, 92, 88, 132, 60, 72, 72, 72, 104, 92, 108, 96] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                        @if ($showDispatchedAt)
                            <col style="width: 96px">
                        @endif
                        <col style="width: 104px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectall" wire:click="setSelectAll" aria-label="Selecionar todas as linhas da página"></th>
                            <th>Nota</th>
                            <th>DD</th>
                            <th>Criada em</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th class="is-left">Município</th>
                            <th>Grp1</th>
                            <th>Grp2</th>
                            <th>Grp4</th>
                            <th>Grp5</th>
                            <th>Levantamentos</th>
                            <th>Status</th>
                            <th>Prazo real</th>
                            <th>Situação</th>
                            @if ($showDispatchedAt)
                                <th>Despachado em</th>
                            @endif
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
                                        @if ($row['express'])
                                            <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" hint="Nota expressa: prazo de execução de 45 dias" />
                                        @endif
                                        @foreach ($row['informes'] as $informe)
                                            <span class="ops-sub">{{ $informe }}</span>
                                        @endforeach
                                        @if (isset($legalNoteIds[$row['noteId']]))
                                            <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-survey-main-' . $row['key']" />
                                        @endif
                                    </x-ops.note>
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        @if ($row['dd'])
                                            <span class="fw-semibold">{{ $row['dd'] }}</span>
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                        @if ($row['mmgd'])
                                            <x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />
                                        @endif
                                    </div>
                                </td>

                                <td><x-ops.date :date="$row['createdAt']" hint="Data de criação da nota" /></td>
                                <td>{{ $row['numPedido'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>

                                <td class="is-left">
                                    @if (filled($row['city']))
                                        <span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] }}</span>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary btn-sm" title="Informar o município que falta nesta nota" wire:click.prevent="$emit('editMunicipio', '{{ $row['id'] }}')">
                                            <i class="ri-edit-2-line" aria-hidden="true"></i> Editar
                                        </button>
                                    @endif
                                </td>

                                @foreach (['g1', 'g2', 'g4', 'g5'] as $group)
                                    <td>{{ $row['groups'][$group] ?: '—' }}</td>
                                @endforeach

                                <td>
                                    @if ($row['doneCount'])
                                        <div class="ops-cell-stack is-center">
                                            <x-ops.badge sm tone="blue" :label="$row['doneCount'] . 'x'" hint="Quantas vezes esta nota já passou por este serviço e quem foi a última pessoa a encerrá-la" />
                                            <span class="ops-sub">{{ $row['lastUser'] }}</span>
                                        </div>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <span>{{ $row['nstats'] ?: '—' }}</span>
                                        @if ($row['centerjob'])
                                            <span class="ops-sub">{{ $row['centerjob'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <x-ops.date :date="$row['deadline']['date']" :tone="$row['deadline']['tone']"
                                        :sub="$row['deadline']['overdue'] ? 'Vencido' : $row['deadline']['elapsed'] . ' d'"
                                        hint="Prazo real, já descontados os status não contabilizáveis. Verde: 10 dias ou mais para vencer. Âmbar: de 6 a 9 dias. Vermelho: menos de 6 dias ou vencido." />
                                </td>

                                <td><x-ops.badge :tone="$row['pze']['tone']" :label="$row['pze']['label']" :hint="$row['pze']['hint']" /></td>

                                @if ($showDispatchedAt)
                                    <td><x-ops.date :date="$row['dispatchAt']" time hint="Data em que foi despachada" /></td>
                                @endif

                                <td title="{{ $row['reason'] }}">
                                    @can('operator')
                                        @if ($row['canDispatch'])
                                            <button type="button" class="btn btn-primary btn-sm ops-action"
                                                wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $row['id'] }}])"
                                                title="{{ $row['stackOpen'] ? 'Assumir ou atribuir a Nota/OV da pilha da empresa' : 'Atribuir a um responsável' }}">
                                                <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                                            </button>
                                        @else
                                            <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['assignee']" :hint="$row['assigneeCo'] . ' · ' . \App\Support\Ops\RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
                                            <span class="ops-sub mt-1">{{ $row['assigneeCo'] }}</span>
                                        @endif
                                    @endcan
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
                        <span class="modal-eyebrow">Busca</span>
                        <h5 class="modal-title" id="buscarMultiLabel">Buscar em massa</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula.</p>
                    <label class="form-label" for="advanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch"></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusSurvey" wire:model="bulkSearchAnyStatus">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusSurvey">Buscar em qualquer status</label>
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

    <div wire:ignore.self class="modal fade ops-modal" id="add_mass_dds" tabindex="-1" aria-labelledby="addMassDdsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Associar DD</span>
                        <h5 class="modal-title" id="addMassDdsModalLabel">Nota/OV e DD em {{ $service->service }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar" wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">
                        Informe a <strong>Nota/OV</strong> e a <strong>DD</strong> na mesma linha, uma associação por linha, somente números.
                        Separadores aceitos: espaço, TAB, ponto e vírgula ou vírgula.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <x-ops.badge mono tone="muted" label="4001123232 14034330" />
                        <x-ops.badge mono tone="muted" label="4001123232;14034330" />
                        <x-ops.badge mono tone="muted" label="4001123232,14034330" />
                    </div>
                    <p class="ops-modal-note">
                        A associação informada aqui é soberana: se a DD já estiver em outra Nota/OV, o sistema move a DD para a Nota/OV informada.
                        Se já estiver na própria Nota/OV, o vínculo é mantido.
                    </p>

                    <label for="surveyMassDdInput" class="form-label">Notas/OVs e DDs</label>
                    <textarea class="form-control" id="surveyMassDdInput" rows="10"
                        placeholder="4001123232 14034330&#10;4001123233 14034331&#10;4001123234 14034332"
                        wire:model.defer="enter_dd"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" wire:click.prevent="closeall">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger btn-dash" wire:click.prevent="$set('enter_dd', '')">Limpar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="mass_modal"><i class="ri-links-line" aria-hidden="true"></i> Associar DD</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-' . $service->uuid))
</div>
