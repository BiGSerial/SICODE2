@php
    $partner = $partner ?? false;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || !empty($multiSearch);
    $legendStates = \App\Support\Ops\RowState::all();
@endphp

{{-- Lista operacional da atividade padrão: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="'Despacho · ' . $service->service . ($contractCompanyName ? ' · ' . $contractCompanyName : '')"
        :title="'Lista para ' . mb_strtolower($service->service)"
        :subtitle="$partner
            ? 'Notas e OVs da sua empresa prontas para atribuição. Marque as linhas, escolha o responsável e acompanhe a situação pela cor.'
            : 'Notas e OVs prontas para atribuição. Marque as linhas, escolha a empresa ou o responsável e acompanhe a situação pela cor.'"
        :updated-at="$update ? $last_update : null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, material ou pedido" label="Buscar nota, material ou pedido">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented :name="$partner ? 'partnerDefaultNoteType' : 'defaultNoteType'" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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
                        @foreach ([36, 112, 60, 96, 100, 100, 110, 76, 100, 76, 76, 96, 90, 64, 92] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                        @if ($partner)
                            @foreach ([92, 92, 92] as $w)
                                <col style="width: {{ $w }}px">
                            @endforeach
                        @endif
                        <col style="width: 112px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectall" wire:click="setSelectAll" @checked($this->checkAllSelect($lists)) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Nota</th>
                            <th>MMGD</th>
                            <th>Criado em</th>
                            <th>Pedido</th>
                            <th class="is-left">Rubrica</th>
                            <th class="is-left">Município</th>
                            <th>Grp1</th>
                            <th>Grp2</th>
                            <th>Grp4</th>
                            <th>Grp5</th>
                            <th>Retorno</th>
                            <th>Status</th>
                            <th>Prazo real</th>
                            <th>Situação</th>
                            @if ($partner)
                                <th>Despachado em</th>
                                <th>Atribuído em</th>
                                <th>Finalizado em</th>
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
                                        @if (isset($legalNoteIds[$row['noteId']]))
                                            <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-default-main-' . $row['key']" />
                                        @endif
                                    </x-ops.note>
                                </td>

                                <td>
                                    @if ($row['mmgd'])
                                        <x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td><x-ops.date :date="$row['createdAt']" hint="Data de criação da nota" /></td>
                                <td>{{ $row['numPedido'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['rubrica'] }}">{{ $row['rubrica'] ?: '—' }}</span></td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                <td>{{ $row['group1'] ?: '—' }}</td>
                                <td><span class="d-block text-truncate" title="{{ $row['group2'] }}">{{ $row['group2'] ?: '—' }}</span></td>
                                <td>{{ $row['group4'] ?: '—' }}</td>
                                <td>{{ $row['group5'] ?: '—' }}</td>

                                <td>
                                    @if ($row['returns'])
                                        <div class="ops-cell-stack is-center">
                                            <x-ops.badge sm tone="muted" :label="$row['returns']" hint="Quantas vezes esta nota já foi concluída nesta atividade" />
                                            <span class="ops-sub">{{ $row['lastUser'] }}</span>
                                        </div>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>{{ $row['status'] ?: '—' }}</td>
                                <td><span title="Prazo da atividade (30 dias; 45 para MMGD aéreo primário MT) menos os dias restantes da nota">{{ $row['realDays'] }}</span></td>
                                <td><x-ops.badge sm :tone="$row['parecer']['tone']" :label="$row['parecer']['label']" :hint="$row['parecer']['hint']" /></td>

                                @if ($partner)
                                    <td><x-ops.date :date="$row['dispatchAt']" time /></td>
                                    <td><x-ops.date :date="$row['attAt']" time /></td>
                                    <td><x-ops.date :date="$row['completedAt']" time /></td>
                                @endif

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="get_single_note({{ $row['noteId'] }})" title="Despachar esta nota ou OV">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Despachar
                                        </button>
                                        @if ($row['company'])
                                            <span class="ops-sub mt-1">{{ $row['company'] }}</span>
                                        @endif
                                    @else
                                        <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['company'] ?? \App\Support\Ops\RowState::label($row['state'])" :hint="$legendStates[$row['state']]['hint']" icon="ri-building-line" />
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
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="go_att_mass"><i class="ri-user-add-line" aria-hidden="true"></i> Despachar selecionadas</button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click.prevent="export_excel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar selecionadas</button>
        </x-ops.selection-bar>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel"><span class="modal-eyebrow">Busca</span> Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="advanceSearch">Notas ou ordens</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="add_mass_notes" tabindex="-1" aria-labelledby="addMassNotesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMassNotesLabel"><span class="modal-eyebrow">Despachar</span> {{ $service->service }}</h5>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar" wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="ops-modal-note">Escolha o tipo de despacho e a empresa. No despacho individual, escolha também o responsável.</div>

                        <div class="mb-3">
                            <label for="defaultDispatchType" class="form-label">Tipo de despacho</label>
                            <select id="defaultDispatchType" class="form-select form-select-sm" wire:model="type">
                                <option selected>Selecione</option>
                                @if ($service->partner_own_stack)
                                    <option value="1">Pilha</option>
                                @endif
                                <option value="2">Individual</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="defaultDispatchCompany" class="form-label">Empresa</label>
                            <select id="defaultDispatchCompany" class="form-select form-select-sm" wire:model="company_s">
                                <option selected>Selecione</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($type === '2')
                            <div class="mb-3">
                                <label for="defaultDispatchUser" class="form-label">Usuário</label>
                                <select id="defaultDispatchUser" class="form-select form-select-sm" wire:model="user_s">
                                    @if ($users->count())
                                        <option value="" selected>Selecione um usuário</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    @else
                                        <option selected>Escolha uma empresa primeiro</option>
                                    @endif
                                </select>
                            </div>
                        @endif

                        <div class="fw-bold mb-2">Despachando {{ $notes->count() }} {{ $notes->count() === 1 ? 'nota/OV' : 'notas/OVs' }}</div>
                        <div class="ops-table-wrap">
                            <table class="table ops-table">
                                <colgroup>
                                    <col style="width: 44px">
                                    <col style="width: 120px">
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nota</th>
                                        <th class="is-left">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr wire:key="dispatch-note-{{ $note->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td><span class="ops-note">{{ $note->note }}</span></td>
                                            <td class="is-left">{{ $note->material ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" wire:click.prevent="closeall">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="confirm_att" wire:loading.attr="disabled" wire:target="confirm_att">
                        <i class="ri-user-add-line" aria-hidden="true"></i> Despachar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
