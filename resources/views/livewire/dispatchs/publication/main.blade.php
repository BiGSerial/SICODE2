@php
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($typeNote) || $not_assigned || !empty($multiSearch);
@endphp

{{-- Lista operacional: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, tipo, atalhos, filtros) → painel com a tabela.
     Publicação libera o informe pela OP20, independente da Fiscalização simultânea. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="'Despacho · ' . $service->service . ($contractCompanyName ? ' · ' . $contractCompanyName : '')"
        :title="'Lista para ' . mb_strtolower($service->service)"
        :subtitle="$subtitle"
        :updated-at="$update ? $last_update : null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, material ou pedido" label="Buscar nota, material ou pedido">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="publicationTypeNote" label="Tipo de nota" wire:model="typeNote" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <x-ops.toggle label="Só livres" icon="ri-user-unfollow-line" :on="$not_assigned" wire:click.prevent="filterStatus" title="Mostra só o que ainda não foi atribuído a ninguém" />

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
                    · informe publicável por OP20 liberada
                </div>
            </div>
            <span class="mp-pill" wire:loading.delay>Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Sem notas em ' . $service->service . ' no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Quando houver informes liberados para publicação, as notas aparecem aqui.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        @foreach ([38, 150, 64, 104, 150, 104, 160, 120, 96, 112, 130, 100, 112] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:model="selectall" aria-label="Selecionar todas as linhas da página"></th>
                            <th>Nota</th>
                            <th>Inf. digitação</th>
                            <th>Rubrica</th>
                            <th>Material</th>
                            <th>Pedido</th>
                            <th class="is-left">Empresa</th>
                            <th>Município</th>
                            <th>Execução</th>
                            <th>Informe</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['key'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                <td>
                                    <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['key'] }}" wire:model.defer="selected" @disabled(!$row['canSelect']) aria-label="Selecionar {{ $row['note'] }}" @change="recount()">
                                </td>

                                <td>
                                    <x-ops.note :note="$row['note']" :sub="$row['workReportId'] ? 'Informe #' . $row['workReportId'] : null">
                                        @if ($row['is45'])
                                            <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" hint="Nota expressa: prazo de execução de 45 dias" />
                                        @endif
                                        @if (isset($legalNoteIds[$row['noteId']]))
                                            <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-publication-main-' . $row['key']" />
                                        @endif
                                    </x-ops.note>
                                </td>

                                <td>
                                    @if ($row['ramalOnly'])
                                        <x-ops.badge sm tone="amber" icon="ri-alert-line" label="Só SMC" hint="Há informe de digitação (SMC), mas ainda não há informe final" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>
                                <td>{{ $row['pedido'] ?: '—' }}</td>

                                <td class="is-left"><span class="fw-semibold d-block text-truncate" title="{{ $row['company'] }}">{{ $row['company'] ?: '—' }}</span></td>

                                <td>{{ $row['city'] ?: '—' }}</td>

                                <td><x-ops.date :date="$row['executedAt']" hint="Data de execução da obra" /></td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <x-ops.date :date="$row['informedAt']" time hint="Data e hora do informe" />
                                        @if ($row['informedDays'] !== null)
                                            <span class="ops-sub">{{ $row['informedDays'] }} dia(s)</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <span>{{ $row['center'] ?: ($row['status'] ?: '—') }}</span>
                                        @if ($row['center'] && $row['status'])
                                            <span class="ops-sub">{{ $row['status'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :hint="\App\Support\Ops\Deadline::remainingText($row['dueDays']) . ' (verde acima de 15 dias, âmbar até 15, vermelho vencido)'" /></td>

                                <td>
                                    @if ($row['canDispatch'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="get_single_note({{ json_encode($row['key']) }})"
                                            title="Atribuir a um responsável">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                                        </button>
                                        @if ($row['command'] && $row['assignee'])
                                            <span class="ops-sub mt-1">{{ $row['assignee'] }}</span>
                                        @endif
                                    @else
                                        <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['assignee'] ?? \App\Support\Ops\RowState::label($row['state'])" :hint="$row['reason']" icon="ri-user-line" />
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
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="go_att_mass"><i class="ri-user-add-line" aria-hidden="true"></i> Atribuir selecionadas</button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click.prevent="export_excel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar selecionadas</button>
        </x-ops.selection-bar>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <span class="modal-eyebrow">Busca</span>
                    <h5 class="modal-title" id="buscarMultiLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole as notas ou ordens, uma por linha ou separadas por espaço, vírgula ou ponto e vírgula.</p>
                    <label class="form-label fw-semibold text-uppercase" for="advanceSearch">Notas ou ordens</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch" placeholder="Informe uma nota por linha"></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusPublication" wire:model.defer="all_services">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusPublication">Buscar em qualquer status</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger btn-dash" wire:click="clean">Limpar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-' . $service->uuid))
</div>
