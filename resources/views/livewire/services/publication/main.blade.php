@php
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || $not_assigned;
@endphp

{{-- Lista operacional de Serviços (auto-atribuição). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs com informe liberado para publicação. Assuma a que for sua e acompanhe os prazos pela cor."
        :updated-at="$update ? $last_update : null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, material ou pedido" label="Buscar nota, material ou pedido" />

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
                        @foreach ([150, 64, 140, 170, 120, 96, 112, 84, 104, 112] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Inf. digitação</th>
                            <th>Escopo</th>
                            <th class="is-left">Empresa</th>
                            <th>Município</th>
                            <th>Execução</th>
                            <th>Informe</th>
                            <th>Dias na pilha</th>
                            <th>Vencimento</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['key'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                <td>
                                    @if ($row['noteVisible'])
                                        <x-ops.note :note="$row['note']">
                                            <x-work-report.canceled-badge :report="$row['productionId'] ? ($canceledByProduction[$row['productionId']] ?? null) : null" />
                                        </x-ops.note>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($row['ramalOnly'])
                                        <x-ops.badge sm tone="amber" icon="ri-alert-line" label="Só SMC" hint="Há informe de digitação (SMC), mas ainda não há informe final" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        @foreach ($row['scopes'] as $scope)
                                            <x-ops.badge :tone="$scope['tone']" :label="$scope['label'] . ($scope['publishable'] ? ' · publicável' : '')" hint="Escopo do informe final" />
                                        @endforeach
                                    </div>
                                </td>

                                <td class="is-left"><span class="fw-semibold d-block text-truncate" title="{{ $row['company'] }}">{{ $row['company'] ?: '—' }}</span></td>

                                <td>{{ $row['city'] ?: '—' }}</td>

                                <td><x-ops.date :date="$row['executedAt']" hint="Data de execução da obra" /></td>
                                <td><x-ops.date :date="$row['informedAt']" time hint="Data e hora do informe" /></td>

                                <td>{{ $row['stackDays'] ?? '—' }}</td>

                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :hint="\App\Support\Ops\Deadline::remainingText($row['dueDays']) . ' (verde acima de 15 dias, âmbar até 15, vermelho vencido)'" /></td>

                                <td>
                                    @if (!$row['block'])
                                        <button type="button" class="btn btn-primary btn-sm ops-action"
                                            wire:click.prevent="to_accompany({{ $row['noteId'] }})"
                                            title="Assumir esta Nota/OV para você e enviar para Acompanhamento">
                                            <i class="ri-hand-coin-line" aria-hidden="true"></i> Assumir
                                        </button>
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

    @livewire('components.workform.canceled-info', key('WorkCanceledInfo'))
</div>
