@php
    use App\Helpers\SelectOptions;
    use App\Support\SicodeRules;

    $bulk       = $profile['bulk'];
    $groupTwo   = $profile['group2'];
    $pauseCol   = $profile['pauseColumn'];
    $activity   = $service->service;
    $hasRows    = $rows->isNotEmpty();
@endphp

{{-- Acompanhamento de Serviços (a fila de trabalho do usuário): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. O que muda por atividade vem do
     ActivityAccompanyProfile. Hero → ferramentas (busca, filtros) → abas → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()" wire:init="checkOpen">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $activity"
        :title="'Acompanhamento de ' . mb_strtolower($activity)"
        subtitle="As Notas e OVs que estão com você. Inicie, continue ou transfira cada uma; o prazo real é a data de vencimento da nota, com os dias que faltam.">
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros do acompanhamento">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, pedido ou material" label="Buscar nota, pedido ou material">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            @if ($adminUsers)
                <label class="ops-toolbar-label mb-0" for="acc-user">Ver fila de</label>
                <select id="acc-user" class="form-select form-select-sm w-auto" wire:model="user_s" title="Visão administrativa: a fila de outro usuário">
                    <option value="">Minha fila</option>
                    @foreach ($adminUsers as $u)
                        <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['total'] }})</option>
                    @endforeach
                </select>
            @endif

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters() || $multiSearch || $viewingName)
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
                    @if ($viewingName)
                        <span class="ops-chip" wire:key="chip-user">
                            <span><strong>Visão administrativa:</strong> fila de {{ $viewingName }}</span>
                            <button type="button" class="btn-close" aria-label="Voltar para a minha fila" wire:click="$set('user_s', '')"></button>
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
                    <h2 class="mp-panel-title">{{ $viewingName ? 'Fila de ' . $viewingName : 'Minha fila' }}</h2>
                    <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }} · prioridade primeiro, depois a mais antiga</div>
                </div>
                <span class="mp-pill" wire:loading.delay>Atualizando…</span>
            </div>

            <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="9" />

            @if (!$hasRows)
                <div class="mp-panel-body">
                    <x-closure.empty-state
                        :title="$hasCriteria ? 'Nenhum resultado para os critérios escolhidos' : ($viewingName ? $viewingName . ' não tem tarefa atribuída em ' . $activity : 'Você não tem tarefa atribuída em ' . $activity)"
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
                            @if ($bulk)<col style="width: 36px">@endif
                            <col style="width: 140px">
                            <col style="width: 96px">
                            <col style="width: 96px">
                            <col style="width: 96px">
                            <col style="width: 130px">
                            <col style="width: 70px">
                            @if ($groupTwo)<col style="width: 120px">@endif
                            <col style="width: 160px">
                            <col style="width: 96px">
                            <col style="width: 90px">
                            @if ($pauseCol)<col style="width: 110px">@endif
                            <col style="width: 116px">
                            <col style="width: 176px">
                        </colgroup>
                        <thead>
                            <tr>
                                @if ($bulk)
                                    <th>
                                        <input class="form-check-input" type="checkbox" aria-label="Selecionar todas as linhas da página"
                                            @change="$root.querySelectorAll('input[data-ops-row]').forEach(c => { if (c.checked !== $event.target.checked) { c.checked = $event.target.checked; c.dispatchEvent(new Event('change', { bubbles: true })); } }); recount()">
                                    </th>
                                @endif
                                <th>Nota</th>
                                <th>Criado em</th>
                                <th>Pedido</th>
                                <th>Rubrica</th>
                                <th class="is-left">Município</th>
                                <th>Zona</th>
                                @if ($groupTwo)<th>Grupo 2</th>@endif
                                <th class="is-left">Descrição</th>
                                <th>Atribuído</th>
                                <th>Prazo real</th>
                                @if ($pauseCol)<th>Em pausa</th>@endif
                                <th>Status</th>
                                <th><span class="visually-hidden">Ação</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="acc-{{ $row['id'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free', 'ops-row-priority' => $row['priority']])>
                                    @if ($bulk)
                                        <td>
                                            <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['id'] }}" wire:model.defer="selected" @change="recount()" @disabled(!$row['canAct']) aria-label="Selecionar a nota {{ $row['note'] }}">
                                        </td>
                                    @endif

                                    <td>
                                        <x-ops.note :note="$row['note']">
                                            @if ($row['priority'])
                                                <x-ops.badge sm tone="red" icon="ri-alert-fill" label="Prioridade" class="ops-clickable" hint="Clique para ver a informação da prioridade desta Nota/OV" wire:click.prevent="$emit('infoPriority', '{{ $row['id'] }}')" />
                                            @endif
                                            <x-legal.note-demand-tags :demands="$row['legal']" :row-key="'services-accompany-' . $profile['key'] . '-' . $row['id']" />
                                        </x-ops.note>
                                    </td>

                                    <td><x-ops.date :date="$row['createdAt']" :sub="$row['createdDays'] !== null ? $row['createdDays'] . ' d' : null" hint="Data de criação da nota e dias desde então" /></td>
                                    <td>{{ $row['order'] ?: '—' }}</td>
                                    <td>{{ $row['rubrica'] ?: '—' }}</td>
                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span></td>
                                    <td>{{ $row['zone'] ?: '—' }}</td>
                                    @if ($groupTwo)<td>{{ $row['group2'] ?: '—' }}</td>@endif
                                    <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                    <td><x-ops.age :date="$row['attAt']" :days="$row['attDays']" rule="assigned" /></td>
                                    <td><x-ops.prazo :left="$row['prazoLeft']" :source="$profile['prazo']" /></td>
                                    @if ($pauseCol)
                                        <td>
                                            @if ($row['paused'])
                                                <x-ops.badge tone="amber" :label="'há ' . $row['pausedFor']" hint="Há quanto tempo a nota está pausada" />
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <x-ops.badge :tone="$row['statusTone']" :label="$row['statusLabel']" class="ops-clickable" hint="Ver o histórico deste status"
                                            wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['status'] }})" />
                                    </td>
                                    <td>
                                        @if ($row['canAct'])
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="getAnalise({{ $row['id'] }}, {{ $row['noteId'] }})" title="Abrir o formulário da atividade">
                                                    <i class="ri-play-circle-line" aria-hidden="true"></i> {{ $row['actionLabel'] }}
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click.prevent="goTransferProd({{ $row['id'] }})" title="Transferir esta Nota/OV para outro usuário">
                                                    <i class="ri-arrow-left-right-line" aria-hidden="true"></i><span class="visually-hidden">Transferir</span>
                                                </button>
                                            </div>
                                        @else
                                            <x-ops.badge tone="blue" label="Bloqueada" hint="Esta Nota/OV está bloqueada e não pode ser iniciada agora" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-ops.pager :paginator="$lists" :per-page="$perPage" :sizes="$pageSizes" />
        </section>
    @endif

    @if ($bulk)
        <div wire:ignore>
            <x-ops.selection-bar>
                <button type="button" class="btn btn-light btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#bulk_finish_modal"><i class="ri-checkbox-multiple-line" aria-hidden="true"></i> Encerrar selecionadas</button>
            </x-ops.selection-bar>
        </div>
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
                    <label class="form-label fw-semibold" for="advanceSearch">Notas ou pedidos</label>
                    <textarea class="form-control" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    @if ($bulk)
        <div wire:ignore.self class="modal fade ops-modal" id="bulk_finish_modal" tabindex="-1" aria-labelledby="bulkFinishLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content closure-dash ops-list">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkFinishLabel"><span class="modal-eyebrow">{{ $activity }}</span>Encerramento em massa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="ops-modal-note mb-3">Você selecionou <strong>{{ count($selected) }}</strong> registro(s) para encerrar. A operação não pode ser desfeita.</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-{{ $bulk === 'reverse' ? '3' : '4' }}">
                                <label class="form-label fw-semibold" for="bulkMmgd">MMGD?</label>
                                <select class="form-select" id="bulkMmgd" wire:model.defer="bulkMmgd">
                                    <option value="">Selecione</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-{{ $bulk === 'reverse' ? '3' : '4' }}">
                                <label class="form-label fw-semibold" for="bulkIs45">Art. 90 (45 dias)?</label>
                                <select class="form-select" id="bulkIs45" wire:model.defer="bulkIs45">
                                    <option value="">Selecione</option>
                                    <option value="1">SIM</option>
                                    <option value="0">NÃO</option>
                                </select>
                            </div>
                            @if ($bulk === 'reverse')
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold" for="bulkPreResult">Tipo de estudo</label>
                                    <select class="form-select" id="bulkPreResult" wire:model="bulkPreResult">
                                        <option value="">Selecione</option>
                                        @foreach (SelectOptions::getReverseFluxConclusion() as $option)
                                            <option value="{{ $option->value }}">{{ $option->reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold" for="bulkConclusion">Conclusão</label>
                                    <select class="form-select" id="bulkConclusion" wire:model.defer="bulkConclusion" @disabled(!$bulkPreResult)>
                                        <option value="">Selecione</option>
                                        @foreach (SelectOptions::getReverseFluxEnd($bulkPreResult) as $option)
                                            <option value="{{ $option->value }}">{{ $option->reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold" for="bulkConclusion">Conclusão</label>
                                    <select class="form-select" id="bulkConclusion" wire:model="bulkConclusion">
                                        <option value="">Selecione</option>
                                        @foreach (($bulk === 'preanalysis' ? SicodeRules::preAnalysisConclusionOptions() : SicodeRules::analysisConclusionOptions()) as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="bulkInfo">Informações</label>
                                <textarea class="form-control" id="bulkInfo" rows="5" wire:model.defer="bulkInfo"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="confirmBulkClose"><i class="ri-checkbox-multiple-line" aria-hidden="true"></i> Encerrar em massa</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Formulário da atividade (tela cheia) e pausa --}}
    <div wire:ignore.self class="modal fade ops-modal" id="{{ $profile['modal'] }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="accFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <h5 class="modal-title" id="accFormLabel"><span class="modal-eyebrow">Acompanhamento</span>{{ mb_strtoupper($activity) }}</h5>
                </div>
                <div class="modal-body">
                    @livewire($profile['form'], key('analise-form'))
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
    @livewire('components.status.show-status', key('show_status_note'))
</div>
