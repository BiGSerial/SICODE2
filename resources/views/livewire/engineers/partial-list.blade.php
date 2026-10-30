@php
    $money  = fn (?float $v) => $v === null ? '—' : 'R$ ' . number_format($v, 2, ',', '.');
    $days   = fn (?int $d) => $d === null ? '—' : ($d === 0 ? 'hoje' : $d . ' ' . ($d === 1 ? 'dia' : 'dias'));
    $hasFilters = $search || $dt_in || $dt_out;
@endphp

<div class="closure-dash my-panel">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Informes parciais</div>
            <h1 class="mp-hero-title">Aguardando aprovação</h1>
            <p class="mp-hero-sub">Informes parciais enviados pelas empreiteiras que esperam o seu parecer, com o saldo do PEP de cada obra.</p>
        </div>
        <div class="mp-hero-actions">
            <a href="{{ route('engineers.hist.parcial') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-history-line"></i> Histórico</a>
            <a href="{{ route('engineers.pep_alerts') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-wallet-3-line"></i> Informes sem saldo PEP</a>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros da fila">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="pl-search">Buscar</label>
                <input type="text" id="pl-search" class="form-control form-control-sm" placeholder="Nota, OV, ordem ou DR" wire:model.defer="search" wire:keydown.enter="pesquisar">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="pl-month">Mês de envio</label>
                <input type="month" id="pl-month" class="form-control form-control-sm" wire:model="month">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="pl-from">De</label>
                <input type="date" id="pl-from" class="form-control form-control-sm" wire:model.defer="dt_in">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="pl-to">Até</label>
                <input type="date" id="pl-to" class="form-control form-control-sm" wire:model.defer="dt_out">
            </div>
            <div class="col-12 col-xl-3 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-sm btn-dash" wire:click.prevent="pesquisar"><i class="ri-search-line"></i> Pesquisar</button>
                @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partial', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                @livewire('components.filter.remove-all', ['group_filter' => 'partial'], key('removeAll'))
                @if ($hasFilters)
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearLocalFilters"><i class="ri-filter-off-line"></i> Limpar</button>
                @endif
            </div>
        </div>
    </section>

    <div wire:loading.class="is-loading-soft" wire:target="pesquisar,month,clearLocalFilters,gotoPage,nextPage,previousPage">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-file-list-3-line" label="Aguardando aprovação" :value="$summary['waiting']"
                    :note="$summary['companies'] . ' ' . ($summary['companies'] === 1 ? 'empreiteira' : 'empreiteiras') . ' · ' . $summary['today'] . ' enviada(s) hoje'"
                    :values="$summary['sent_daily']" :labels="$summary['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-money-dollar-circle-line" label="Valor em análise" :value="$money($summary['value'])"
                    note="Soma das ADS das parciais da fila"
                    :values="$summary['sent_daily']" :labels="$summary['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-timer-line" label="Mais antiga na fila" :value="$summary['oldest'] === null ? '—' : $days($summary['oldest'])"
                    :note="'Idade média ' . ($summary['average'] !== null ? number_format($summary['average'], 1, ',', '.') . ' dias' : '—')"
                    :values="$summary['age_values']" :labels="$summary['age_labels']" :ticks="4" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-wallet-3-line" label="Com saldo de PEP insuficiente" :value="$shortCount"
                    note="Sem saldo ou com ADS acima do saldo"
                    :values="$summary['sent_daily']" :labels="$summary['labels']" />
            </div>
        </div>

        <h2 class="mp-section-title">Fila de aprovação</h2>

        <section class="mp-panel mb-3" aria-labelledby="pl-queue-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="pl-queue-title" class="mp-panel-title">Parciais da mais antiga para a mais recente</h3>
                    <div class="mp-panel-sub">Clique em Avaliar para ver a obra, as ordens, o saldo do PEP e os anexos antes de decidir.</div>
                </div>
                <span class="mp-pill">{{ $lists->total() }} {{ $lists->total() === 1 ? 'parcial' : 'parciais' }}</span>
            </div>

            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-checkbox-circle-line" title="Nenhum informe parcial aguardando aprovação" text="{{ $hasFilters ? 'Nenhum resultado nos filtros atuais. Limpe os filtros para ver a fila completa.' : 'Tudo o que as empreiteiras enviaram já foi avaliado.' }}" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-9"><col class="col-pct-7"><col class="col-pct-10"><col class="col-pct-9"><col class="col-pct-10">
                            <col class="col-pct-10"><col class="col-pct-12"><col class="col-pct-23"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Nota/OV</th>
                                <th scope="col" class="text-center">Escopo</th>
                                <th scope="col">Ordens</th>
                                <th scope="col">Rubrica</th>
                                <th scope="col" class="text-end">Valor</th>
                                <th scope="col" class="text-center">Saldo do PEP</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col" class="text-center">Posição</th>
                                <th scope="col" class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $age   = (int) $list->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
                                    $state = $pep[$list->id] ?? ['state' => 'none', 'available' => null, 'reason' => null];
                                @endphp
                                <tr wire:key="partial-{{ $list->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $list->Note->note }}</div>
                                        <div class="small text-muted text-truncate">{{ $list->Note->lexp }}</div>
                                    </td>
                                    <td class="text-center"><span class="badge text-bg-secondary">{{ $list->scopeLabel() }}</span></td>
                                    <td>
                                        @foreach ($list->Orders->take(3) as $order)
                                            <div class="small">{{ $order->ordem }}</div>
                                        @endforeach
                                        @if ($list->Orders->count() > 3)
                                            <span class="small text-muted">+{{ $list->Orders->count() - 3 }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $list->Note->rubrica ?: '—' }}</td>
                                    <td class="text-end fw-bold">{{ $money((float) $list->value) }}</td>
                                    <td class="text-center">
                                        @if ($state['state'] === 'short')
                                            <span class="badge text-bg-danger"><i class="ri-alert-line"></i> {{ $state['reason'] === 'ads_exceeds' ? 'ADS acima' : 'Sem saldo' }}</span>
                                            <div class="small text-danger">{{ $money($state['available']) }}</div>
                                        @elseif ($state['state'] === 'ok')
                                            <span class="badge text-bg-success"><i class="ri-check-line"></i> Cobre</span>
                                            <div class="small text-muted">{{ $money($state['available']) }}</div>
                                        @else
                                            <span class="text-muted small" title="Sem PEP -2 sincronizado nas ordens abertas">—</span>
                                        @endif
                                    </td>
                                    <td class="text-truncate" title="{{ $list->Company?->name }}">{{ $list->Company?->name ?? 'Desconhecida' }}</td>
                                    <td class="text-center">
                                        <x-engineers.partial-steps :partial="$list" />
                                        <div class="small text-muted mt-1 text-center">
                                            <strong class="text-body">{{ $days($age) }}</strong> aguardando · enviada {{ $list->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="row-action border-0" wire:click.prevent="$emitTo('engineers.actions.check-partial', 'show_form', {{ $list->id }})">
                                            <i class="ri-eye-line"></i> Avaliar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mp-panel-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>{{ $lists->links() }}</div>
                    <small class="text-muted">Mostrando {{ $lists->firstItem() ?? 0 }} a {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}</small>
                </div>
            @endif
        </section>
    </div>

    @livewire('engineers.actions.check-partial', key('check_partial'))
</div>
