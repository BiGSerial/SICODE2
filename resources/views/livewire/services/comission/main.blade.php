@php
    use App\Support\Ops\Tone;
    use App\Custom\Notestatus;

    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type);
    $dueHint = 'Prazo real: já desconta o tempo em status que não conta. Verde com 10 dias ou mais para vencer, âmbar de 6 a 9, vermelho com menos de 6 ou vencido.';
@endphp

{{-- Minhas atividades no padrão de listas operacionais (docs/PADRAO_LISTAS_OPERACIONAIS.md). Hero → abas → ferramentas → painel. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Minhas atividades em ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs atribuídas a você. Inicie a atividade ou transfira para outra pessoa."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <div class="mp-presets mb-3" role="tablist" aria-label="Acompanhamento">
        <button type="button" role="tab" class="mp-preset {{ $tab === 'producao' ? 'is-active' : '' }}" wire:click="setTab('producao')" aria-selected="{{ $tab === 'producao' ? 'true' : 'false' }}">Produção</button>
        <button type="button" role="tab" class="mp-preset {{ $tab === 'transferencias' ? 'is-active' : '' }}" wire:click="setTab('transferencias')" aria-selected="{{ $tab === 'transferencias' ? 'true' : 'false' }}">
            Transferências @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
        </button>
    </div>

    @if ($tab === 'transferencias')
        @livewire('components.transprod.translist', ['service' => $service->id], key('acc-translist-' . $service->id))
    @else

            <section class="ops-toolbar" aria-label="Filtros da lista">
                <div class="ops-toolbar-row">
                    <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou material" label="Buscar nota ou material" />

                    <x-ops.segmented name="servicesComissionNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

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

            <section class="mp-panel" aria-label="Minhas atividades em {{ $service->service }}">
                <div class="mp-panel-head">
                    <div>
                        <h2 class="mp-panel-title">Acompanhamento · {{ mb_strtolower($service->service) }}</h2>
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
                                @foreach ([128, 100, 70, 84, 110, 130, 84, 200, 90, 110, 120, 170] as $w)
                                    <col style="width: {{ $w }}px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>DD</th>
                                    <th>MMGD</th>
                                    <th>Grupo 2</th>
                                    <th>Rubrica</th>
                                    <th>Município</th>
                                    <th>Zona</th>
                                    <th class="is-left">Descrição</th>
                                    <th>Dias atribuído</th>
                                    <th>Prazo real</th>
                                    <th>Status</th>
                                    <th><span class="visually-hidden">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="prod-{{ $row['id'] }}" @class(['ops-row-priority' => $row['priority']])>
                                        <td>
                                            <x-ops.note :note="$row['note']">
                                                @if ($row['priority'])
                                                    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
                                                        wire:click.prevent="$emit('infoPriority', '{{ $row['id'] }}')" />
                                                @endif
                                            </x-ops.note>
                                        </td>

                                        <td>
                                            @if ($row['dd'])
                                                <a class="fw-semibold" href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $row['dd'] }}" title="Abrir a DD no WPA">{{ $row['dd'] }}</a>
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($row['mmgd'])
                                                <x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td>{{ $row['group2'] ?: '—' }}</td>
                                        <td>{{ $row['rubrica'] ?: '—' }}</td>
                                        <td>{{ $row['city'] ?: '—' }}</td>
                                        <td>{{ $row['zone'] ?: '—' }}</td>
                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                        <td>{{ $row['attDays'] !== null ? $row['attDays'] . ' dias' : '—' }}</td>

                                        <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['dueSub']" :hint="$dueHint . ' Já passaram ' . $row['elapsedDays'] . ' de 30 dias.'" /></td>

                                        <td>
                                            @if ($row['waiting'])
                                                <x-ops.badge tone="amber" label="Aguardando despacho" hint="Transferida: aguarda o despacho para seguir" />
                                            @else
                                                <x-ops.badge class="ops-clickable" :tone="Tone::forProductionStatus($row['status'])" :label="Notestatus::status($row['status'])->status" hint="Clique para ver o histórico de status"
                                                    wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['status'] }})" />
                                            @endif
                                        </td>

                                        <td>
                                            @if ($row['canAct'])
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="getAnalise({{ $row['id'] }}, {{ $row['noteId'] }})" title="Iniciar a atividade desta Nota/OV">
                                                        <i class="ri-play-circle-line" aria-hidden="true"></i> Iniciar
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click.prevent="goTransferProd({{ $row['id'] }})" title="Transferir esta Nota/OV para outra pessoa">
                                                        <i class="ri-exchange-fill" aria-hidden="true"></i> Transferir
                                                    </button>
                                                </div>
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
    @endif

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="analiseFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list h-100">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Atividade em andamento</span>
                        <h5 class="modal-title" id="analiseFormLabel">{{ $service->service }}</h5>
                    </div>
                </div>
                <div class="modal-body">
                    @livewire('services.comission.forms.analise', key('comissionamento-form'))
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="pause_note" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="pauseNoteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Pausar atividade</span>
                        <h5 class="modal-title" id="pauseNoteLabel">{{ $service->service }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('components.pausenote.pausenote')
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL COMPLEMENTS TRANSFER NOTE --}}
    @livewire('components.transprod.transprodlev', key('Transfer_production'))
    @livewire('components.status.show-status', key('show_status_note'))

    <div wire:init="checkOpen"></div>
</div>
