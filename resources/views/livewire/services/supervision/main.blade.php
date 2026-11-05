@php
    $serviceTitle = mb_strtolower($service->service);
    $statusCodes  = $service->Status->unique('value')->pluck('value')->implode(' · ');
    $hasCriteria  = $this->hasListFilters() || filled($search) || filled($note_type);
@endphp

{{-- Acompanhamento do Serviço de Fiscalização (as notas atribuídas a quem está logado): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md.
     Hero → ferramentas (busca, tipo, filtros) → abas Produção / Transferências → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" wire:init="checkOpen">

    <x-ops.hero
        :eyebrow="'Serviço · ' . $service->service"
        :title="'Acompanhamento de ' . $serviceTitle"
        subtitle="As notas e OVs atribuídas a você: abra a atividade, transfira ou devolva o informe e acompanhe os prazos pela cor."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou material" label="Buscar nota ou material" />

            <x-ops.segmented name="servicesSupervisionNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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

    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-production-tab" data-bs-toggle="tab" data-bs-target="#my_production"
                type="button" role="tab" aria-controls="my_production" aria-selected="true"
                wire:click.prevent="$emit('refresh_accomany')">
                Produção
            </button>

            <button class="nav-link" id="nav-transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer"
                type="button" role="tab" aria-controls="transfer" aria-selected="false"
                wire:click.prevent="openTransfers">
                Transferências
                @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
            </button>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="my_production" role="tabpanel" aria-labelledby="nav-production-tab" tabindex="0">
            <section class="mp-panel" aria-label="Acompanhamento de {{ $service->service }}">
                <div class="mp-panel-head">
                    <div>
                        <h2 class="mp-panel-title">Acompanhamento</h2>
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
                            :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Você não tem tarefa atribuída em ' . $service->service"
                            :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Quando uma nota for atribuída a você, ela aparece aqui.'"
                            icon="ri-inbox-line" />
                        @if ($hasCriteria)
                            <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                        @endif
                    </div>
                @else
                    <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                        <table class="table ops-table">
                            <colgroup>
                                @foreach ([66, 140, 100, 90, 60, 64, 56, 74, 92, 130, 90, 90, 100, 104, 100, 100] as $w)
                                    <col style="width: {{ $w }}px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nota</th>
                                    <th>Ordens</th>
                                    <th>DD</th>
                                    <th>Arquivos</th>
                                    <th>MMGD</th>
                                    <th>Postes</th>
                                    <th class="is-left">Rubrica</th>
                                    <th class="is-left">Município</th>
                                    <th class="is-left">Descrição</th>
                                    <th>Despachado</th>
                                    <th>Atribuído</th>
                                    <th>Informe</th>
                                    <th>ADS</th>
                                    <th>Status</th>
                                    <th><span class="visually-hidden">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                @foreach ($rows as $row)
                                    <tr wire:key="prod-{{ $row['id'] }}"
                                        @if ($row['dblclick']) wire:dblclick="$emitTo('{{ $row['dblclick']['component'] }}', 'show_form', {{ $row['dblclick']['id'] }})" @endif
                                        @class(['ops-row-priority' => $row['priority'], 'ops-row-late' => !$row['priority'] && $row['rejected']])>

                                        <td>
                                            <x-ops.badge :tone="$row['isPartial'] || $row['isConnection'] ? 'amber' : 'green'" :label="$row['isPartial'] ? 'Parcial' : 'Final'"
                                                :hint="$row['isPartial'] ? 'Informe parcial' : ($row['isConnection'] ? 'Informe final de ligação' : 'Informe final')" />
                                        </td>

                                        <td>
                                            @if ($row['d5'])
                                                <x-ops.note :note="$row['note']" kind="d5" tone="green" hint="D5 concluída – aguardando fiscalização"
                                                    wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $row['noteId'] }})">
                                                    @include('livewire.services.supervision.partials.note-extras')
                                                </x-ops.note>
                                            @else
                                                <x-ops.note :note="$row['note']">
                                                    @include('livewire.services.supervision.partials.note-extras')
                                                </x-ops.note>
                                            @endif
                                        </td>

                                        <td><div class="ops-cell-stack is-center">@forelse ($row['orders'] as $order)<span class="fw-semibold">{{ $order }}</span>@empty<span class="ops-empty">—</span>@endforelse</div></td>

                                        <td>
                                            @if ($row['dd'])
                                                <a class="fw-semibold" href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $row['dd'] }}" target="_blank" rel="noopener" title="Abrir a DD no WPA">{{ $row['dd'] }}</a>
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td><x-files.select-download-list :files="$row['files']" /></td>

                                        <td>@if ($row['mmgd'])<x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />@else<span class="ops-empty">—</span>@endif</td>

                                        <td>@if ($row['postes'] !== null)<span class="fw-semibold">{{ $row['postes'] }}</span>@else<span class="ops-empty">—</span>@endif</td>

                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['rubrica'] }}">{{ $row['rubrica'] ?: '—' }}</span></td>
                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>

                                        <td><x-ops.date :date="$row['dispatchAt']" :sub="$row['dispatchAt'] ? $row['dispatchDays'] . ($row['dispatchDays'] === 1 ? ' dia' : ' dias') : null" hint="Data em que foi despachada e dias desde então" /></td>
                                        <td><x-ops.date :date="$row['attAt']" :sub="$row['attAt'] ? $row['attDays'] . ($row['attDays'] === 1 ? ' dia' : ' dias') : null" hint="Data em que foi atribuída a você e dias desde então" /></td>

                                        <td>
                                            <x-ops.date :date="$row['informedAt']" :tone="$row['informedTone']" :sub="$row['cycleTwo'] ? 'D5' : $row['informedDays'] . ($row['informedDays'] === 1 ? ' dia' : ' dias')"
                                                :hint="$row['cycleTwo'] ? 'D5 de 2º ciclo: o prazo do informe não é cobrado aqui' : 'Dias desde o informe (verde até 20 dias, âmbar de 21 a 27, vermelho a partir de 28)'" />
                                        </td>

                                        <td>
                                            <div class="ops-cell-stack is-center">
                                                @if ($row['adsAt'])
                                                    <x-ops.date :date="$row['adsAt']" :tone="$row['adsTone']" :sub="$row['cycleTwo'] ? 'D5' : $row['adsDays'] . ($row['adsDays'] === 1 ? ' dia' : ' dias')"
                                                        :hint="$row['cycleTwo'] ? 'D5 de 2º ciclo: o prazo da ADS não é cobrado aqui' : 'Entrega da ADS e dias desde então (verde até 6 dias, âmbar até 9, vermelho acima de 9)'" />
                                                @elseif ($row['adsTacit'])
                                                    <x-ops.badge sm tone="red" label="Aguardando entrega" hint="ADS tácita ainda não entregue" />
                                                @else
                                                    <span class="ops-empty">—</span>
                                                @endif

                                                @if ($row['adsTacit'])
                                                    <x-ops.badge sm :tone="$row['adsDelivered'] ? 'muted' : 'amber'" label="Tácita"
                                                        :hint="$row['adsDelivered'] ? 'ADS entregue de forma tácita; está em Solicitação de ADS, na lista de Histórico.' : 'ADS tácita ainda não entregue'" />
                                                @elseif ($row['adsLegacy'])
                                                    <x-ops.badge sm tone="muted" label="Legada" hint="ADS do sistema antigo" />
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            @if ($row['status']['history'])
                                                <x-ops.badge class="ops-clickable" :tone="$row['status']['tone']" :label="$row['status']['label']" :hint="$row['status']['hint']"
                                                    wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['statusCode'] }})" />
                                            @else
                                                <x-ops.badge :tone="$row['status']['tone']" :label="$row['status']['label']" :hint="$row['status']['hint']" />
                                            @endif
                                        </td>

                                        <td>
                                            <div class="d-inline-flex gap-1">
                                                @if ($row['canFinish'] ?? false)
                                                    <button type="button" class="btn btn-danger btn-sm ops-action" wire:click.prevent="askFinishCanceled({{ $row['id'] }})" title="O informe foi cancelado: finaliza a atividade por cancelamento e registra na análise quem cancelou e quando">
                                                        <i class="ri-checkbox-circle-line" aria-hidden="true"></i> Finalizar
                                                    </button>
                                                @endif
                                                @if ($row['canPlay'])
                                                    <button type="button" class="btn btn-primary btn-sm ops-action" title="Abrir a atividade"
                                                        wire:click="$emitTo('services.supervision.forms.jobform', 'showProduction', {{ $row['id'] }})">
                                                        <i class="ri-play-circle-line" aria-hidden="true"></i> Abrir
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" title="Transferir para outro usuário" aria-label="Transferir"
                                                        wire:click.prevent="goTransferProd({{ $row['id'] }})">
                                                        <i class="ri-exchange-fill" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($row['canReturn'])
                                                    <button type="button" class="btn btn-outline-danger btn-sm" title="Devolver o informe para a empreiteira" aria-label="Devolver o informe"
                                                        wire:click.prevent="$emitTo('production.return.return-work', 'toReturn', {{ $row['id'] }})">
                                                        <i class="ri-delete-back-2-fill" aria-hidden="true"></i>
                                                    </button>
                                                @endif
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
        </div>

        <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="nav-transfer-tab" tabindex="0">
            @if ($transfersLoaded)
                @livewire('components.transprod.translist', ['service' => $service->id])
            @endif
        </div>
    </div>

    {{-- Modais/Componentes auxiliares --}}
    @livewire('components.transprod.transprodlev', key('Transfer_production'))
    @livewire('services.supervision.forms.jobform', key('JobForm'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('partner.show.show-work-form', key('WorkFormCompany'))
    @livewire('components.workform.canceled-info', key('WorkCanceledInfo'))
    @livewire('partner.show.show-partial-info', key('PartialInfo'))
    @livewire('components.d5.d5details', key('ViewD5Details'))
    @livewire('production.return.return-work', key('returnWorkfomr'))
</div>
