@php
    $accompanyingTab = $tab === 'accompanying';
    $statusFilter    = $this->filterValues('status');
@endphp

{{-- CAIXA DO ENCERRAMENTO. Lista de controle (só visão) + despachos para retorno (exige resposta). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero
        class="ops-hero--control"
        :eyebrow="'Encerramento · ' . $areaLabel"
        :title="'Caixa do Encerramento — ' . $areaLabel"
        :subtitle="'O que o Encerramento está acompanhando e o que enviou para ' . $areaLabel . '.'">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            @if ($accompanyingTab)
                <span class="ops-mode-pill" title="Nesta aba não há nada a responder: a Ordem sai da caixa sozinha quando o status muda."><i class="ri-eye-line" aria-hidden="true"></i> Só visão</span>
            @else
                <span class="ops-mode-pill" title="Nesta aba estão as pendências que o Encerramento enviou e que esperam resposta da área."><i class="ri-send-plane-line" aria-hidden="true"></i> Exige resposta</span>
            @endif
            @if ($canConfigure)
                <a href="{{ route('closure.status_map') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-settings-3-line" aria-hidden="true"></i> Configurar status desta caixa</a>
            @endif
        </div>
    </x-ops.hero>

    <x-closure.inbox-tabs class="mb-2" :active="$tab" :tabs="[
        ['key' => 'accompanying', 'label' => 'Em acompanhamento', 'count' => $totalWatched, 'tone' => 'blue', 'icon' => 'ri-eye-line'],
        ['key' => 'dispatched', 'label' => 'Despachos para retorno', 'count' => $dispatched, 'tone' => 'red', 'icon' => 'ri-send-plane-line'],
    ]" />

    {{-- Cada aba tem a própria raiz com chave: sem isso o diff do Livewire reaproveita o painel de filtros (wire:ignore) da outra aba. --}}
    @if ($accompanyingTab)
    <div wire:key="inbox-tab-accompanying">
        <div class="inbox-note my-2">
            <i class="ri-eye-line" aria-hidden="true"></i>
            <span><strong>Em acompanhamento: só visão.</strong> São as Ordens do Encerramento que estão em status de {{ $areaLabel }}, despachadas ou não. Nada a responder aqui: a Ordem sai da caixa sozinha quando o status muda.</span>
        </div>

        @if (count($mapped) === 0)
            <section class="mp-panel">
                <div class="mp-panel-body">
                    <x-closure.empty-state icon="ri-route-line" title="Esta caixa ainda não tem status configurados" :text="'Escolha quais status do usuário pertencem a ' . $areaLabel . ' para as Ordens aparecerem aqui.'" />
                    <div class="text-center mt-2">
                        @if ($canConfigure)
                            <a href="{{ route('closure.status_map') }}" class="btn btn-primary btn-sm btn-dash"><i class="ri-settings-3-line" aria-hidden="true"></i> Configurar agora</a>
                        @else
                            <span class="small text-muted">Peça à gestão do Encerramento para configurar o mapa de status.</span>
                        @endif
                    </div>
                </div>
            </section>
        @else
            <section class="ops-stats" aria-label="Ordens por status usuário">
                <x-ops.stat tone="teal" label="Em acompanhamento" :value="number_format($totalWatched, 0, ',', '.')" icon="ri-stack-line" :active="false" disabled />
                @foreach ($counts as $prefix => $count)
                    <x-ops.stat tone="blue" :label="$prefix" :value="number_format($count, 0, ',', '.')" icon="ri-flag-line" :active="in_array((string) $prefix, $statusFilter, true)"
                        wire:click.prevent="togglePrefix('{{ $prefix }}')" wire:key="inbox-kpi-{{ $prefix }}" :title="'Filtrar pelo status ' . $prefix" />
                @endforeach
            </section>

            <section class="ops-toolbar" aria-label="Filtros da caixa">
                <div class="ops-toolbar-row">
                    <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar ordem ou nota" label="Buscar ordem ou nota" />

                    <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />

                    <x-ops.segmented name="inboxOrderType" label="Tipo de ordem" wire:model="type" :options="['all' => 'Todos', 'P' => 'P · Passivo', 'M' => 'M · Meta atual', 'F' => 'F · Meta futura']" />

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

                <x-closure.bulk-search-result :report="$this->bulkReport()" />
            </section>

            <section class="mp-panel" aria-label="Ordens em acompanhamento">
                <div class="mp-panel-head">
                    <div>
                        <h2 class="mp-panel-title">Ordens em acompanhamento</h2>
                        <div class="mp-panel-sub">{{ number_format($accompanying->total(), 0, ',', '.') }} {{ $accompanying->total() === 1 ? 'Ordem' : 'Ordens' }}@if ($hasCriteria) com os filtros atuais @endif · o tempo no status só existe para as já despachadas</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="mp-pill" wire:loading.delay>Atualizando…</span>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click.prevent="exportList" wire:loading.attr="disabled" wire:target="exportList">
                            <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                        </button>
                    </div>
                </div>

                <div class="mp-panel-body pb-0"><x-closure.signal-legend /></div>

                <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

                @if ($rows->isEmpty())
                    <div class="mp-panel-body">
                        <x-closure.empty-state
                            icon="ri-radar-line"
                            :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Nenhuma Ordem neste momento'"
                            :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : 'Quando uma Ordem do Encerramento entrar em um status de ' . $areaLabel . ', ela aparece aqui.'" />
                        @if ($hasCriteria)
                            <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                        @endif
                    </div>
                @else
                    <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                        <table class="table ops-table">
                            <colgroup>
                                @foreach ([60, 130, 130, 90, 210, 180, 150, 120, 130, 170, 110] as $w)
                                    <col style="width: {{ $w }}px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nota</th>
                                    <th>Ordem</th>
                                    <th>Compet.</th>
                                    <th class="is-left">Obra / Município</th>
                                    <th title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                                    <th class="is-left">Status SAP</th>
                                    <th>Status usuário</th>
                                    <th>No status desde</th>
                                    <th>Situação no Encerramento</th>
                                    <th><span class="visually-hidden">Ação</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="inbox-order-{{ $row['id'] }}" @class(['signal-row', 'signal-' . $row['signalKey'] => $row['signalKey']]) @if ($row['signalKey']) data-signal="{{ $row['signalKey'] }}" title="{{ $row['signalHint'] }}" @endif data-ordem="{{ $row['ordem'] }}">
                                        <td><x-ops.badge :tone="$row['typeTone']" :label="$row['typeLetter'] ?? '—'" :hint="$row['typeLabel']" sm mono /></td>
                                        <td><x-ops.note :note="$row['note']" :sub="$row['partialId'] ? 'Parcial #' . $row['partialId'] : null" /></td>
                                        <td><span class="ops-note">{{ $row['ordem'] }}</span></td>
                                        <td>{{ $row['competence'] }}</td>
                                        <td class="is-left">
                                            <div class="ops-cell-stack">
                                                <span class="d-block text-truncate" title="{{ $row['work'] }}">{{ $row['work'] ?? '—' }}</span>
                                                <span class="ops-sub">{{ $row['city'] ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ops-cell-stack align-items-end">
                                                <span class="text-nowrap"><span class="text-muted">Plan.</span> <x-ops.money :value="$row['planned']" /></span>
                                                <span class="text-nowrap"><span class="text-muted">Real.</span> <x-ops.money :value="$row['actual']" /></span>
                                            </div>
                                        </td>
                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['statusSap'] }}">{{ $row['statusSap'] ?? '—' }}</span></td>
                                        <td>
                                            @if ($row['statusUser'])
                                                <x-ops.badge tone="blue" :label="$row['statusUser']" hint="Status usuário atual da Ordem" />
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row['since'])
                                                @php $days = (int) $row['since']->copy()->startOfDay()->diffInDays(\Carbon\Carbon::today(), true); @endphp
                                                <x-ops.date :date="$row['since']" :sub="$days === 0 ? 'hoje' : 'há ' . $days . ($days === 1 ? ' dia' : ' dias')"
                                                    :hint="$row['estimated'] ? 'Primeira vez que o sistema viu a Ordem neste status; o início real pode ser anterior.' : 'Data em que a Ordem entrou neste status usuário'" />
                                            @else
                                                <span class="ops-sub" title="Sem data registrada: a Ordem ainda não foi despachada ao Encerramento.">sem registro</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row['situation'])
                                                <div class="ops-cell-stack">
                                                    <x-ops.badge :tone="$row['situation']['tone']" :label="$row['situation']['label']" hint="Situação da Ordem no Encerramento" />
                                                    @if ($row['holder'])
                                                        <span class="ops-sub">{{ $row['holder'] }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <x-ops.badge tone="muted" label="Não despachada" hint="Ainda não foi despachada a ninguém no Encerramento" />
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('closure.work.show', ['note' => $row['noteId'], 'ctx' => 'service']) }}" class="btn btn-outline-primary btn-sm ops-action" title="Abrir a obra da Ordem {{ $row['ordem'] }}"><i class="ri-external-link-line" aria-hidden="true"></i> Abrir obra</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <x-ops.pager :paginator="$accompanying" :per-page="$perPage" />
                @endif
            </section>
        @endif
    </div>
    @else
    <div wire:key="inbox-tab-dispatched">
        <div class="inbox-note my-2">
            <i class="ri-send-plane-line" aria-hidden="true"></i>
            <span><strong>Despachos para retorno: exige resposta.</strong> São as pendências que o Encerramento enviou para {{ $areaLabel }} e que esperam o seu retorno. Diferente do acompanhamento, aqui há o que responder.</span>
        </div>

        <section class="ops-toolbar" aria-label="Filtros dos despachos">
            <div class="ops-toolbar-row">
                <span class="ops-toolbar-label">Situação</span>
                <x-ops.segmented name="inboxIssueStatus" label="Situação do despacho" wire:model="issueStatus" :options="['open' => 'Em aberto', 'done' => 'Concluídos', 'all' => 'Todos']" />
            </div>
        </section>

        <section class="mp-panel" aria-label="Despachos para retorno">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Despachos para retorno</h2>
                    <div class="mp-panel-sub">{{ number_format($issues->total(), 0, ',', '.') }} {{ $issues->total() === 1 ? 'despacho' : 'despachos' }}</div>
                </div>
                <span class="mp-pill" wire:loading.delay>Atualizando…</span>
            </div>

            @if ($issueRows->isEmpty())
                <div class="mp-panel-body">
                    <x-closure.empty-state icon="ri-send-plane-line" title="Nenhum despacho para retorno" :text="'Quando o Encerramento enviar uma pendência para ' . $areaLabel . ', ela aparece aqui.'" />
                </div>
            @else
                <div class="ops-table-wrap">
                    <table class="table ops-table">
                        <colgroup>
                            @foreach ([140, 150, 360, 190, 140] as $w)
                                <col style="width: {{ $w }}px">
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota</th>
                                <th>Ordem</th>
                                <th class="is-left">Assunto</th>
                                <th>Situação</th>
                                <th>Enviado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($issueRows as $row)
                                <tr wire:key="inbox-issue-{{ $row['id'] }}">
                                    <td>@if ($row['note']) <x-ops.note :note="$row['note']" /> @else <span class="ops-empty">—</span> @endif</td>
                                    <td><span class="ops-note">{{ $row['ordem'] ?? '—' }}</span></td>
                                    <td class="is-left">
                                        <div class="ops-cell-stack">
                                            <span>{{ $row['subject'] ?? '—' }}</span>
                                            @if ($row['detail'])
                                                <span class="ops-sub">{{ $row['detail'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td><x-ops.badge :tone="$row['statusTone']" :label="$row['statusText']" :hint="$row['statusHint']" /></td>
                                    <td><x-ops.date :date="$row['sentAt']" :sub="$row['sentDays'] === null ? null : 'há ' . $row['sentDays'] . ($row['sentDays'] === 1 ? ' dia' : ' dias')" hint="Data em que o Encerramento enviou o despacho" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-ops.pager :paginator="$issues" :per-page="$perPage" />
            @endif
        </section>
    </div>
    @endif
</div>
