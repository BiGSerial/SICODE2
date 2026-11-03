@php
    use App\Custom\Notestatus;
    use App\Support\Ops\Tone;

    $partner = $partner ?? false;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || $statusFilter || !empty($multiSearch);
    $totalOpen = array_sum($statusCounts);
    $canRemove = !auth()->user()?->contract;
@endphp

{{-- LISTA DE CONTROLE (acompanha o que já foi despachado). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
     Não confundir com a lista operacional, onde se despacha. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero
        class="ops-hero--control"
        :eyebrow="'Controle · ' . ($partner ? 'Parceira · ' : '') . $serviceInfo->service"
        :title="'Controle de ' . mb_strtolower($serviceInfo->service)"
        subtitle="Acompanhamento do que já foi despachado: quem está com cada nota, há quantos dias e como está o prazo."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <span class="ops-mode-pill" title="Esta é uma lista de controle: aqui não se despacha, só se acompanha e se corrige."><i class="ri-radar-line" aria-hidden="true"></i> Lista de controle</span>
            <a class="btn btn-light btn-sm btn-dash" href="{{ route($partner ? 'dispatch.partner' : 'dispatch.main', ['service' => $service]) }}"><i class="ri-arrow-left-right-line" aria-hidden="true"></i> Ir para a Lista para {{ $serviceInfo->service }}</a>
        </div>
    </x-ops.hero>

    {{-- Painel de situação (discreto): os cartões de status são filtros. --}}
    <section class="ops-stats" aria-label="Situação do controle">
        <x-ops.stat tone="teal" label="Em controle" :value="number_format($totalOpen, 0, ',', '.')" icon="ri-stack-line" :active="false" disabled />
        @foreach ($statusCounts as $st => $total)
            <x-ops.stat :tone="Tone::forProductionStatus($st)" :label="Notestatus::status($st)->status" :value="number_format($total, 0, ',', '.')"
                :icon="Notestatus::status($st)->icon" :active="$statusFilter === (int) $st" wire:click.prevent="toggleStatus({{ (int) $st }})" wire:key="stat-{{ $st }}"
                :title="'Filtrar por ' . Notestatus::status($st)->status" />
        @endforeach
    </section>

    <section class="ops-toolbar" aria-label="Filtros do controle">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, ordem, rubrica, usuário…" label="Buscar no controle">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="comissionControlNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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

    <section class="mp-panel" aria-label="Controle de {{ $serviceInfo->service }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Controle de {{ mb_strtolower($serviceInfo->service) }}</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="mp-pill" wire:loading.delay>Atualizando…</span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click="$emitTo('dispatchs.common.dd-changes-create', 'openDdChangesCreateModal')" title="Relacionar números de DD às notas em massa">
                    <i class="ri-group-line" aria-hidden="true"></i> DD em massa
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                    <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                </button>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $lists->count()))" :cols="8" />

        @if ($lists->isEmpty())
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
                        @foreach ([112, 128, 140, 110, 120, 70, 140, 108, 84, 104, 104, 104, 112, 52] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="is-left">Despachante</th>
                            <th>Nota</th>
                            <th>DD</th>
                            <th class="is-left">Rubrica</th>
                            <th class="is-left">Município</th>
                            <th>Grupo 2</th>
                            <th class="is-left">Empresa</th>
                            <th class="is-left">Usuário</th>
                            <th>Dias atribuído</th>
                            <th>Prazo real</th>
                            <th>Em despacho</th>
                            <th>Em atribuição</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $item)
                            @php $row = $rows[$item->id]; @endphp
                            <tr wire:key="row-{{ $item->id }}" @class(['ops-row-priority' => $row['priority']])>
                                <td class="is-left">
                                    <span class="fw-semibold d-block text-truncate" title="{{ $row['dispatcherFull'] }}">{{ $row['dispatcher'] }}</span>
                                </td>

                                <td>
                                    @if ($item->d5)
                                        <x-ops.note :note="$item->note?->note" kind="ri" tone="blue" hint="Retorno Interno – clique para ver"
                                            wire:click.prevent="$emitTo('dispatchs.common.reclaim-info', 'getInfoByProduction', {{ $item->id }})">
                                            @if ($row['priority'])
                                                <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
                                                    wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $item->id }})" />
                                            @endif
                                            @if (isset($legalNoteIds[$item->note_id]))
                                                <x-legal.note-demand-tags :note-id="$item->note_id" :row-key="'dispatchs-comission-' . $item->id" />
                                            @endif
                                        </x-ops.note>
                                    @else
                                        <x-ops.note :note="$item->note?->note">
                                            @if ($row['priority'])
                                                <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
                                                    wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $item->id }})" />
                                            @endif
                                            @if (isset($legalNoteIds[$item->note_id]))
                                                <x-legal.note-demand-tags :note-id="$item->note_id" :row-key="'dispatchs-comission-' . $item->id" />
                                            @endif
                                        </x-ops.note>
                                    @endif
                                </td>

                                <td>
                                    @if ($row['wpa'])
                                        <x-ops.badge :tone="$row['wpa']['tone']" :icon="$row['wpa']['icon']" :label="$row['wpa']['label'] ?? '—'" hint="Situação da DD no WPA" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td class="is-left"><span class="d-block text-truncate" title="{{ $item->note?->rubrica }}">{{ $item->note?->rubrica ?: '—' }}</span></td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $item->note?->lexp }}">{{ $item->note?->lexp ?: '—' }}</span></td>
                                <td>{{ $item->note?->group2 ?: '—' }}</td>

                                <td class="is-left"><span class="fw-semibold d-block text-truncate" title="{{ $item->company?->name }}">{{ $item->company?->name ?? '—' }}</span></td>
                                <td class="is-left"><span class="fw-semibold d-block text-truncate"><x-user.status-name :user="$item->user" /></span></td>

                                <td>{{ $row['attDays'] !== null ? $row['attDays'] . ' dias' : '—' }}</td>

                                <td><x-ops.date :date="$row['created']" :tone="$row['createdTone']" :sub="$row['createdAge'] . ' d'" :hint="'Criada há ' . $row['createdAge'] . ' dias; o prazo é de 30 dias. Verde até 20 dias, âmbar até 30, vermelho acima.'" /></td>

                                <td><x-ops.date :date="$item->dispatch_at" :tone="$row['dispatchTone']" :sub="$row['dispatchAge'] !== null ? $row['dispatchAge'] . ' d' : null" hint="Data do despacho e dias corridos desde então. Verde até 21 dias, âmbar até 30, vermelho acima." /></td>

                                <td><x-ops.date :date="$item->att_at" :tone="$row['attTone']" :sub="$row['attAge'] !== null ? $row['attAge'] . ' d' : null" hint="Data da atribuição ao responsável e dias corridos desde então. Verde até 7 dias, âmbar até 9, vermelho acima." /></td>

                                <td>
                                    <x-ops.badge class="ops-clickable" :tone="Tone::forProductionStatus($item->status)" :label="Notestatus::status($item->status)->status" hint="Clique para ver o histórico de status"
                                        wire:click.prevent="$emitTo('components.status.show-status', 'showStatus', {{ $item->id }}, {{ $item->status }})" />
                                </td>

                                <td>
                                    <div class="dropdown ops-row-actions">
                                        <button class="btn btn-outline-secondary btn-sm ops-row-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações da linha"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if (!$item->block)
                                                @if (!$item->priority)
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('setPriority', '{{ $item->id }}')"><i class="ri-alert-fill text-danger align-middle"></i> Priorizar</a></li>
                                                @else
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('removePriority', '{{ $item->id }}')"><i class="ri-alert-line text-success align-middle"></i> Remover prioridade</a></li>
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $item->id }})"><i class="ri-file-info-line text-primary align-middle"></i> Ver prioridade</a></li>
                                                @endif
                                                @if ($item->d5)
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('dispatchs.common.reclaim-info', 'getInfoByProduction', {{ $item->id }})"><i class="ri-loop-left-line text-primary align-middle"></i> Ver retorno interno</a></li>
                                                @endif
                                                <li>
                                                    @if ($item->user_id)
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-assign', 'toAssign', {{ $item->id }})"><i class="ri-user-unfollow-line text-danger align-middle"></i> Desatribuir</a>
                                                    @else
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-assign', 'toAssign', {{ $item->id }})"><i class="ri-user-add-fill text-primary align-middle"></i> Atribuir</a>
                                                    @endif
                                                </li>
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.new-production', 'editProduction', {{ $item->id }})"><i class="ri-edit-2-line align-middle"></i> Editar</a></li>
                                                @if ($canRemove)
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-remove', 'toRemove', {{ $item->id }})"><i class="ri-delete-bin-2-line text-danger align-middle"></i> Remover atividade</a></li>
                                                @endif
                                            @elseif ($canRemove)
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.to-remove-transfer', 'toRemoveTransfer', {{ $item->id }})"><i class="ri-delete-row text-danger align-middle"></i> Remover transferência</a></li>
                                            @else
                                                <li><span class="dropdown-item-text text-muted">Sem ações disponíveis</span></li>
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
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="controlBuscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="controlBuscarMultiLabel">{{ $serviceInfo->service }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula. A busca também vale para rubrica, município, usuário e ordem.</p>
                    <label class="form-label fw-semibold text-uppercase" for="advanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" wire:keydown.ctrl.enter="buscarMulti"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('components.status.show-status', key('show_status_note_comission'))
    @livewire('dispatchs.common.dd-changes-create', ['service' => $service, 'control' => true], key('dd-changes-create-comission'))
    @livewire('dispatchs.common.reclaim-info', key('reclaim-info-comission-stack'))
    @livewire('production.actions.to-assign', key('to_assign_comission'))
    @livewire('production.actions.new-production', key('new_production_comission'))
    @livewire('production.actions.to-remove', key('to_remove_comission'))
    @livewire('production.actions.to-remove-transfer', key('to_remove_transfer_comission'))
</div>
