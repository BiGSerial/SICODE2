@php
    use App\Custom\Notestatus;
    use App\Support\Ops\Tone;

    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || filled($measurementDeadlineStatus) || !empty($multiSearch);
    $statusFilter = $this->filterValues('status');
    $totalOpen = array_sum($statusCounts);
@endphp

{{-- LISTA DE CONTROLE DA PARCEIRA (acompanha o que já foi despachado para a empresa). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
     Mesma tela do Controle de Pagamento, sem o saldo do PEP; Empresa e Usuário em colunas separadas. --}}
<div class="closure-dash my-panel ops-list ops-control" x-data="opsSelection()">

    <x-ops.hero
        class="ops-hero--control"
        :eyebrow="'Controle · ' . $service->service"
        :title="'Controle de ' . mb_strtolower($service->service)"
        subtitle="Acompanhamento do que já foi despachado: quem está com cada nota, há quantos dias e como está o prazo."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <span class="ops-mode-pill" title="Esta é uma lista de controle: aqui não se despacha, só se acompanha e se corrige."><i class="ri-radar-line" aria-hidden="true"></i> Lista de controle</span>
            <a class="btn btn-light btn-sm btn-dash" href="{{ route('dispatch.partner', ['service' => $service->uuid]) }}"><i class="ri-arrow-left-right-line" aria-hidden="true"></i> Ir para a Lista para {{ $service->service }}</a>
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
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, ordem, rubrica…" label="Buscar no controle">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="partnerControlNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <span class="ops-toolbar-label">Prazo da medição</span>
            <x-ops.segmented name="partnerControlDeadline" label="Prazo da medição" wire:model="measurementDeadlineStatus"
                :options="['' => 'Todos', 'no_prazo' => 'No prazo', 'vencendo' => 'Vencendo', 'vencido' => 'Vencido']" />

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
                @include('livewire.dispatchs.payment.partials.stack-mass-actions')
                <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
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
                        @foreach ([38, 60, 148, 104, 100, 120, 120, 130, 130, 96, 96, 96, 108, 100, 52] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectAll" wire:click="setSelectAll()" @checked($this->checkAllSelect($lists)) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Ordem</th>
                            <th>Valor ADS</th>
                            <th>Empresa do informe</th>
                            <th>Rubrica / Município</th>
                            <th class="is-left">Empresa</th>
                            <th class="is-left">Usuário</th>
                            <th>Informe</th>
                            <th>Despachado</th>
                            <th>Atribuído</th>
                            <th>Prazo medição</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $soma = 0; @endphp
                        @foreach ($lists as $list)
                            @php
                                $flowWorkForm = $list->WorkReportFlowProductions->firstWhere('stage', \App\Models\WorkReportFlowProduction::STAGE_PAYMENT)?->WorkReport;
                                $workForm     = $flowWorkForm ?? $list->Note->WorkForm;
                                // D5 do informe atual tem prioridade; cai para a legada (sem informe) só quando o informe atual não tem D5 própria.
                                $five    = $flowWorkForm?->FiveNote ?? $list->Note->FiveNote;
                                $d5Tone  = $five ? (($five->is_supervisioned ?? false) ? 'green' : 'blue') : null;
                                $d5Hint  = $five ? (($five->is_supervisioned ?? false) ? 'D5 fiscalizada – liberar carta' : 'Gerar D5 e reter carta') : null;
                                $partial = $list->partial ? $list->Note->Partials?->last() : null;
                                $orders  = $workForm && !$partial ? ($workForm->Orders ?? collect()) : ($partial ? ($partial->Orders ?? collect()) : collect());
                                $adsForm = $list->Note->Adsform ?? $workForm?->Adsform;
                                $adsAmount = $partial ? $partial->value : $adsForm?->amount;
                                $soma += (float) ($adsAmount ?? 0);
                                $isConnection = !$list->partial && $workForm && collect($workForm->finalScopeBadges())->pluck('scope')->contains('connection');
                                $informedAt = $workForm?->informed_at ?? $partial?->supervision_at;
                                $deadline = $this->measurementDeadlineFor($list);
                                $deadlineTone = match ($deadline['status'] ?? null) { 'no_prazo' => 'green', 'vencendo' => 'amber', 'vencido' => 'red', default => 'muted' };
                            @endphp
                            <tr wire:key="line-{{ $list->id }}" @class(['ops-row-priority' => $list->priority])>
                                <td><input class="form-check-input" type="checkbox" data-ops-row value="{{ $list->id }}" wire:model.defer="selected" aria-label="Selecionar {{ $list->Note->note }}" @change="recount()"></td>

                                <td>
                                    <x-ops.badge :tone="$list->partial ? 'amber' : ($isConnection ? 'amber' : 'green')" :label="$list->partial ? 'Parcial' : 'Final'" :hint="$list->partial ? 'Informe parcial' : ($isConnection ? 'Informe final de ligação' : 'Informe final')" />
                                </td>

                                <td class="copy-text" data-value="{{ $list->Note->note }}">
                                    <div class="ops-cell-stack is-center">
                                        @if ($list->d5)
                                            <x-ops.badge class="ops-note-badge ops-clickable" tone="blue" hint="Retorno Interno – clique para ver"
                                                wire:click.prevent="$emitTo('dispatchs.common.reclaim-info', 'getInfoByProduction', {{ $list->id }})"><strong>RI</strong> {{ $list->Note->note }}</x-ops.badge>
                                        @elseif ($five)
                                            <x-ops.badge class="ops-note-badge ops-clickable" :tone="$d5Tone" :hint="$d5Hint"
                                                wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $list->note_id }})"><strong>D5</strong> {{ $list->Note->note }}</x-ops.badge>
                                        @else
                                            <span class="ops-note">{{ $list->Note->note }}</span>
                                        @endif
                                        <span class="ops-sub">{{ $workForm ? 'Informe #' . $workForm->id : ($partial ? 'Parcial #' . $partial->id : 'Sem informe') }}</span>
                                        @if ($list->priority)
                                            <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
                                                wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $list->id }})" />
                                        @endif
                                        @if ($adsForm?->tacit)
                                            <span class="d-inline-flex gap-1">
                                                <x-ops.badge sm tone="muted" label="ADS tácita" />
                                                <x-ops.badge sm :tone="$adsForm->tacit_delivered_at ? 'green' : 'red'" :label="$adsForm->tacit_delivered_at ? 'Entregue' : 'Não entregue'" />
                                            </span>
                                        @endif
                                        @if (!$list->d5 && !$five && isset($legalNoteIds[$list->note_id]))
                                            <x-legal.note-demand-tags :note-id="$list->note_id" :row-key="'dispatchs-payment-' . $list->id" />
                                        @endif
                                    </div>
                                </td>

                                <td><div class="ops-cell-stack is-center">@forelse ($orders as $order)<span class="fw-semibold">{{ $order->ordem }}</span>@empty<span class="ops-empty">—</span>@endforelse</div></td>

                                <td><x-ops.money :value="$adsAmount" /></td>

                                <td class="is-left"><span class="fw-semibold d-block text-truncate" title="{{ $workForm?->Company?->name ?? $partial?->Company?->name }}">{{ $workForm?->Company?->name ?? $partial?->Company?->name ?? '—' }}</span></td>

                                <td class="is-left">
                                    <span class="fw-semibold d-block text-truncate" title="{{ $list->Note->rubrica }}">{{ $list->Note->rubrica ?: '—' }}</span>
                                    <span class="ops-sub text-truncate">{{ $list->Note->lexp }}</span>
                                </td>

                                <td class="is-left"><span class="fw-semibold d-block text-truncate" title="{{ $list->Company?->name }}">{{ $list->Company?->name ?? '—' }}</span></td>

                                <td class="is-left"><span class="fw-semibold d-block text-truncate"><x-user.status-name :user="$list->User" /></span></td>

                                <td><x-ops.age :date="$informedAt" rule="informed" /></td>
                                <td><x-ops.age :date="$list->dispatch_at" rule="dispatch" /></td>
                                <td><x-ops.age :date="$list->att_at" rule="assigned" /></td>

                                <td>
                                    @if ($deadline)
                                        <x-ops.date :date="$deadline['due_at']" :tone="$deadlineTone"
                                            :sub="$deadline['remaining_days'] < 0 ? abs($deadline['remaining_days']) . ($deadline['remaining_days'] === -1 ? ' dia vencido' : ' dias vencidos') : $deadline['remaining_days'] . ($deadline['remaining_days'] === 1 ? ' dia' : ' dias')"
                                            hint="Prazo da medição. Verde: mais de 4 dias para vencer. Âmbar: até o vencimento. Vermelho: vencido." />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <x-ops.badge class="ops-clickable" :tone="Tone::forProductionStatus($list->status)" :label="Notestatus::status($list->status)->status" hint="Clique para ver o histórico de status"
                                        wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $list->id }}, {{ $list->status }})" />
                                </td>

                                <td>
                                    <div class="dropdown ops-row-actions">
                                        <button class="btn btn-outline-secondary btn-sm ops-row-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações da linha"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($list->status == 1)
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="get_single_note({{ $list->id }})"><i class="ri-user-shared-fill text-primary align-middle"></i> Atribuir</a></li>
                                            @elseif (!$list->completed)
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="to_remove_add({{ $list->id }})"><i class="ri-user-received-2-line text-danger align-middle"></i> Desatribuir</a></li>
                                            @endif
                                            <li><a class="dropdown-item" href="#" wire:click.prevent="$emitTo('production.actions.new-production', 'editProduction', {{ $list->id }})"><i class="ri-edit-2-line align-middle"></i> Editar</a></li>
                                            @if (($actions ??= app(\App\Services\Production\ProductionActions::class))->canReattribute($list))
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="askReattribute({{ $list->id }})"><i class="ri-refresh-line text-primary align-middle"></i> Re-atribuir</a></li>
                                            @endif
                                            @if (!$list->priority)
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('setPriority', '{{ $list->id }}')"><i class="ri-alert-fill text-danger align-middle"></i> Priorizar</a></li>
                                            @else
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="$emit('removePriority', '{{ $list->id }}')"><i class="ri-alert-line text-success align-middle"></i> Remover prioridade</a></li>
                                            @endif
                                            @if ($actions->canRemove($list))
                                                <li><a class="dropdown-item" href="#" wire:click.prevent="askRemove({{ $list->id }})"><i class="ri-delete-bin-2-line text-danger align-middle"></i> Remover despacho</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="is-right">Total da página</td>
                            <td><x-ops.money :value="$soma" /></td>
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
            @include('livewire.dispatchs.payment.partials.stack-mass-actions', ['drop' => 'dropup', 'btn' => 'btn-light'])
        </x-ops.selection-bar>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="controlBuscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="controlBuscarMultiLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
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

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-partner-stack-' . $service->uuid))

    @stack('modals')
    @livewire('audits.info')
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('components.d5.d5details', key('view_d5_note_details'))
    @livewire('dispatchs.common.reclaim-info', key('reclaim-info-partner-payment-stack'))
        @once
        @livewire('production.actions.new-production', key('new_production_payment_stack'))
    @endonce
</div>
