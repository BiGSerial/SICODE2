@php
    $money = fn (?float $v) => $v === null ? '—' : 'R$ ' . number_format($v, 2, ',', '.');
    $dec   = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
    $hasFilters = $search || $bulk_search || $company_id || $status || $dt_in || $dt_out;

    $stageMax  = max(1, $summary['supervision'], $summary['payment'], $summary['paid'], $summary['rejected']);
    $stageRows = [
        ['label' => 'Em fiscalização', 'text' => (string) $summary['supervision'], 'pct' => $summary['supervision'] / $stageMax * 100, 'tone' => 'blue', 'sub' => 'Aprovadas, aguardando a Fiscalização'],
        ['label' => 'Em medição', 'text' => (string) $summary['payment'], 'pct' => $summary['payment'] / $stageMax * 100, 'tone' => 'teal', 'sub' => 'Fiscalizadas, aguardando a Medição'],
        ['label' => 'Pagas', 'text' => (string) $summary['paid'], 'pct' => $summary['paid'] / $stageMax * 100, 'tone' => 'green', 'sub' => 'Medição concluída'],
        ['label' => 'Rejeitadas', 'text' => $summary['rejected'] . ' · ' . $dec($summary['rejected_rate']) . '%', 'pct' => $summary['rejected'] / $stageMax * 100, 'tone' => 'red', 'sub' => 'Em qualquer etapa'],
    ];

    $companyMax  = max(1, collect($summary['by_company'])->max('total') ?? 1);
    $companyRows = collect($summary['by_company'])->map(fn ($c) => [
        'label' => $c['label'],
        'text'  => (string) $c['total'],
        'pct'   => $c['total'] / $companyMax * 100,
        'tone'  => $c['total'] > 0 && $c['rejected'] / $c['total'] >= .3 ? 'amber' : 'blue',
        'sub'   => $money($c['value']) . ' em ADS' . ($c['rejected'] ? ' · ' . $c['rejected'] . ' rejeitada(s)' : ''),
    ])->all();
@endphp

