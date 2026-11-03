@php
    use App\Custom\Notestatus;
    use App\Support\Ops\Tone;

    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || !empty($multiSearch);
    $statusFilter = $this->filterValues('status');
    $totalOpen = array_sum($statusCounts);
@endphp

{{-- LISTA DE CONTROLE (acompanha o que já foi despachado). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
     Não confundir com a lista operacional (Lista para Análises Pré), onde se despacha. --}}
<div class="closure-dash my-panel ops-list ops-control" x-data="opsSelection()">

    <x-ops.hero
        class="ops-hero--control"
        :eyebrow="'Controle · ' . $service->service"
        :title="'Controle de ' . mb_strtolower($service->service)"
        subtitle="Acompanhamento do que já foi despachado: quem está com cada nota, há quantos dias e como está o prazo."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <span class="ops-mode-pill" title="Esta é uma lista de controle: aqui não se despacha, só se acompanha e se corrige."><i class="ri-radar-line" aria-hidden="true"></i> Lista de controle</span>
            <a class="btn btn-light btn-sm btn-dash" href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"><i class="ri-arrow-left-right-line" aria-hidden="true"></i> Ir para a Lista para {{ $service->service }}</a>
        </div>
    </x-ops.hero>

    {{-- Painel de situação (discreto): os cartões de status são filtros. --}}
    <section class="ops-stats" aria-label="Situação do controle">
        <x-ops.stat tone="teal" label="Em controle" :value="number_format($totalOpen, 0, ',', '.')" icon="ri-stack-line" :active="false" disabled />
        @foreach ([1, 2, 4, 5] as $st)
            <x-ops.stat :tone="Tone::forProductionStatus($st)" :label="Notestatus::status($st)->status" :value="number_format($statusCounts[$st] ?? 0, 0, ',', '.')"
                :icon="Notestatus::status($st)->icon" :active="in_array((string) $st, $statusFilter, true)" wire:click.prevent="toggleStatus({{ $st }})" wire:key="stat-{{ $st }}"
                :title="'Filtrar por ' . Notestatus::status($st)->status" />
        @endforeach
    </section>

    <section class="ops-toolbar" aria-label="Filtros do controle">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, rubrica, pedido…" label="Buscar no controle">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="preControlNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
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

    <section class="mp-panel" aria-label="Controle de {{ $service->service }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Controle de {{ mb_strtolower($service->service) }}</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="mp-pill" wire:loading.delay>Atualizando…</span>
                @include('livewire.dispatchs.analises-pre.partials.stack-mass-actions')
                <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                    <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                </button>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum registro para os filtros escolhidos' : 'Nada em controle no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais registros.' : 'Quando uma nota for despachada, ela passa a ser acompanhada aqui.'"
                    icon="ri-radar-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        @foreach ([38, 144, 72, 96, 76, 124, 84, 140, 92, 124, 88, 88, 100, 96, 48] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectAll" wire:click="setSelectAll()" @checked($this->checkAllSelect($lists)) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Nota</th>
                            <th>Grp2</th>
                            <th>Rubrica</th>
                            <th>Centro</th>
                            <th class="is-left">Município</th>
                            <th>Zona</th>
                            <th class="is-left">Descrição</th>
                            <th>Empresa</th>
                            <th class="is-left">Usuário</th>
                            <th>Despachado</th>
                            <th>Atribuído</th>
                            <th>Prazo real</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="line-{{ $row['id'] }}" @class(['ops-row-priority' => $row['priority']])>
                                <td><input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['id'] }}" wire:model.defer="selected" aria-label="Selecionar {{ $row['note'] }}" @change="recount()"></td>

                                <td>
                                    @if ($row['isRi'])
                                        <x-ops.note :note="$row['note']" kind="ri" tone="blue" hint="Retorno Interno – clique para ver"
                                            wire:click.prevent="$emitTo('dispatchs.common.reclaim-info', 'getInfoByProduction', {{ $row['id'] }})">
                                            @if ($row['priority'])
                                                <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
                                                    wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})" />
                                            @endif
                                        </x-ops.note>
                                    @else
                                        <x-ops.note :note="$row['note']">
                                            @if ($row['priority'])
                                                <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
                                                    wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})" />
                                            @endif
                                            @if ($row['legal'])
                                                <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-analises-pre-' . $row['id']" />
                                            @endif
                                        </x-ops.note>
                                    @endif
                                </td>

                                <td>{{ $row['group2'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td>{{ $row['centerjob'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                <td>{{ $row['group1'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['description'] }}">{{ $row['description'] ?: '—' }}</span></td>
                                <td>{{ $row['company'] ?: '—' }}</td>
                                <td class="is-left"><span class="fw-semibold d-block text-truncate"><x-user.status-name :user="$row['user']" /></span></td>

                                <td><x-ops.age :date="$row['dispatchAt']" :days="$row['dispatchDays']" rule="dispatch" /></td>
                                <td><x-ops.age :date="$row['attAt']" :days="$row['attDays']" rule="assigned" /></td>
                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['elapsed'] !== null ? $row['elapsed'] . ' d' : null" :hint="\App\Services\Dispatch\PreAnalysisListRowPresenter::DEADLINE_HINT" /></td>

                                <td>
                                    <x-ops.badge class="ops-clickable" :tone="$row['statusTone']" :label="$row['statusLabel']" hint="Clique para ver o histórico de status"
                                        wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['statusCode'] }})" />
                                </td>

                                <td>
                                    <div class="dropdown ops-row-actions">
                                        <button class="btn btn-outline-secondary btn-sm ops-row-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações da linha"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($row['statusCode'] == 1)
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="get_single_note({{ $row['id'] }})"><i class="ri-user-shared-fill text-primary align-middle"></i> Atribuir</a></li>
                                            @elseif (!$row['completed'])
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="to_remove_add({{ $row['id'] }})"><i class="ri-user-received-2-line text-danger align-middle"></i> Desatribuir</a></li>
                                            @endif
                                            @if ($row['canReattribute'])
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="askReattribute({{ $row['id'] }})"><i class="ri-refresh-line text-primary align-middle"></i> Re-atribuir</a></li>
                                            @endif
                                            @if (!$row['priority'])
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('setPriority', '{{ $row['id'] }}')"><i class="ri-alert-fill text-danger align-middle"></i> Priorizar</a></li>
                                            @else
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('removePriority', '{{ $row['id'] }}')"><i class="ri-alert-line text-success align-middle"></i> Remover prioridade</a></li>
                                            @endif
                                            @if ($row['canRemove'])
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="askRemove({{ $row['id'] }})"><i class="ri-delete-bin-2-line text-danger align-middle"></i> Remover despacho</a></li>
                                            @endif
                                        </ul>
                                    </div>
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
            @include('livewire.dispatchs.analises-pre.partials.stack-mass-actions', ['drop' => 'dropup', 'btn' => 'btn-light'])
        </x-ops.selection-bar>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="controlBuscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="controlBuscarMultiLabel">Buscar várias notas</h5>
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
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="add_mass_notes" tabindex="-1" aria-labelledby="addMassNotesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Atribuição em massa</span>
                        <h5 class="modal-title" id="addMassNotesLabel">Atribuir {{ $service->service }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar" wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <p class="ops-modal-note">Escolha a empresa e o usuário que passam a ser os responsáveis pelas {{ $notes->count() }} nota(s) abaixo.</p>

                        <div class="mb-3">
                            <label class="form-label text-uppercase" for="preMassCompany">Empresa</label>
                            <select class="form-select form-select-sm" id="preMassCompany" wire:model="company_s">
                                <option selected>Selecione</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-uppercase" for="preMassUser">Usuário</label>
                            <select class="form-select form-select-sm" id="preMassUser" wire:model="user_s">
                                @if ($users->count())
                                    <option value="" selected>Selecione um usuário</option>
                                    @foreach ($users->sortBy('name', SORT_LOCALE_STRING) as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                @else
                                    <option selected>Escolha uma empresa primeiro</option>
                                @endif
                            </select>
                        </div>

                        <div class="ops-table-wrap">
                            <table class="table ops-table">
                                <colgroup>
                                    <col style="width: 44px">
                                    <col style="width: 130px">
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
                                        <tr>
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
                    <button type="button" class="btn btn-outline-secondary btn-dash" wire:click.prevent="closeall">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="confirm_att" wire:loading.attr="disabled" wire:target="confirm_att">
                        <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                    </button>
                </div>
            </div>
        </div>
    </div>

    @stack('modals')
    @livewire('audits.info')
    @livewire('components.status.show-status', key('show_status'))
    @livewire('dispatchs.common.reclaim-info', key('reclaim-info-analises-pre-stack'))
</div>
