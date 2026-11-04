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
                    <div class="text-center mt-2"><span class="small text-muted">Peça à gestão do Encerramento para definir os status desta caixa.</span></div>
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
                        <div class="mp-panel-sub">{{ number_format($accompanying->total(), 0, ',', '.') }} {{ $accompanying->total() === 1 ? 'Ordem' : 'Ordens' }}@if ($hasCriteria) com os filtros atuais @endif @if (!$paymentView) · o tempo no status só existe para as já despachadas @endif</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="mp-pill" wire:loading.delay>Atualizando…</span>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-dash" wire:click.prevent="exportList" wire:loading.attr="disabled" wire:target="exportList">
                            <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                        </button>
                    </div>
                </div>

                <div class="mp-panel-body pb-0"><x-closure.signal-legend /></div>

                <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="$paymentView ? 10 : 9" />

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
                                @foreach ($paymentView ? [56, 120, 130, 230, 190, 120, 130, 170, 150, 170, 120] : [56, 120, 130, 90, 230, 130, 170, 130, 130, 170, 120] as $w)
                                    <col style="width: {{ $w }}px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nota</th>
                                    <th>Ordem</th>
                                    @unless ($paymentView)
                                        <th>Compet.</th>
                                    @endunless
                                    <th class="is-left">Obra / Município</th>
                                    @if ($paymentView)
                                        <th class="is-left" title="Empresa e tipo do informe da obra">Informe</th>
                                        <th class="is-right" title="Valor da ADS do informe">Valor ADS</th>
                                        <th title="Prazo de medição: dias úteis após a entrega da ADS (Final) ou a aprovação do engenheiro (Parcial)">Prazo de medição</th>
                                        <th class="is-left" title="Situação da Nota na fila de Pagamento e quem está com ela">Fila de Pagamento</th>
                                        <th title="Status usuário atual e há quanto tempo a Ordem está nele">Status usuário</th>
                                    @else
                                        <th class="is-right" title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                                        <th class="is-left">Status SAP</th>
                                        <th>Status usuário</th>
                                        <th>No status desde</th>
                                    @endif
                                    <th class="is-left">Situação no Encerramento</th>
                                    <th><span class="visually-hidden">Ação</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php
                                        $pay   = $row['payment'];
                                        $days  = $row['since'] ? (int) $row['since']->copy()->startOfDay()->diffInDays(\Carbon\Carbon::today(), true) : null;
                                        $daysT = $days === null ? null : ($days === 0 ? 'hoje' : 'há ' . $days . ($days === 1 ? ' dia' : ' dias'));
                                    @endphp
                                    <tr wire:key="inbox-order-{{ $row['id'] }}" @class(['signal-row', 'signal-' . $row['signalKey'] => $row['signalKey']]) @if ($row['signalKey']) data-signal="{{ $row['signalKey'] }}" title="{{ $row['signalHint'] }}" @endif data-ordem="{{ $row['ordem'] }}">
                                        <td><x-ops.badge :tone="$row['typeTone']" :label="$row['typeLetter'] ?? '—'" :hint="$row['typeLabel']" sm mono /></td>
                                        <td><x-ops.note :note="$row['note']" :sub="$row['partialId'] ? 'Parcial #' . $row['partialId'] : null" /></td>
                                        <td>
                                            <div class="ops-cell-stack is-center">
                                                <span class="ops-note">{{ $row['ordem'] }}</span>
                                                @if ($paymentView)
                                                    <span class="ops-sub" title="Competência da Ordem">{{ $row['competence'] ?: '—' }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        @unless ($paymentView)
                                            <td>{{ $row['competence'] }}</td>
                                        @endunless
                                        <td class="is-left">
                                            <div class="ops-cell-stack">
                                                <span class="d-block text-truncate w-100" title="{{ $row['work'] }}">{{ $row['work'] ?? '—' }}</span>
                                                <span class="ops-sub">{{ $row['city'] ?? '—' }}</span>
                                            </div>
                                        </td>

                                        @if ($paymentView)
                                            <td class="is-left">
                                                @if ($pay)
                                                    <div class="ops-cell-stack">
                                                        <span class="fw-semibold d-block text-truncate w-100" title="{{ $pay['company'] }}">{{ $pay['company'] ?: '—' }}</span>
                                                        <x-ops.badge :tone="$pay['scopeTone']" :label="$pay['scopeText']" :hint="$pay['scopeHint']" sm />
                                                    </div>
                                                @else
                                                    <span class="ops-empty">—</span>
                                                @endif
                                            </td>
                                            <td class="is-right"><x-ops.money :value="$pay['adsAmount'] ?? null" /></td>
                                            <td>
                                                @if ($pay && $pay['dueAt'])
                                                    <x-ops.date :date="$pay['dueAt']" :tone="$pay['dueTone']" :sub="$pay['dueLabel']" :hint="$pay['dueHint']" />
                                                @elseif ($pay)
                                                    <span class="ops-sub" title="{{ $pay['dueHint'] }}">não iniciado</span>
                                                @else
                                                    <span class="ops-empty">—</span>
                                                @endif
                                            </td>
                                            <td class="is-left">
                                                @if ($pay)
                                                    <div class="ops-cell-stack">
                                                        <x-ops.badge :tone="$pay['done'] ? 'green' : $pay['statusTone']" :label="$pay['done'] ? 'Concluída' : $pay['statusLabel']" hint="Situação da Nota na fila de Pagamento" />
                                                        <span class="ops-sub text-truncate w-100" title="Responsável pela Nota em Pagamento">{{ $pay['owner'] ?: 'Sem responsável' }}</span>
                                                    </div>
                                                @else
                                                    <x-ops.badge tone="muted" label="Fora da fila" hint="Esta Nota não está na fila de Pagamento" />
                                                @endif
                                            </td>
                                            <td>
                                                <div class="ops-cell-stack is-center">
                                                    @if ($row['statusUser'])
                                                        <x-ops.badge tone="blue" :label="$row['statusUser']" :hint="'Status usuário atual da Ordem' . ($row['statusSap'] ? ' · Status SAP: ' . $row['statusSap'] : '')" />
                                                    @else
                                                        <span class="ops-empty">—</span>
                                                    @endif
                                                    <span class="ops-sub" title="{{ $row['since'] ? ($row['estimated'] ? 'Primeira vez que o sistema viu a Ordem neste status; o início real pode ser anterior.' : 'Data em que a Ordem entrou neste status usuário') : 'Sem data registrada: a Ordem ainda não foi despachada ao Encerramento.' }}">{{ $daysT ?? 'sem registro' }}</span>
                                                </div>
                                            </td>
                                        @else
                                            <td class="is-right">
                                                <div class="ops-cell-stack is-end">
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
                                                    <x-ops.date :date="$row['since']" :sub="$daysT"
                                                        :hint="$row['estimated'] ? 'Primeira vez que o sistema viu a Ordem neste status; o início real pode ser anterior.' : 'Data em que a Ordem entrou neste status usuário'" />
                                                @else
                                                    <span class="ops-sub" title="Sem data registrada: a Ordem ainda não foi despachada ao Encerramento.">sem registro</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td class="is-left">
                                            <div class="ops-cell-stack">
                                                @if ($row['situation'])
                                                    <x-ops.badge :tone="$row['situation']['tone']" :label="$row['situation']['label']" hint="Situação da Ordem no Encerramento" />
                                                    @if ($row['holder'])
                                                        <span class="ops-sub text-truncate w-100" title="{{ $row['holder'] }}">{{ $row['holder'] }}</span>
                                                    @endif
                                                @else
                                                    <x-ops.badge tone="muted" label="Não despachada" hint="Ainda não foi despachada a ninguém no Encerramento" />
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click="openDetail({{ $row['id'] }})" title="Ver os dados da Ordem {{ $row['ordem'] }} (só leitura)"><i class="ri-eye-line" aria-hidden="true"></i> Ver detalhes</button>
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
                            @foreach ([140, 150, 330, 190, 140, 130] as $w)
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
                                <th><span class="visually-hidden">Ação</span></th>
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
                                    <td>
                                        @if ($canRespond && in_array($row['status'], ['REQUESTED', 'RETURNED'], true))
                                            <button type="button" class="btn btn-primary btn-sm ops-action" wire:click="openIssue({{ $row['id'] }})" title="Ler a solicitação e finalizar com o seu retorno"><i class="ri-check-double-line" aria-hidden="true"></i> Finalizar</button>
                                        @else
                                            <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click="openIssue({{ $row['id'] }})" title="Ver a solicitação e o andamento (só leitura)"><i class="ri-eye-line" aria-hidden="true"></i> Ver</button>
                                        @endif
                                    </td>
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

    {{-- Detalhe da Ordem: só leitura, dentro da atividade (não leva ao Encerramento). --}}
    @if ($detail)
        @php $pay = $detail['payment']; @endphp
        <x-ops.modal :title="'Ordem ' . $detail['ordem']" :eyebrow="'Nota ' . ($detail['note'] ?? '—') . ' · só leitura'" close="closeDetail" size="modal-lg">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="ops-modal-card">
                        <div class="ops-modal-card-head tone-blue"><i class="ri-building-line" aria-hidden="true"></i> Obra</div>
                        <div class="ops-modal-card-body">
                            <div class="fw-semibold">{{ $detail['work'] ?? '—' }}</div>
                            <div class="ops-sub">{{ $detail['city'] ?? '—' }} · Competência {{ $detail['competence'] ?: '—' }}</div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <x-ops.badge :tone="$detail['typeTone']" :label="$detail['typeLabel'] ?? '—'" sm />
                                @if ($detail['statusUser'])<x-ops.badge tone="blue" :label="$detail['statusUser']" hint="Status usuário atual" sm />@endif
                            </div>
                            <div class="mt-2 small"><span class="text-muted">Plan.</span> <x-ops.money :value="$detail['planned']" /> · <span class="text-muted">Real.</span> <x-ops.money :value="$detail['actual']" /></div>
                            @if ($detail['statusSap'])<div class="ops-sub mt-1">Status SAP: {{ $detail['statusSap'] }}</div>@endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ops-modal-card">
                        <div class="ops-modal-card-head tone-teal"><i class="ri-stack-line" aria-hidden="true"></i> Situação no Encerramento</div>
                        <div class="ops-modal-card-body">
                            @if ($detail['situation'])
                                <x-ops.badge :tone="$detail['situation']['tone']" :label="$detail['situation']['label']" />
                                @if ($detail['holder'])<div class="ops-sub mt-1">{{ $detail['holder'] }}</div>@endif
                            @else
                                <x-ops.badge tone="muted" label="Não despachada" />
                            @endif
                            <div class="ops-sub mt-2">No status {{ $detail['statusUser'] ?? '—' }}: {{ $detail['since'] ? $detail['since']->format('d/m/Y') : 'sem registro' }}</div>
                        </div>
                    </div>
                </div>
                @if ($paymentView)
                    <div class="col-12">
                        <div class="ops-modal-card">
                            <div class="ops-modal-card-head tone-amber"><i class="ri-bank-card-line" aria-hidden="true"></i> Pagamento</div>
                            <div class="ops-modal-card-body">
                                @if ($pay)
                                    <div class="row g-2">
                                        <div class="col-md-3"><div class="ops-sub">Informe</div><div class="fw-semibold">{{ $pay['company'] ?: '—' }}</div><x-ops.badge :tone="$pay['scopeTone']" :label="$pay['scopeText']" sm /></div>
                                        <div class="col-md-3"><div class="ops-sub">Valor ADS</div><x-ops.money :value="$pay['adsAmount'] ?? null" /></div>
                                        <div class="col-md-3"><div class="ops-sub">Prazo de medição</div>@if ($pay['dueAt'])<x-ops.date :date="$pay['dueAt']" :tone="$pay['dueTone']" :sub="$pay['dueLabel']" />@else<span class="ops-sub">não iniciado</span>@endif</div>
                                        <div class="col-md-3"><div class="ops-sub">Fila de Pagamento</div><x-ops.badge :tone="$pay['done'] ? 'green' : $pay['statusTone']" :label="$pay['done'] ? 'Concluída' : $pay['statusLabel']" /><div class="ops-sub">{{ $pay['owner'] ?: 'Sem responsável' }}</div></div>
                                    </div>
                                @else
                                    <span class="text-muted">Esta Nota não está na fila de Pagamento.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <x-slot name="footer"><button type="button" class="btn btn-primary" wire:click="closeDetail">Fechar</button></x-slot>
        </x-ops.modal>
    @endif

    {{-- Resolução do despacho para retorno: a área lê a solicitação e a finaliza com o próprio retorno (o Encerramento valida depois). --}}
    @if ($issueDetail)
        <x-ops.modal :title="$issueDetail['subject'] ?? 'Solicitação'" :eyebrow="'Nota ' . ($issueDetail['note'] ?? '—') . ' · Ordem ' . ($issueDetail['ordem'] ?? '—')" close="closeIssue" size="modal-lg">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <x-ops.badge :tone="$issueDetail['statusTone']" :label="$issueDetail['statusText']" :hint="$issueDetail['statusHint']" />
                @if ($issueDetail['detail'])<span class="ops-sub">{{ $issueDetail['detail'] }}</span>@endif
                @if ($issueDetail['openedBy'])<span class="ops-sub ms-auto">Enviado por {{ $issueDetail['openedBy'] }}</span>@endif
            </div>
            @if ($issueDetail['description'])
                <div class="ops-modal-card mb-3"><div class="ops-modal-card-head tone-blue"><i class="ri-message-3-line" aria-hidden="true"></i> O que foi pedido</div><div class="ops-modal-card-body">{!! nl2br(e($issueDetail['description'])) !!}</div></div>
            @endif
            @if ($issueDetail['requirements'])
                <div class="ops-modal-card mb-3"><div class="ops-modal-card-head tone-amber"><i class="ri-list-check-2" aria-hidden="true"></i> Exigências</div><div class="ops-modal-card-body"><ul class="mb-0 ps-3">@foreach ($issueDetail['requirements'] as $requirement)<li>{{ is_array($requirement) ? ($requirement['text'] ?? '') : $requirement }}</li>@endforeach</ul></div></div>
            @endif
            @if ($issueDetail['timeline'])
                <x-closure.timeline :items="$issueDetail['timeline']" />
            @endif
            @if ($issueDetail['canFinalize'] && $canRespond)
                <label class="form-label fw-semibold mt-3" for="inbox-issue-reply">Seu retorno <span class="text-danger">*</span></label>
                <textarea id="inbox-issue-reply" class="form-control @error('issueReply') is-invalid @enderror" rows="4" wire:model.defer="issueReply" placeholder="Conte o que foi feito para atender a solicitação"></textarea>
                @error('issueReply')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @endif
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="closeIssue">Fechar</button>
                @if ($issueDetail['canFinalize'] && $canRespond)
                    <button type="button" class="btn btn-primary" wire:click="finalizeIssue" wire:loading.attr="disabled" wire:target="finalizeIssue"><i class="ri-check-double-line" aria-hidden="true"></i> Finalizar solicitação</button>
                @endif
            </x-slot>
        </x-ops.modal>
    @endif
</div>