<div class="closure-dash my-panel">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Informes parciais</div>
            <h1 class="mp-hero-title">Histórico de informes parciais</h1>
            <p class="mp-hero-sub">Tudo o que já passou pela Engenharia: em que etapa cada parcial está, quanto foi pedido e como as empreiteiras se saíram.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> <span wire:loading.remove wire:target="exportToExcel">Exportar Excel</span><span wire:loading wire:target="exportToExcel">Enviando…</span>
            </button>
            <a href="{{ route('engineers.info.parcial') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-timer-flash-line"></i> Aguardando aprovação</a>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do histórico">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="ph-search">Buscar</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="ph-search" class="form-control" placeholder="Nota, OV, ordem ou DR" wire:model.defer="search" wire:keydown.enter="pesquisar">
                    <button type="button" class="btn btn-primary" wire:click.prevent="pesquisar" aria-label="Pesquisar"><i class="ri-search-line"></i></button>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="ph-company">Empreiteira</label>
                <select id="ph-company" class="form-select form-select-sm" wire:model="company_id">
                    <option value="">Todas</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small mb-1" for="ph-status">Etapa</label>
                <select id="ph-status" class="form-select form-select-sm" wire:model="status">
                    <option value="">Todas</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small mb-1" for="ph-month">Mês de envio</label>
                <input type="month" id="ph-month" class="form-control form-control-sm" wire:model="month">
            </div>
            <div class="col-6 col-md-2 col-xl-1">
                <label class="form-label small mb-1" for="ph-from">De</label>
                <input type="date" id="ph-from" class="form-control form-control-sm" wire:model.defer="dt_in">
            </div>
            <div class="col-6 col-md-2 col-xl-1">
                <label class="form-label small mb-1" for="ph-to">Até</label>
                <input type="date" id="ph-to" class="form-control form-control-sm" wire:model.defer="dt_out">
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap align-items-center">
                <button type="button" class="btn btn-outline-primary btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#partialBulkSearchModal">
                    <i class="ri-list-check-2"></i> Busca em massa @if ($bulkSearchCount) <span class="badge text-bg-primary">{{ $bulkSearchCount }}</span> @endif
                </button>
                @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partial', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                @livewire('components.filter.remove-all', ['group_filter' => 'partial'], key('removeAll'))
                @if ($hasFilters)
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearLocalFilters"><i class="ri-filter-off-line"></i> Limpar filtros</button>
                @endif
            </div>
        </div>
    </section>

    <div wire:loading.class="is-loading-soft" wire:target="pesquisar,company_id,status,month,clearLocalFilters,applyBulkSearch,clearBulkSearch,perPage,gotoPage,nextPage,previousPage">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-file-list-3-line" label="Informes parciais" :value="$summary['total']"
                    :note="$summary['companies'] . ' ' . ($summary['companies'] === 1 ? 'empreiteira' : 'empreiteiras') . ' no filtro'"
                    :values="$summary['month_totals']" :labels="$summary['month_labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-money-dollar-circle-line" label="Valor pedido (ADS)" :value="$money($summary['value'])"
                    :note="'Média de ' . $money($summary['average_value']) . ' por parcial'"
                    :values="$summary['month_values']" :labels="$summary['month_labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-route-line" label="Em andamento" :value="$summary['in_progress']"
                    :note="$summary['supervision'] . ' em fiscalização · ' . $summary['payment'] . ' em medição · ' . $summary['paid'] . ' pagas'"
                    :values="$summary['month_totals']" :labels="$summary['month_labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-close-circle-line" label="Rejeitadas" :value="$summary['rejected']"
                    :note="$dec($summary['rejected_rate']) . '% do total · decisão da Engenharia em ' . ($summary['approval_days'] === null ? '—' : $dec($summary['approval_days']) . ' dias')"
                    :values="$summary['month_rejected']" :labels="$summary['month_labels']" />
            </div>
        </div>

        <h2 class="mp-section-title">Parciais informadas</h2>

        <section class="mp-panel mb-3" aria-labelledby="ph-table-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="ph-table-title" class="mp-panel-title">Parciais do mais recente para o mais antigo, com a posição no caminho até a finalização</h3>
                    <div class="mp-panel-sub">Passe o mouse em uma etapa para ver a data e quem decidiu. Etapa em vermelho = onde foi rejeitada.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="mp-pill">{{ $lists->total() }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}</span>
                    <select class="form-select form-select-sm w-auto" wire:model="perPage" aria-label="Registros por página">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
            </div>

            @if ($lists->total() === 0)
                <x-closure.empty-state icon="ri-history-line" title="Nenhum informe parcial no histórico" text="{{ $hasFilters ? 'Nenhum resultado nos filtros atuais. Limpe os filtros para ver tudo.' : 'Quando a Engenharia decidir uma parcial, ela aparece aqui.' }}" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-8"><col class="col-pct-6"><col class="col-pct-7"><col class="col-pct-7"><col class="col-pct-11">
                            <col class="col-pct-9"><col class="col-pct-9"><col class="col-pct-27"><col class="col-pct-9"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Nota/OV</th>
                                <th scope="col" class="text-center">Escopo</th>
                                <th scope="col">Ordens</th>
                                <th scope="col">Rubrica</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col" class="text-end">Valor ADS</th>
                                <th scope="col" class="text-center">Enviada em</th>
                                <th scope="col" class="text-center">Posição</th>
                                <th scope="col" class="text-center">Situação</th>
                                <th scope="col" class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $st       = $this->partialStatus($list);
                                    $headline = \App\Services\Engineers\PartialProgress::headline($list);
                                @endphp
                                <tr wire:key="hist-{{ $list->id }}">
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
                                    <td class="text-truncate" title="{{ $list->Company?->name }}">{{ $list->Company?->name ?? 'Desconhecida' }}</td>
                                    <td class="text-end fw-bold">{{ $money((float) $list->value) }}</td>
                                    <td class="text-center">
                                        <div class="date-tile" title="Enviada {{ $list->created_at->format('d/m/Y H:i') }} · há {{ $list->created_at->diffForHumans(null, true) }}">
                                            <span class="date-tile-day">{{ $list->created_at->format('d') }}</span>
                                            <span class="date-tile-month">{{ mb_strtoupper($list->created_at->translatedFormat('M')) }}</span>
                                            <span class="date-tile-year">{{ $list->created_at->format('Y') }}</span>
                                        </div>
                                        <div class="date-tile-time"><i class="ri-time-line"></i> {{ $list->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <x-engineers.partial-steps :partial="$list" />
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $st['color'] }}">{{ $st['status'] }}</span>
                                        <div class="small text-muted">{{ $headline }}</div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="row-action border-0" wire:click.prevent="$emitTo('engineers.inform-detail', 'open_inform', 'partial', {{ $list->id }})">
                                            <i class="ri-eye-line"></i> Detalhes
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

        <h2 class="mp-section-title">Como estão indo</h2>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por etapa</h3><div class="mp-panel-sub">Onde estão as parciais do filtro</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$stageRows" caption="Informes parciais por etapa" :headers="['Etapa', 'Parciais', 'Detalhe']" empty="Nenhuma parcial no filtro" />
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por empreiteira</h3><div class="mp-panel-sub">Top 8 por volume; âmbar = 30% ou mais rejeitadas</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$companyRows" caption="Informes parciais por empreiteira" :headers="['Empreiteira', 'Parciais', 'Detalhe']" empty="Nenhuma parcial no filtro" />
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="modal fade" id="partialBulkSearchModal" tabindex="-1" aria-labelledby="partialBulkSearchModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content cpop">
                <div class="cpop-head">
                    <span class="cpop-icon tone-blue"><i class="ri-list-check-2"></i></span>
                    <div>
                        <h2 class="cpop-title" id="partialBulkSearchModalLabel">Busca em massa</h2>
                        <div class="cpop-sub">Cole vários números de uma vez: notas, OVs, ordens ou DRs.</div>
                    </div>
                    <button type="button" class="cpop-close" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <label for="bulkSearchText" class="cpop-label">Um item por linha, ou separados por vírgula, ponto e vírgula ou espaço</label>
                    <textarea class="form-control" id="bulkSearchText" rows="10" wire:model.defer="bulk_search"></textarea>
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger btn-dash" wire:click="clearBulkSearch" data-bs-dismiss="modal">Limpar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="applyBulkSearch"><i class="ri-search-line"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('engineers.inform-detail', key('inform-detail'))

    <script>
        window.addEventListener('hide-bulk-search-modal', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('partialBulkSearchModal'));
            if (modal) {
                modal.hide();
            }
        });

        window.addEventListener('notify', function(event) {
            const detail = event.detail || {};
            if (window.Swal) {
                Swal.fire({
                    icon: detail.type || 'info',
                    text: detail.message || 'Operação concluída.',
                    timer: 3500,
                    showConfirmButton: false
                });
            }
        });
    </script>
</div>
