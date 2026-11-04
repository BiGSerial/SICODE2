@php
    $activity = $service->service;
    $hasRows  = $rows->isNotEmpty();
@endphp

{{-- Acompanhamento de Pagamento (a fila de trabalho do usuário): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, tipo, filtros)
     → abas → painel com a tabela. Prazo de medição: 8 dias úteis (regra em App\Services\Payment\PaymentAccompanyRowPresenter). --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $activity"
        :title="'Acompanhamento de ' . mb_strtolower($activity)"
        subtitle="As Notas e OVs que estão com você. Inicie, transfira ou devolva cada uma; o prazo de medição conta em dias, da aprovação do engenheiro (Parcial) ou da entrega da ADS (Final); a regra vem da região.">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_d5tolist" wire:loading.attr="disabled" wire:target="export_d5tolist">
                <i class="ri-file-text-line" aria-hidden="true"></i> Exportar D5
            </button>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros do acompanhamento">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou material" label="Buscar nota ou material">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="paymentAccompanyTypeNote" label="Tipo de nota" wire:model="typeNote" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <x-ops.toggle label="Vencidos" icon="ri-alarm-warning-line" :on="$prazo === 'vencido'" wire:click.prevent="toggleDeadline('vencido')" title="Mostra só o que já passou do prazo de medição" />
            <x-ops.toggle label="Vencendo" icon="ri-time-line" :on="$prazo === 'vencendo'" wire:click.prevent="toggleDeadline('vencendo')" title="Mostra só o que vence de 1 a 0 dias" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters() || $multiSearch)
            <div class="ops-toolbar-row">
                <div class="ops-chips" aria-label="Critérios ativos">
                    @foreach ($this->listFilterChips() as $chip)
                        <span class="ops-chip" wire:key="chip-{{ $chip['key'] }}">
                            <span><strong>{{ $chip['label'] }}:</strong> {{ $chip['value'] }}</span>
                            <button type="button" class="btn-close" aria-label="Remover filtro {{ $chip['label'] }}" wire:click="removeListFilter('{{ $chip['key'] }}')"></button>
                        </span>
                    @endforeach
                    @if ($multiSearch)
                        <span class="ops-chip" wire:key="chip-multi">
                            <span><strong>Busca em lote:</strong> {{ count($multiSearch) }} {{ count($multiSearch) === 1 ? 'item' : 'itens' }}</span>
                            <button type="button" class="btn-close" aria-label="Remover a busca em lote" wire:click="clearMultiSearch"></button>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <div class="mp-presets mb-3" role="tablist" aria-label="Acompanhamento">
        <button type="button" role="tab" class="mp-preset {{ $tab === 'producao' ? 'is-active' : '' }}" wire:click="setTab('producao')" aria-selected="{{ $tab === 'producao' ? 'true' : 'false' }}">Produção</button>
        <button type="button" role="tab" class="mp-preset {{ $tab === 'transferencias' ? 'is-active' : '' }}" wire:click="setTab('transferencias')" aria-selected="{{ $tab === 'transferencias' ? 'true' : 'false' }}">
            Transferências @livewire('components.transprod.count', ['service_id' => $service->uuid], key('acc-transfer-count'))
        </button>
    </div>

    @if ($tab === 'transferencias')
        <section class="mp-panel" aria-label="Transferências de {{ $activity }}">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Transferências</h2>
                    <div class="mp-panel-sub">Notas que você transferiu ou que foram transferidas para você.</div>
                </div>
            </div>
            <div class="mp-panel-body">
                @livewire('components.transprod.translist', ['service' => $service->id], key('acc-translist-' . $service->id))
            </div>
        </section>
    @else
        <section class="mp-panel" aria-label="Acompanhamento de {{ $activity }}">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Minha fila</h2>
                    <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }} · prioridade primeiro, depois a mais antiga</div>
                </div>
                <span class="mp-pill" wire:loading.delay>Atualizando…</span>
            </div>

            <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="9" />

            @if (!$hasRows)
                <div class="mp-panel-body">
                    <x-closure.empty-state
                        :title="$hasCriteria ? 'Nenhum resultado para os critérios escolhidos' : 'Você não tem tarefa atribuída em ' . $activity"
                        :text="$hasCriteria ? 'Ajuste ou limpe os critérios para ver mais registros.' : 'Assuma Notas e OVs na lista da atividade para elas aparecerem aqui.'"
                        icon="ri-inbox-line" />
                    <div class="text-center mt-2">
                        @if ($hasCriteria)
                            <button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar critérios</button>
                        @else
                            <a class="btn btn-outline-primary btn-sm btn-dash" href="{{ route('services.main', ['service' => $service->uuid]) }}"><i class="ri-list-check-2" aria-hidden="true"></i> Ir para a lista de {{ mb_strtolower($activity) }}</a>
                        @endif
                    </div>
                </div>
            @else
                <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                    <table class="table ops-table">
                        <colgroup>
                            <col style="width: 76px">
                            <col style="width: 140px">
                            <col style="width: 64px">
                            <col style="width: 88px">
                            <col style="width: 104px">
                            <col style="width: 120px">
                            <col style="width: 140px">
                            <col style="width: 100px">
                            <col style="width: 100px">
                            <col style="width: 88px">
                            <col style="width: 92px">
                            <col style="width: 110px">
                            <col style="width: 96px">
                            <col style="width: 140px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Nota</th>
                                <th>Arquivos</th>
                                <th>Ordem</th>
                                <th>Valor ADS</th>
                                <th>Operações 30 · 40 · 50</th>
                                <th class="is-left">Empresa</th>
                                <th>Saldo PEP</th>
                                <th class="is-left">Município</th>
                                <th>Execução</th>
                                <th>ADS</th>
                                <th>Prazo medição</th>
                                <th>Status</th>
                                <th><span class="visually-hidden">Ação</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="acc-{{ $row['id'] }}" @class(['ops-row-priority' => $row['priority']])>
                                    <td @if ($row['detail']) class="ops-clickable" title="Duplo clique para ver os detalhes do informe"
                                        wire:dblclick="$emitTo('{{ $row['detail']['kind'] === 'partial' ? 'partner.show.show-partial-info' : 'partner.show.show-work-form' }}', 'show_form', {{ $row['detail']['id'] }})" @endif>
                                        <x-ops.badge :tone="$row['scopeTone']" :label="$row['scopeText']" :hint="$row['scopeHint']" />
                                    </td>

                                    <td>
                                        <div class="ops-cell-stack is-center">
                                            @if ($row['d5'])
                                                <x-ops.badge class="ops-note-badge ops-clickable" :tone="$row['d5']['tone']" :hint="$row['d5']['hint']"
                                                    wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $row['noteId'] }}, {{ $row['five'] ?? 'null' }})"><strong>D5</strong> {{ $row['note'] }}</x-ops.badge>
                                            @else
                                                <span class="ops-note copy-text" data-value="{{ $row['note'] }}">{{ $row['note'] }}</span>
                                            @endif
                                            @if ($row['priority'])
                                                <x-ops.badge sm tone="red" icon="ri-alert-fill" label="Prioridade" class="ops-clickable" hint="Clique para ver a informação da prioridade desta Nota/OV" wire:click.prevent="$emit('infoPriority', '{{ $row['id'] }}')" />
                                            @endif
                                            @if ($row['tacit'])
                                                <x-ops.badge sm tone="muted" label="ADS tácita" hint="A ADS deste informe é tácita" />
                                                <x-ops.badge sm :tone="$row['tacitSent'] ? 'green' : 'red'" :label="$row['tacitSent'] ? 'Entregue' : 'Não entregue'" hint="Situação da entrega da ADS tácita" />
                                            @endif
                                            <x-legal.note-demand-tags :demands="$row['legal']" :row-key="'services-payment-accompany-' . $row['id']" />
                                        </div>
                                    </td>

                                    <td><x-files.select-download-list :files="$row['files']" :allow-tacit="true" /></td>

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

                                    <td class="is-left">
                                        <span class="fw-semibold d-block text-truncate" title="{{ $row['company'] }}">{{ $row['company'] ?: '—' }}</span>
                                        <span class="ops-sub text-truncate">{{ $row['informant'] ?: '' }}</span>
                                    </td>

                                    <td>
                                        <div class="ops-cell-stack is-center">
                                            @forelse ($row['orders'] as $order)
                                                <span class="ops-clickable" @if ($order['pep']) wire:dblclick="$emit('openPepFinancialModal', '{{ $order['pep'] }}')" title="Duplo clique para consultar o PEP {{ $order['pep'] }}" @endif>
                                                    <x-ops.money :value="$order['saldo']" signed />
                                                </span>
                                            @empty
                                                <span class="ops-empty">—</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>

                                    <td><x-ops.date :date="$row['executedAt']" hint="Data de execução informada no informe" /></td>
                                    <td><x-ops.date :date="$row['adsAt']" :sub="$row['adsLabel']" :hint="$row['isPartial'] ? 'Data da ADS da parcial' : 'Entrega da ADS do informe'" /></td>
                                    <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['dueLabel']" :hint="$row['dueHint']" /></td>

                                    <td>
                                        <x-ops.badge :tone="$row['statusTone']" :label="$row['statusLabel']" class="ops-clickable" hint="Ver o histórico deste status"
                                            wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['status'] }})" />
                                    </td>

                                    <td>
                                        <div class="d-inline-flex gap-1">
                                            @if ($row['canAct'])
                                                <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="$emitTo('services.payment.forms.jobform', 'showProduction', {{ $row['id'] }})" title="Abrir o formulário de pagamento desta Nota/OV">
                                                    <i class="ri-play-circle-line" aria-hidden="true"></i> Iniciar
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click.prevent="goTransferProd({{ $row['id'] }})" title="Transferir esta Nota/OV para outro usuário">
                                                    <i class="ri-arrow-left-right-line" aria-hidden="true"></i><span class="visually-hidden">Transferir</span>
                                                </button>
                                            @elseif ($row['blocked'])
                                                <x-ops.badge tone="blue" label="Bloqueada" hint="Esta Nota/OV está bloqueada e não pode ser iniciada agora" />
                                            @endif
                                            @if ($row['canReturn'])
                                                <button type="button" class="btn btn-outline-danger btn-sm ops-action" wire:click.prevent="$emitTo('production.return.reject-inform-partial', 'toReturn', {{ $row['id'] }})" title="Devolver o informe parcial para a empreiteira">
                                                    <i class="ri-delete-back-2-line" aria-hidden="true"></i><span class="visually-hidden">Devolver informe</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="is-right">Total da página</td>
                                <td><x-ops.money :value="$rows->sum('adsAmount')" /></td>
                                <td colspan="9"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            <x-ops.pager :paginator="$lists" :per-page="$perPage" :sizes="$pageSizes" />
        </section>
    @endif

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel"><span class="modal-eyebrow">{{ $activity }}</span>Buscar em lote</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="advanceSearch">Notas ou ordens</label>
                    <textarea class="form-control" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="pause_note" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="accPauseLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accPauseLabel"><span class="modal-eyebrow">Pausar</span>{{ mb_strtoupper($activity) }}</h5>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('components.pausenote.pausenote')
                </div>
            </div>
        </div>
    </div>

    @livewire('components.transprod.transprod', key('Transfer_production'))
    @livewire('partner.show.show-work-form', key('WorkFormCompany'))
    @livewire('services.payment.forms.jobform', key('payment-form'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('partner.show.show-partial-info', key('show_partial_info'))
    @livewire('production.return.reject-inform-partial', key('reject_inform_partial'))
    @livewire('components.d5.d5details', key('d5_details'))
    @livewire('components.pep.financial-modal', key('payment-accompany-pep-financial-modal'))

    <div wire:init="checkOpen"></div>
</div>
