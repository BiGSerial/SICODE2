@php
    $classifier     = \App\Services\Closure\ClosureMetaClassifier::class;
    $presenter      = \App\Services\Closure\ClosureDispatchRowPresenter::class;
    $typeTone       = ['P' => 'red', 'M' => 'green', 'F' => 'blue'];
    $selectedCount  = count($selectedOrderIds);
    $loadingTargets = 'search,type,toggleCard,gotoPage,nextPage,previousPage,perPage,clearFilters,removeAnyFilter,applyListFilters,removeListFilter,clearListFilters,applyBulkSearch';
@endphp

{{-- Lista Encerramento do Serviço: todas as Ordens do Encerramento. Ver é livre; assumir depende da auto-atribuição liberada em Gestão do ciclo → Configurações. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero eyebrow="Serviço · Encerramento" title="Lista Encerramento" subtitle="Todas as Ordens do Encerramento, despachadas ou não. Assumir depende de o despacho ter liberado a auto-atribuição.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <x-ops.legend />
            @if ($enabled)
                <x-ops.badge tone="green" icon="ri-user-follow-line" :label="'Auto-atribuição liberada (' . implode('/', $allowedTypes) . ')'" hint="Tipos que o despacho liberou para você assumir" />
            @else
                <x-ops.badge tone="muted" icon="ri-lock-line" label="Auto-atribuição não liberada" hint="O despacho precisa liberar em Gestão do ciclo > Configurações" />
            @endif
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        </div>
    </x-ops.hero>

    <div class="ops-stats">
        <x-ops.stat wire:key="astat-all" wire:click="toggleCard('all')" tone="teal" icon="ri-list-check" label="Total" :value="number_format($counts['total'], 0, ',', '.')" :active="$scope === 'all'" title="Todas as Ordens do recorte atual" />
        <x-ops.stat wire:key="astat-free" wire:click="toggleCard('free')" tone="amber" icon="ri-inbox-line" label="Sem posse" :value="number_format($counts['undispatched'], 0, ',', '.')" :active="$scope === 'free'" title="Ainda não despachadas: ninguém tem a Ordem" />
        <x-ops.stat wire:key="astat-mine" wire:click="toggleCard('mine')" tone="blue" icon="ri-user-line" label="Minhas" :value="$scope === 'mine' ? number_format($orders->total(), 0, ',', '.') : '—'" :active="$scope === 'mine'" title="Atribuídas a você" />
        @if ($activeDeposits->isNotEmpty())
            <x-ops.stat wire:key="astat-deposit" tone="red" icon="ri-lock-line" label="Depósito bloqueado" :value="number_format($counts['blocked'], 0, ',', '.')" disabled title="Ordens no alcance de um depósito bloqueado" />
        @endif
    </div>

    @if ($activeDeposits->isNotEmpty())
        <div class="ops-notice ops-notice--danger" role="status">
            <i class="ri-lock-line" aria-hidden="true"></i>
            <div>
                <strong>{{ $activeDeposits->count() === 1 ? 'Depósito bloqueado' : 'Depósitos bloqueados' }}:</strong>
                @foreach ($activeDeposits as $deposit)
                    <x-ops.badge tone="red" mono :hint="$deposit->deposit_label . ' · desde ' . $deposit->created_at?->format('d/m/Y')">{{ $deposit->deposit_code }}</x-ops.badge>
                @endforeach
                As Ordens afetadas trazem o selo do depósito na coluna Status da atividade.
            </div>
        </div>
    @endif

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Nota ou Ordem" label="Buscar Nota ou Ordem" />
            <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />
            <x-ops.segmented name="closureAllType" label="Tipo de Ordem" wire:model="type" :options="['all' => 'Todos', 'P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura']" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($hasCriteria)
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="array_merge($criteriaChips, $this->listFilterChips())" remove-action="removeAnyFilter" clear-action="clearFilters" />
            </div>
        @endif

        <x-closure.bulk-search-result :report="$this->bulkReport()" />
    </section>

    <section class="mp-panel" aria-label="Ordens do Encerramento">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Ordens</h2>
                <div class="mp-panel-sub">{{ number_format($orders->total(), 0, ',', '.') }} {{ $orders->total() === 1 ? 'Ordem' : 'Ordens' }}. Só é possível marcar as que você pode assumir; nas demais, passe o mouse para ver o motivo.</div>
            </div>
            <div class="ops-panel-actions">
                <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleAssumablePage" @disabled(!$enabled)>Selecionar as que posso assumir</button>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $orders->count()))" :cols="8" />

        @if ($orders->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Nenhuma Ordem no Encerramento no momento'" :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : 'Quando houver Ordens elegíveis, elas aparecem aqui.'" icon="ri-inbox-2-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 38px"><col style="width: 52px"><col style="width: 100px"><col style="width: 108px"><col style="width: 92px"><col style="width: 190px">
                        <col style="width: 120px"><col style="width: 92px"><col style="width: 210px"><col style="width: 150px"><col style="width: 60px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><span class="visually-hidden">Selecionar</span></th>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Ordem</th>
                            <th>Compet.</th>
                            <th class="is-left">Obra / Município</th>
                            <th class="is-right" title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                            <th>Status SAP</th>
                            <th class="is-left">Status da atividade</th>
                            <th class="is-left">Responsável</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                [$can, $why] = $this->assumability($order);
                                $row         = $presenter::present($order, $depositSignals, $centerSignals);
                                $letter      = $classifier::letter($order->meta_type);
                            @endphp
                            <tr wire:key="closure-all-{{ $order->id }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free', 'is-selected' => in_array($order->id, $selectedOrderIds, true)])
                                @if ($row['signal']) data-signal="{{ $row['signal']['key'] }}" title="{{ $row['signal']['label'] }}: {{ $row['signal']['hint'] }}" @endif data-ordem="{{ $order->ordem }}">
                                <td>
                                    @if ($can)
                                        <input type="checkbox" class="form-check-input" @checked(in_array($order->id, $selectedOrderIds, true)) wire:click="toggleOrder({{ $order->id }})" aria-label="Selecionar Ordem {{ $order->ordem }}">
                                    @else
                                        <input type="checkbox" class="form-check-input" disabled title="{{ $why }}" aria-label="Ordem {{ $order->ordem }}: {{ $why }}">
                                    @endif
                                </td>
                                <td>
                                    @if ($letter)
                                        <x-ops.badge :tone="$typeTone[$letter] ?? 'muted'" mono :label="$letter" :hint="$classifier::label($order->meta_type)" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td class="copy-text" data-value="{{ $order->note_number }}">
                                    <div class="ops-cell-stack is-center">
                                        <span class="ops-note">{{ $order->note_number }}</span>
                                        @if ($order->partial_id)<x-ops.badge sm tone="amber" label="Parcial" :hint="'Parcial #' . $order->partial_id" />@endif
                                    </div>
                                </td>
                                <td class="copy-text" data-value="{{ $order->ordem }}"><span class="ops-note">{{ $order->ordem }}</span></td>
                                <td><span class="ops-sub text-body">{{ $classifier::competenceLabel($order->competence) }}</span></td>
                                <td class="is-left">
                                    <div class="text-truncate" title="{{ $order->descricao }}">{{ $order->descricao ?: '—' }}</div>
                                    <span class="ops-sub text-truncate" title="{{ $order->city_name }}">{{ $order->city_name ?: '—' }}</span>
                                </td>
                                <x-closure.order-cost :planned="$order->custPlanejado" :actual="$order->custRealizado" />
                                <td><x-ops.badge tone="muted" mono :label="$order->statusSist ?: '—'" hint="Status do sistema (SAP)" /></td>
                                <td class="is-left">
                                    <div class="ops-cell-stack">
                                        @if ($row['status'])
                                            <x-ops.badge :tone="$row['status']['tone']" :label="$row['status']['label']" :hint="$row['status']['hint']" />
                                            <span class="ops-sub">{{ $row['status']['hint'] }}</span>
                                        @else
                                            <x-ops.badge tone="muted" label="Não despachada" :hint="$presenter::statusHint(null)" />
                                            <span class="ops-sub">{{ $presenter::statusHint(null) }}</span>
                                        @endif
                                        @if ($row['deposit'])
                                            <x-ops.badge sm :tone="$row['deposit']['tone']" :icon="$row['deposit']['locked'] ? 'ri-lock-line' : 'ri-error-warning-line'" :label="$row['deposit']['label']" :hint="$row['deposit']['hint']" />
                                        @endif
                                        @unless ($can)
                                            <span class="ops-sub" title="{{ $why }}">{{ \Illuminate\Support\Str::limit($why, 60) }}</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="is-left"><div class="text-truncate" title="{{ $order->assignee_name }}">{{ $order->assignee_name ?: ($order->holder_company_name ? 'Pilha de ' . $order->holder_company_name : '—') }}</div></td>
                                <td>
                                    <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'service']) }}" class="btn btn-outline-secondary btn-sm ops-action" title="Abrir a obra desta Ordem" aria-label="Abrir obra da Ordem {{ $order->ordem }}"><i class="ri-external-link-line" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$orders" :per-page="$perPage" :sizes="[10, 25, 50, 100]" />
    </section>

    @if ($selectedCount > 0)
        <div class="ops-selection" id="closure-all-bulk">
            <div class="ops-selection-count"><strong>{{ $selectedCount }}</strong> {{ $selectedCount === 1 ? 'selecionada' : 'selecionadas' }}</div>
            <div class="ops-selection-actions">
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="askAssume"><i class="ri-user-follow-line" aria-hidden="true"></i> Assumir selecionadas</button>
            </div>
            <button type="button" class="ops-selection-clear" wire:click="clearSelection" aria-label="Limpar seleção"><i class="ri-close-line"></i></button>
        </div>
    @endif

    @if ($showReceipt && $receipt)
        <x-ops.modal title="Resultado da atribuição" :eyebrow="count($receipt['successes']) . ' assumida(s), ' . count($receipt['refusals']) . ' recusada(s)'" close="closeReceipt" size="modal-lg">
            <div class="row g-3" id="closure-all-receipt">
                <div class="col-md-6">
                    <div class="ops-modal-card">
                        <div class="ops-modal-card-head tone-green"><i class="ri-checkbox-circle-line" aria-hidden="true"></i> Assumidas ({{ count($receipt['successes']) }})</div>
                        <div class="ops-modal-card-body"><ul class="mb-0 ps-3">@forelse ($receipt['successes'] as $o)<li>{{ $o }}</li>@empty<li class="text-muted">Nenhuma.</li>@endforelse</ul></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ops-modal-card">
                        <div class="ops-modal-card-head tone-red"><i class="ri-close-circle-line" aria-hidden="true"></i> Recusadas ({{ count($receipt['refusals']) }})</div>
                        <div class="ops-modal-card-body"><ul class="mb-0 ps-3">@forelse ($receipt['refusals'] as $r)<li><strong>{{ $r['order'] }}</strong>: {{ $r['reason'] }}</li>@empty<li class="text-muted">Nenhuma.</li>@endforelse</ul></div>
                    </div>
                </div>
            </div>
            <x-slot name="footer"><button type="button" class="btn btn-primary" wire:click="closeReceipt">Fechar</button></x-slot>
        </x-ops.modal>
    @endif
</div>
