@php
    $ranges = $filters['from'] || $filters['to']
        ? ($filters['from']?->format('d/m/Y') ?? 'início') . ' a ' . ($filters['to']?->format('d/m/Y') ?? 'hoje')
        : 'todo o histórico';
@endphp

<div class="closure-dash my-panel">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Informes finais</div>
            <h1 class="mp-hero-title">Lista de informes</h1>
            <p class="mp-hero-sub">Todos os informes finais de obra, do mais recente para o mais antigo: onde foi, de quem é, em que situação está e se a ADS chegou.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel com todos os informes dos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros da lista">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small mb-1" for="wl-search">Buscar</label>
                <div class="input-group input-group-sm">
                    <input id="wl-search" type="text" class="form-control" placeholder="Nota, ordem ou empreiteira" wire:model.debounce.500ms="search">
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas ou ordens de uma vez">
                        <i class="ri-file-copy-line"></i>
                    </button>
                </div>
                @if ($multiSearch)
                    <small class="text-muted">Busca em lote: {{ count($multiSearch) }} {{ count($multiSearch) === 1 ? 'item' : 'itens' }}</small>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small mb-1" for="wl-company">Empreiteira</label>
                <select id="wl-company" class="form-select form-select-sm" wire:model="company">
                    <option value="">Todas as empreiteiras</option>
                    @foreach ($options['companies'] as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="wl-rubrica">Rubrica</label>
                <select id="wl-rubrica" class="form-select form-select-sm" wire:model="rubrica">
                    <option value="">Todas</option>
                    @foreach ($options['rubricas'] as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="wl-city">Município</label>
                <select id="wl-city" class="form-select form-select-sm" wire:model="city">
                    <option value="">Todos</option>
                    @foreach ($options['cities'] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="wl-ads">ADS</label>
                <select id="wl-ads" class="form-select form-select-sm" wire:model="ads">
                    <option value="">Todas</option>
                    @foreach (\App\Services\Engineers\WorkedListQuery::ADS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="wl-changes">Alteração no projeto</label>
                <select id="wl-changes" class="form-select form-select-sm" wire:model="changes">
                    <option value="">Todas</option>
                    <option value="sim">Com alteração</option>
                    <option value="nao">Sem alteração</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="wl-situacao">Situação</label>
                <select id="wl-situacao" class="form-select form-select-sm" wire:model="situacao">
                    @foreach (\App\Services\Engineers\WorkedListQuery::SITUATIONS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-6">
                <span class="form-label small d-block mb-1">Informado em</span>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                        @foreach (\App\Services\Engineers\WorkedListQuery::PRESETS as $key => $label)
                            <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="dt_in" aria-label="De">
                    <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="dt_out" aria-label="Até">
                </div>
            </div>
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Período exibido: <strong>{{ $ranges }}</strong>. Ordem: informado mais recentemente primeiro.
            @if ($active)
                <button type="button" class="btn btn-link btn-sm p-0 ms-2" wire:click="clearFilters">limpar filtros</button>
            @endif
        </div>
    </section>

    <div wire:loading.class="is-loading-soft" wire:target="search,company,rubrica,city,ads,changes,situacao,setPreset,dt_in,dt_out,perPage,gotoPage,nextPage,previousPage">

        <h2 class="mp-section-title">Resumo do recorte</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-file-list-3-line" label="Informes no recorte" :value="number_format($summary['total'], 0, ',', '.')"
                    :note="\App\Services\Engineers\WorkedListQuery::SITUATIONS[$filters['situacao']] . ' · ' . $ranges" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-checkbox-circle-line" label="Com ADS" :value="number_format($summary['with_ads'], 0, ',', '.')"
                    :note="$summary['tacit'] . ' ' . ($summary['tacit'] === 1 ? 'tácita' : 'tácitas')" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-time-line" label="Sem ADS" :value="number_format($summary['without_ads'], 0, ',', '.')"
                    :note="$summary['overdue'] . ' com o prazo vencido'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-edit-box-line" label="Alteração no projeto" :value="number_format($summary['changes'], 0, ',', '.')"
                    :note="$filters['situacao'] === 'informados' ? 'Informes que mudaram o projeto' : $summary['rejected'] . ' rejeitados no recorte'" />
            </div>
        </div>

        <h2 class="mp-section-title">Informes</h2>

        <section class="mp-panel mb-3" aria-labelledby="wl-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="wl-title" class="mp-panel-title">{{ $lists->total() ? 'Exibindo ' . $lists->firstItem() . ' a ' . $lists->lastItem() . ' de ' . number_format($lists->total(), 0, ',', '.') : 'Nenhum informe' }}</h3>
                    <div class="mp-panel-sub">Clique em “Ver informe” para o dossiê completo: evidências, PEP, operações e rejeições.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0" for="wl-pp">Por página</label>
                    <select id="wl-pp" class="form-select form-select-sm w-auto" wire:model="perPage">
                        @foreach ([25, 50, 100, 250] as $n)
                            <option value="{{ $n }}">{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-search-line" title="Nenhum informe nos filtros atuais"
                    text="Amplie o período, troque a situação ou limpe os filtros para ver mais informes." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-16"><col class="col-pct-12">
                            <col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-9"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Obra</th>
                                <th scope="col">Local</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col">Situação</th>
                                <th scope="col">Datas</th>
                                <th scope="col">ADS</th>
                                <th scope="col">Detalhes</th>
                                <th scope="col" class="text-end">Informe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $report)
                                @php
                                    $adsInfo = $service->adsOf($report);
                                    $scopes  = collect($report->finalScopeBadges());
                                @endphp
                                <tr wire:key="wl-{{ $report->id }}">
                                    <td>
                                        <strong>{{ $report->Note?->note }}</strong>
                                        <div class="small text-muted">{{ $report->Orders->pluck('ordem')->implode(' · ') ?: '—' }}</div>
                                    </td>
                                    <td>
                                        {{ $report->Note?->lexp ?: '—' }}
                                        <div class="small text-muted">{{ $report->Note?->rubrica ?: '—' }}</div>
                                    </td>
                                    <td class="text-truncate" title="{{ $report->Company?->name }}">
                                        {{ $report->Company?->name ?? '—' }}
                                        <div class="small text-muted text-truncate">{{ $report->team ?: ($report->responsible ?: '—') }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $report->rejected ? 'text-bg-danger' : ($report->current_status_class ?: 'text-bg-success') }}">{{ $report->rejected ? 'Rejeitado' : ($report->current_status_label ?: 'Informado') }}</span>
                                        <div class="mt-1">
                                            @foreach ($scopes as $scope)
                                                <span class="badge {{ $scope['class'] }}">{{ $scope['label'] }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ ($report->informed_at ?? $report->created_at)?->format('d/m/Y') }}</strong>
                                        <div class="small text-muted">executado em {{ $report->date?->format('d/m/Y') ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $adsInfo['class'] }}">{{ $adsInfo['label'] }}</span>
                                        <div class="small text-muted">
                                            @if ($adsInfo['at'])
                                                {{ $adsInfo['at']->format('d/m/Y') }}
                                            @elseif ($adsInfo['due'])
                                                prazo {{ $adsInfo['due']->format('d/m/Y') }}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($report->changes)
                                            <span class="badge text-bg-warning" title="Mudança no projeto">Alteração</span>
                                        @endif
                                        @if ($report->equipment_count)
                                            <span class="badge text-bg-dark" title="Equipamentos informados">{{ $report->equipment_count }} equip.</span>
                                        @endif
                                        @if ($report->returnwork_count)
                                            <span class="badge text-bg-danger" title="Vezes que foi rejeitado">{{ $report->returnwork_count }} rej.</span>
                                        @endif
                                        @if (!$report->changes && !$report->equipment_count && !$report->returnwork_count)
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="row-action border-0" wire:click.prevent="$emitTo('engineers.inform-detail', 'open_inform', 'final', {{ $report->id }})">
                                            <i class="ri-eye-line"></i> Ver informe
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mp-panel-foot">
                    {{ $lists->links() }}
                </div>
            @endif
        </section>
    </div>

    @livewire('engineers.inform-detail', key('inform-detail'))

    {{-- Busca em lote --}}
    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content inform-modal">
                <div class="modal-header inform-header">
                    <h5 class="modal-title" id="buscarMultiLabel">BUSCAR VÁRIAS NOTAS OU ORDENS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small" for="advanceSearch">Cole uma nota ou ordem por linha</label>
                    <textarea class="form-control" id="advanceSearch" rows="10" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">Buscar</button>
                </div>
            </div>
        </div>
    </div>
</div>
