@php
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = filled($search);
@endphp

{{-- Minha produção em Levantamento (Serviços). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Minha produção em ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs atribuídas a você. Inicie a análise, acompanhe os prazos pela cor ou transfira para outro usuário."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#quality_available_modal">
                <i class="ri-shield-check-line" aria-hidden="true"></i> Buscar na Qualidade
            </button>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Busca da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou material" label="Buscar nota ou material" />

            <span class="ops-toolbar-spacer"></span>

            @if ($exportFeedback)
                <x-ops.badge :tone="$exportFailed ? 'red' : 'green'" :icon="$exportFailed ? 'ri-error-warning-line' : 'ri-check-line'" :label="$exportFeedback" />
            @endif

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar busca</button>
            @endif
        </div>
    </section>

    <ul class="nav nav-tabs mb-3" id="tab-levantamento" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-prod-tab" data-bs-toggle="tab" data-bs-target="#tab-prod"
                type="button" role="tab" aria-controls="tab-prod" aria-selected="true"
                wire:click.prevent="$emit('refresh_accomany')">
                Produção
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-transf-tab" data-bs-toggle="tab" data-bs-target="#tab-transf"
                type="button" role="tab" aria-controls="tab-transf" aria-selected="false"
                wire:click.prevent="$emit('refresh_translist')">
                Transferências
                @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
            </button>
        </li>
    </ul>

    <div class="tab-content" id="tab-content-levantamento">
        {{-- PRODUÇÃO --}}
        <div class="tab-pane fade show active" id="tab-prod" role="tabpanel" aria-labelledby="tab-prod-tab">
            <section class="mp-panel" aria-label="Produção em {{ $service->service }}">
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
                            :title="$hasCriteria ? 'Nenhum resultado para a busca' : 'Nenhuma tarefa atribuída em ' . $service->service"
                            :text="$hasCriteria ? 'Ajuste ou limpe a busca para ver mais notas.' : 'Quando uma nota for atribuída a você, ela aparece aqui.'"
                            icon="ri-inbox-line" />
                        @if ($hasCriteria)
                            <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar busca</button></div>
                        @endif
                    </div>
                @else
                    <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                        <table class="table ops-table">
                            <colgroup>
                                @foreach ([138, 100, 96, 150, 80, 200, 100, 112, 150, 240] as $w)
                                    <col style="width: {{ $w }}px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>DD</th>
                                    <th>Rubrica</th>
                                    <th class="is-left">Município</th>
                                    <th>Grupo 2</th>
                                    <th class="is-left">Descrição</th>
                                    <th>Em atribuição</th>
                                    <th>Prazo real</th>
                                    <th>Status</th>
                                    <th><span class="visually-hidden">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="svc-{{ $row['id'] }}">
                                        <td>
                                            <x-ops.note :note="$row['note']">
                                                @if ($row['priority'])
                                                    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Nota com prioridade – clique para ver o motivo"
                                                        wire:click="$emit('infoPriority', '{{ $row['id'] }}')" />
                                                @endif
                                            </x-ops.note>
                                        </td>

                                        <td>
                                            @if ($row['dd'])
                                                <a href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $row['dd'] }}" class="link-primary fw-bold" target="_blank" rel="noopener" title="Abrir a DD {{ $row['dd'] }} no WPA">{{ $row['dd'] }}</a>
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td>{{ $row['rubrica'] ?: '—' }}</td>
                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                        <td>{{ $row['group2'] ?: '—' }}</td>
                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>

                                        <td>
                                            @if ($row['attDays'] !== null)
                                                <x-ops.badge :tone="$row['attTone']" :label="$row['attDays'] . ' d'" hint="Dias desde que foi atribuída a você. Verde até 5 dias, âmbar até 8, vermelho acima." />
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            <x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['dueAge'] !== null ? $row['dueAge'] . ' d' : null"
                                                hint="Criação da nota + 30 dias, e dias corridos desde a criação. Verde até 20 dias, âmbar até 30, vermelho acima." />
                                        </td>

                                        <td>
                                            <div class="ops-cell-stack is-center">
                                                @if ($row['waitingDispatch'])
                                                    <x-ops.badge tone="amber" label="Aguardando despacho" hint="Transferida e aguardando o despacho da DD" />
                                                @else
                                                    <x-ops.badge class="ops-clickable" :tone="$row['statusTone']" :label="$row['statusLabel']" hint="Clique para ver o histórico de status"
                                                        wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['status'] }})" />
                                                @endif
                                                @if ($row['quality'])
                                                    <x-ops.badge sm tone="teal" icon="ri-shield-check-line" :label="$row['quality']" hint="Atividade originada pela Qualidade: finalize pelo formulário da Qualidade." />
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            @if ($row['canAct'])
                                                <div class="d-inline-flex flex-wrap gap-1 justify-content-center">
                                                    @if ($row['quality'])
                                                        <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click.prevent="getQualityDetails({{ $row['id'] }})" title="Ver detalhes (somente leitura)">
                                                            <i class="ri-eye-line" aria-hidden="true"></i> Ver detalhes
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="getAnalise({{ $row['id'] }}, {{ $row['noteId'] }})" title="Iniciar a análise desta nota">
                                                        <i class="ri-play-circle-line" aria-hidden="true"></i> Iniciar
                                                    </button>
                                                    @if (!$row['quality'])
                                                        <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click.prevent="goTransferProd({{ $row['id'] }})" title="Transferir para outro usuário">
                                                            <i class="ri-exchange-fill" aria-hidden="true"></i> Transferir
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <x-ops.pager :paginator="$lists" :per-page="$perPage" :sizes="[30, 50, 100, 250]" />
            </section>
        </div>

        {{-- TRANSFERÊNCIAS --}}
        <div class="tab-pane fade" id="tab-transf" role="tabpanel" aria-labelledby="tab-transf-tab">
            @livewire('components.transprod.translist', ['service' => $service->id])
        </div>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="quality_available_modal" tabindex="-1" aria-labelledby="qualityAvailableLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Qualidade</span>
                        <h5 class="modal-title" id="qualityAvailableLabel">Obras disponíveis na Qualidade</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Consulte as notas da fila de despacho N1 e assuma apenas as que deseja executar. Ao assumir, a obra sai da fila N1 e entra na sua produção com todos os registros de Qualidade. Itens já atribuídos ficam bloqueados.</p>

                    <label for="quality_notes" class="form-label">Notas ou pedidos</label>
                    <textarea id="quality_notes" wire:model.defer="qualityNotes" class="form-control" rows="5" placeholder="Cole uma nota por linha, ou separe por vírgula e ponto e vírgula"></textarea>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <span class="ops-sub">Busca em massa de até 2.000 itens</span>
                        <button type="button" class="btn btn-primary btn-dash" wire:click="searchQualityAvailable" wire:loading.attr="disabled" wire:target="searchQualityAvailable">
                            <i class="ri-search-line" aria-hidden="true"></i>
                            <span wire:loading.remove wire:target="searchQualityAvailable">Buscar na fila</span>
                            <span wire:loading wire:target="searchQualityAvailable">Consultando…</span>
                        </button>
                    </div>

                    @if (count($qualityCandidates))
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                            <h6 class="mb-0 fw-bold">Resultado da consulta</h6>
                            <x-ops.badge tone="green" :label="collect($qualityCandidates)->where('available', true)->count() . ' disponível(is)'" />
                        </div>
                        <div class="ops-table-wrap">
                            <table class="table ops-table">
                                <colgroup>
                                    @foreach ([90, 120, 220, 160, 280] as $w)
                                        <col style="width: {{ $w }}px">
                                    @endforeach
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Selecionar</th>
                                        <th>Nota</th>
                                        <th class="is-left">Material</th>
                                        <th class="is-left">Empresa</th>
                                        <th class="is-left">Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($qualityCandidates as $item)
                                        <tr wire:key="quality-candidate-{{ $loop->index }}">
                                            <td><input class="form-check-input" type="checkbox" value="{{ $item['process_id'] }}" wire:model="selectedQualityProcessIds" @disabled(!$item['available']) aria-label="Selecionar nota {{ $item['note'] }}"></td>
                                            <td><span class="ops-note">{{ $item['note'] }}</span></td>
                                            <td class="is-left"><span class="d-block text-truncate" title="{{ $item['material'] }}">{{ $item['material'] ?: '—' }}</span></td>
                                            <td class="is-left">{{ $item['company'] ?: '—' }}</td>
                                            <td class="is-left">
                                                <x-ops.badge :tone="$item['available'] ? 'green' : 'muted'" :label="$item['available'] ? 'Disponível' : 'Bloqueada'" />
                                                @if (!$item['available'])
                                                    <span class="ops-sub">{{ $item['reason'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="claimQualityAvailable" wire:loading.attr="disabled" wire:target="claimQualityAvailable">
                        <i class="ri-user-add-line" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="claimQualityAvailable">Importar selecionadas</span>
                        <span wire:loading wire:target="claimQualityAvailable">Importando…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="analise_form_label" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Análise</span>
                        <h5 class="modal-title" id="analise_form_label">{{ $service->service }}</h5>
                    </div>
                </div>
                <div class="modal-body">
                    @livewire('services.levantamento.forms.analise', key('levantamento-form'))
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="quality_closing_form" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="qualityClosingLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Qualidade</span>
                        <h5 class="modal-title" id="qualityClosingLabel">Atividade de Qualidade</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.quality-closing', key('quality-closing-form'))
                </div>
            </div>
        </div>
    </div>

    {{-- Componentes auxiliares --}}
    @livewire('components.transprod.transprodlev', key('Transfer_production'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('services.desenho.actions.responserinfo', key('responser_info_return'))

    {{-- Inicialização automática --}}
    <div wire:init="checkOpen"></div>
</div>
