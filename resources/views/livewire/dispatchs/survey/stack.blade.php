@php
    use App\Custom\Notestatus;
    use App\Support\Ops\Tone;

    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || !empty($multiSearch);
    $statusFilter = $this->filterValues('status');
    $totalOpen = array_sum($statusCounts);
    $contractUser = (bool) auth()->user()?->contract;
@endphp

{{-- LISTA DE CONTROLE (acompanha o que já foi despachado). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
     Não confundir com a lista operacional (Lista para Levantamento), onde se despacha. --}}
<div class="closure-dash my-panel ops-list ops-control">

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
        @foreach ($statusCounts as $st => $total)
            <x-ops.stat :tone="Tone::forProductionStatus($st)" :label="Notestatus::status($st)->status" :value="number_format($total, 0, ',', '.')"
                :icon="Notestatus::status($st)->icon" :active="in_array((string) $st, $statusFilter, true)" wire:click.prevent="toggleStatus({{ $st }})" wire:key="stat-{{ $st }}"
                :title="'Filtrar por ' . Notestatus::status($st)->status" />
        @endforeach
    </section>

    <section class="ops-toolbar" aria-label="Filtros do controle">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, ordem, rubrica, usuário…" label="Buscar no controle">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="surveyControlNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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
                <button type="button" class="btn btn-sm btn-outline-primary btn-dash" title="Associar várias DDs de uma vez" wire:click.prevent="$emitTo('dispatchs.common.dd-changes-create', 'openDdChangesCreateModal')">
                    <i class="ri-group-line" aria-hidden="true"></i> DD em massa
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                    <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                </button>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $lists->count()))" :cols="8" />

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
                        @foreach ([140, 124, 132, 100, 132, 80, 140, 84, 108, 108, 108, 100, 52] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="is-left">Despachante</th>
                            <th>Nota</th>
                            <th>DD</th>
                            <th>Rubrica</th>
                            <th class="is-left">Município</th>
                            <th>Grupo 2</th>
                            <th class="is-left">Usuário</th>
                            <th>Com o usuário</th>
                            <th>Prazo real</th>
                            <th>Despachada</th>
                            <th>Atribuída</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="line-{{ $row['id'] }}" @class(['ops-row-priority' => $row['priority']])>
                                <td class="is-left">
                                    <span class="fw-semibold d-block text-truncate">{{ $row['dispatcher'] }}</span>
                                    @if ($row['dispatcherCo'])
                                        <span class="ops-sub text-truncate" title="{{ $row['dispatcherCo'] }}">{{ $row['dispatcherCo'] }}</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($row['ri'])
                                        <x-ops.note :note="$row['note']" kind="ri" tone="blue" hint="Retorno Interno – clique para ver"
                                            wire:click="$emitTo('dispatchs.common.reclaim-info', 'getInfoByProduction', {{ $row['id'] }})">
                                            @include('livewire.dispatchs.survey.partials.stack-note-extras')
                                        </x-ops.note>
                                    @else
                                        <x-ops.note :note="$row['note']">
                                            @include('livewire.dispatchs.survey.partials.stack-note-extras')
                                        </x-ops.note>
                                    @endif
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <x-ops.badge :tone="$row['ddTone']" :icon="$row['ddIcon']" :label="$row['ddInfo'] ?: '—'" :hint="$row['dd'] ? 'DD ' . $row['dd'] : 'Sem DD'" />
                                    </div>
                                </td>

                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                <td>{{ $row['group2'] ?: '—' }}</td>

                                <td class="is-left">
                                    <span class="fw-semibold d-block text-truncate">{{ $row['user'] }}</span>
                                    @if ($row['userCo'])
                                        <span class="ops-sub text-truncate" title="{{ $row['userCo'] }}">{{ $row['userCo'] }}</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($row['attDays'] !== null)
                                        {{ $row['attDays'] }} {{ $row['attDays'] === 1 ? 'dia' : 'dias' }}
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['dueAge'] . ' d'"
                                        hint="Criação da nota + 30 dias, e dias corridos desde a criação. Verde até 20 dias, âmbar até 30, vermelho acima." />
                                </td>
                                <td>
                                    <x-ops.date :date="$row['dispatchAt']" :tone="$row['dispatchTone']" :sub="$row['dispatchAge'] !== null ? $row['dispatchAge'] . ' d' : null"
                                        hint="Data do despacho e dias corridos desde então. Verde até 21 dias, âmbar até 30, vermelho acima." />
                                </td>
                                <td>
                                    <x-ops.date :date="$row['attAt']" :tone="$row['attTone']" :sub="$row['attAge'] !== null ? $row['attAge'] . ' d' : null"
                                        hint="Data em que foi atribuída ao usuário e dias corridos desde então. Verde até 7 dias, âmbar até 9, vermelho acima." />
                                </td>

                                <td>
                                    <x-ops.badge class="ops-clickable" :tone="$row['statusTone']" :label="$row['statusLabel']" hint="Clique para ver o histórico de status"
                                        wire:click.prevent="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['status'] }})" />
                                </td>

                                <td>
                                    <div class="dropdown ops-row-actions">
                                        <button class="btn btn-outline-secondary btn-sm ops-row-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações da linha"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if (!$row['block'])
                                                @if (!$row['completed'])
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('setPriority', '{{ $row['id'] }}')"><i class="ri-alert-fill {{ $row['priority'] ? 'text-success' : 'text-danger' }} align-middle"></i> {{ $row['priority'] ? 'Remover prioridade' : 'Priorizar' }}</a></li>
                                                    @if ($row['priority'])
                                                        <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})"><i class="ri-file-info-line text-primary align-middle"></i> Ver prioridade</a></li>
                                                    @endif
                                                @endif
                                                @if ($row['ri'])
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('dispatchs.common.reclaim-info', 'getInfoByProduction', {{ $row['id'] }})"><i class="ri-loop-left-line text-primary align-middle"></i> Ver Retorno Interno</a></li>
                                                @endif
                                                <li>
                                                    @if (!$row['completed'] && $row['hasUser'])
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-assign', 'toAssign', {{ $row['id'] }})"><i class="ri-user-unfollow-line text-danger align-middle"></i> Desatribuir</a>
                                                    @elseif (!$row['hasUser'])
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-assign', 'toAssign', {{ $row['id'] }})"><i class="ri-user-add-fill text-primary align-middle"></i> Atribuir</a>
                                                    @else
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-return', 'toReturn', {{ $row['id'] }})"><i class="ri-restart-line text-success align-middle"></i> Retornar</a>
                                                    @endif
                                                </li>
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.new-production', 'editProduction', {{ $row['id'] }})"><i class="ri-edit-2-line align-middle"></i> Editar</a></li>
                                                @unless ($contractUser)
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-remove', 'toRemove', {{ $row['id'] }})"><i class="ri-delete-bin-2-line text-danger align-middle"></i> Remover atividade</a></li>
                                                @endunless
                                            @else
                                                @unless ($contractUser)
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-remove-transfer', 'toRemoveTransfer', {{ $row['id'] }})"><i class="ri-delete-row text-danger align-middle"></i> Remover transferência</a></li>
                                                @endunless
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

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="multiSearchModal" tabindex="-1" aria-labelledby="multiSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca</span>
                        <h5 class="modal-title" id="multiSearchModalLabel">Buscar em massa</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota por linha (ou ordem, rubrica, município, usuário). Use Ctrl + Enter para buscar.</p>
                    <label class="form-label" for="surveyControlAdvancedSearch">Notas ou ordens</label>
                    <textarea class="form-control" id="surveyControlAdvancedSearch" rows="12" wire:model.defer="advancedSearch" wire:keydown.ctrl.enter="buscarMulti"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.common.dd-changes-create', ['service' => $service->uuid, 'control' => true], key('dd-changes-create'))
    @livewire('components.status.show-status', key('show_status_note'))

    {{-- Ações da linha (cada uma é um único componente da tela, acionado por evento) --}}
    @livewire('production.actions.set-priority', key('set_priority_note'))
    @livewire('production.actions.to-assign', key('to_assign_note'))
    @livewire('production.actions.new-production', key('new_production_note'))
    @livewire('production.actions.to-return', key('to_return_note'))
    @livewire('production.actions.to-remove', key('to_remove_note'))
    @livewire('production.actions.to-remove-transfer', key('to_remove_transfer_note'))
    @livewire('dispatchs.common.reclaim-info', key('reclaim_info_production_action'))
</div>
