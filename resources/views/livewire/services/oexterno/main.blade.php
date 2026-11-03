@php
    $hasCriteria = $this->hasCriteria();
@endphp

{{-- Lista operacional de Serviços: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, tipo, status, filtros) → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        title="Entidade externa a protocolar"
        subtitle="Notas e OVs prontas para iniciar ou consultar a tratativa com o órgão externo. Duplo clique na linha abre a nota."
        :updated-at="$updatedAt">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, pedido, material ou protocolo" label="Buscar nota, pedido, material ou protocolo">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="oexternoTypeNote" label="Tipo de nota" wire:model="typeNote" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />
            <x-ops.segmented name="oexternoStatus" label="Status SAP" wire:model="statusFilter" :options="['' => 'Todos', '11' => 'Status 11', '20' => 'Status 20']" />

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

    <section class="mp-panel" aria-label="Entidade externa a protocolar">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Entidade externa a protocolar</h2>
                <div class="mp-panel-sub">
                    {{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }} · sem busca, só notas nos status 11 e 20 que ainda não estão nas caixas de Taxa, Retorno ou Pagamento
                </div>
            </div>
            <span class="mp-pill" wire:loading.delay>Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="12" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Nenhuma nota disponível para protocolar'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Aguarde novas notas nesta etapa.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 116px">
                        <col style="width: 70px">
                        <col style="width: 80px">
                        <col style="width: 130px">
                        <col style="width: 130px">
                        <col style="width: 92px">
                        <col style="width: 70px">
                        <col style="width: 120px">
                        <col style="width: 96px">
                        <col style="width: 80px">
                        <col style="width: 140px">
                        <col style="width: 108px">
                        <col style="width: 108px">
                        <col style="width: 96px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Arquivos</th>
                            <th>Protocolo</th>
                            <th>Último protocolo</th>
                            <th class="is-left">Entidade</th>
                            <th>Rubrica</th>
                            <th>Grupo 2</th>
                            <th class="is-left">Município</th>
                            <th>Pedido</th>
                            <th>Status</th>
                            <th>Pasta atual</th>
                            <th class="ops-clickable" wire:click="setColumn('dt_status')" title="Ordenar por dias no status">
                                Dias no status
                                @if ($column == 'dt_status')
                                    <i class="{{ $direction == 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}" aria-hidden="true"></i>
                                @endif
                            </th>
                            <th class="ops-clickable" wire:click="setColumn('dt_created')" title="Ordenar por total de dias">
                                Total de dias
                                @if ($column == 'dt_created')
                                    <i class="{{ $direction == 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}" aria-hidden="true"></i>
                                @endif
                            </th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['id'] }}" wire:dblclick="navigateTo('{{ $row['note'] }}')">
                                <td><x-ops.note :note="$row['note']" /></td>

                                <td>
                                    {{-- Lista de arquivos da nota (componente compartilhado) --}}
                                    <x-files.select-download-list :files="$row['files']" />
                                </td>

                                <td>
                                    <x-ops.badge :tone="$row['proto']['tone']" :label="$row['proto']['done'] . ' / ' . $row['proto']['total']" hint="Protocolos concluídos / total de protocolos da nota" />
                                </td>

                                <td>
                                    @if ($row['lastProto'])
                                        <div class="ops-cell-stack is-center">
                                            <span class="fw-semibold">{{ $row['lastProto'] }}</span>
                                            <x-ops.date :date="$row['lastProtoAt']" time hint="Data do último protocolo" />
                                        </div>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td class="is-left"><span class="d-block text-truncate" title="{{ $row['entity'] }}">{{ $row['entity'] ?: '—' }}</span></td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>
                                <td>{{ $row['group2'] ?: '—' }}</td>
                                <td class="is-left">{{ $row['city'] ?: '—' }}</td>
                                <td>{{ $row['order'] ?: '—' }}</td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <span>{{ $row['status'] ?: '—' }}</span>
                                        @if ($row['centerjob'])
                                            <span class="ops-sub">{{ $row['centerjob'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td><x-ops.badge :tone="$row['folder']['tone']" :label="$row['folder']['label']" hint="Pasta em que a nota está hoje na Entidade Externa" /></td>

                                <td><x-ops.date :date="$row['statusAt']" :tone="$row['statusTone']" :sub="$row['statusDays'] . ' dias'" hint="Dias no status atual (verde até 60 dias, âmbar de 61 a 120, vermelho acima de 120)" /></td>
                                <td><x-ops.date :date="$row['createdAt']" :tone="$row['totalTone']" :sub="($row['totalDays'] ?? 0) . ' dias'" hint="Dias desde a criação da nota (verde abaixo de 27 dias, âmbar de 27 a 30, vermelho acima de 30)" /></td>

                                <td>
                                    <button type="button" class="btn btn-primary btn-sm ops-action"
                                        wire:click.prevent="navigateTo('{{ $row['note'] }}')"
                                        title="Abrir a nota para protocolar">
                                        <i class="ri-external-link-line" aria-hidden="true"></i> Abrir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$lists" :per-page="$perPage" :sizes="[25, 50, 100, 200, 500]" />
    </section>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="buscarMultiLabel">Buscar várias notas</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota por linha, ou separe por espaço, vírgula ou ponto e vírgula. A busca também confere pedido, material, grupo 2 e protocolo.</p>
                    <label class="form-label" for="advanceSearch">Notas ou ordens</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Livewire Components --}}
    @livewire('services.oexterno.actions.protocols', key('external_protocols'))
</div>
