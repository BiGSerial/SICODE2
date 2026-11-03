{{-- Lista operacional: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, origem, atalho, filtros) → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="'Despacho · ' . $service->service"
        :title="'Retorno interno de ' . mb_strtolower($service->service)"
        subtitle="Retornos internos (RI) aguardando atribuição. Marque as linhas para atribuir em massa ou use o botão de cada linha.">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, rubrica, município ou material" label="Buscar retornos" />

            <x-ops.segmented name="returnOrigin" label="Origem do retorno" wire:model="origin" :options="['' => 'Todas'] + $origins" />

            <x-ops.toggle label="Sem atribuição" icon="ri-user-unfollow-line" :on="$notAtt" wire:click.prevent="setNotAtt" title="Mostra só os retornos que ainda não têm atividade atribuída" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters() || $userName)
            <div class="ops-toolbar-row">
                <div class="ops-chips" aria-label="Filtros ativos">
                    @foreach ($this->listFilterChips() as $chip)
                        <span class="ops-chip" wire:key="chip-{{ $chip['key'] }}">
                            <span><strong>{{ $chip['label'] }}:</strong> {{ $chip['value'] }}</span>
                            <button type="button" class="btn-close" aria-label="Remover filtro {{ $chip['label'] }}" wire:click="removeListFilter('{{ $chip['key'] }}')"></button>
                        </span>
                    @endforeach
                    @if ($userName)
                        <span class="ops-chip" wire:key="chip-user">
                            <span><strong>Usuário:</strong> {{ $userName }}</span>
                            <button type="button" class="btn-close" aria-label="Remover filtro Usuário" wire:click="cleanUser"></button>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="Retornos internos de {{ $service->service }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Retornos internos de {{ mb_strtolower($service->service) }}</h2>
                <div class="mp-panel-sub">
                    {{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }} · mais antigos primeiro
                </div>
            </div>
            <span class="mp-pill" wire:loading.delay>Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum retorno para os filtros escolhidos' : 'Sem retornos internos em ' . $service->service . ' no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais retornos.' : 'Quando um retorno interno for aberto para esta atividade, ele aparece aqui.'"
                    icon="ri-arrow-go-back-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 38px">
                        <col style="width: 128px">
                        <col style="width: 112px">
                        <col style="width: 64px">
                        <col style="width: 160px">
                        <col style="width: 190px">
                        <col style="width: 150px">
                        <col style="width: 96px">
                        <col style="width: 104px">
                        <col style="width: 118px">
                        <col style="width: 200px">
                        <col style="width: 100px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" aria-label="Selecionar todas as linhas da página"
                                    @change="$root.querySelectorAll('input[data-ops-row]').forEach(c => { if (c.checked !== $event.target.checked) { c.checked = $event.target.checked; c.dispatchEvent(new Event('change', { bubbles: true })); } }); recount()">
                            </th>
                            @foreach ([['note', 'Nota'], [null, 'Origem'], [null, 'Arquivos'], ['lexp', 'Local'], ['material', 'Material'], ['category', 'Categoria'], ['created_at', 'Enviado'], [null, 'Em atividade'], [null, 'Status'], [null, 'Responsável']] as [$field, $label])
                                <th @class(['is-left' => in_array($label, ['Material', 'Categoria', 'Responsável'])])>
                                    @if ($field)
                                        <button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold" wire:click="sortBy('{{ $field }}')" title="Ordenar por {{ mb_strtolower($label) }}">
                                            {{ $label }}
                                            @if ($sortField === $field)
                                                <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line" aria-hidden="true"></i>
                                            @endif
                                        </button>
                                    @else
                                        {{ $label }}
                                    @endif
                                </th>
                            @endforeach
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="ri-{{ $row['id'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                <td>
                                    <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['id'] }}" wire:model.defer="selected" @change="recount()" aria-label="Selecionar a nota {{ $row['note'] }}">
                                </td>

                                <td>
                                    <x-ops.note :note="$row['note']" kind="ri" tone="blue" hint="Retorno interno (RI)">
                                        @if ($row['express'])
                                            <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" :hint="'Nota expressa: prazo de execução de ' . $row['expressDays'] . ' dias'" />
                                        @endif
                                    </x-ops.note>
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <x-ops.badge :tone="$row['originTone']" :label="$row['origin']" hint="De onde veio o retorno" />
                                        @if ($row['requester'])
                                            <span class="ops-sub text-truncate" title="Pedido por {{ $row['requester'] }}">{{ $row['requester'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    @if ($row['files']->isNotEmpty())
                                        <x-files.select-download-list :files="$row['files']" :tacit-file-ids="$tacitFiles" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="fw-semibold d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span>
                                    <span class="ops-sub text-truncate">{{ $row['rubrica'] ?: '—' }}</span>
                                </td>

                                <td class="is-left">
                                    <span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span>
                                    <span class="ops-sub text-truncate">Grp5 {{ $row['group5'] ?: '—' }}</span>
                                </td>

                                <td class="is-left">
                                    <span class="ops-clickable d-block text-truncate" wire:click.prevent="$emitTo('dispatchs.common.reclaim-info', 'getInfoResponse', '{{ $row['id'] }}')"
                                        title="Ver os detalhes e os comentários deste retorno">{{ $row['category'] ?: '—' }}</span>
                                </td>

                                <td><x-ops.date :date="$row['sentAt']" time hint="Quando o retorno foi enviado" /></td>

                                <td>
                                    <x-ops.badge :tone="$row['staleTone']" :label="$row['activeFor'] ?? '—'"
                                        :hint="$row['staleTone'] === 'red' ? 'Sem andamento há mais de 24 h (vermelho)' : 'Tempo desde o envio do retorno'" />
                                </td>

                                <td><x-ops.badge :tone="$row['statusTone']" :label="$row['status']" :hint="$row['hasActivity'] ? 'Situação da atividade de desenho' : 'Ainda não há atividade atribuída'" /></td>

                                <td class="is-left">
                                    @if ($row['hasActivity'])
                                        <span class="fw-semibold d-block text-truncate" title="{{ $row['responsible'] }}">{{ $row['responsible'] }}</span>
                                        <span class="ops-sub text-truncate">{{ $row['company'] }}</span>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($row['hasActivity'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="$emitTo('dispatchs.users.richange-user', 'goChangeUser', {{ $row['id'] }})" title="Trocar o responsável desta atividade">
                                            <i class="ri-arrow-left-right-line" aria-hidden="true"></i> Trocar
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="$emitTo('dispatchs.users.riatt-user', 'goAttUser', {{ $row['id'] }})" title="Atribuir a um responsável">
                                            <i class="ri-user-add-line" aria-hidden="true"></i> Atribuir
                                        </button>
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
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="massAssign"><i class="ri-user-shared-line" aria-hidden="true"></i> Atribuir selecionadas</button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click.prevent="exportToExcel(true)"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar selecionadas</button>
        </x-ops.selection-bar>
    </div>

    {{-- Modais de atribuição (cada um é um componente com raiz própria) --}}
    @livewire('dispatchs.users.richange-user', key('change-users-intern-return'))
    @livewire('dispatchs.users.riatt-user', ['service' => $service], key('att-users-intern-return'))
    @livewire('dispatchs.common.reclaim-info', key('reclaim-info-intern-return'))
    @livewire('dispatchs.common.return-in-mass', ['service' => $service], key('return-in-mass-table'))
</div>
