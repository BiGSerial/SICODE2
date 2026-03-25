@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div class="supervision-page">
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />

    <style>
        .supervision-page {
            --sp-bg: #f6f7fb;
            --sp-surface: #ffffff;
            --sp-ink: #1f2933;
            --sp-muted: #6b7280;
            --sp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--sp-bg);
            padding: 1.5rem 0;
        }

        .supervision-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .supervision-header h2 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .supervision-meta {
            color: rgba(248, 250, 252, 0.8);
            font-size: 0.9rem;
        }

        .filter-shell {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 0.9rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .filter-shell h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--sp-muted);
            margin-bottom: 0.65rem;
        }

        .summary-bar {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            margin-bottom: 1rem;
        }

        .summary-item {
            color: var(--sp-muted);
            font-size: 0.92rem;
        }

        .summary-item strong {
            color: var(--sp-ink);
        }

        .table-card {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .table-card .card-header {
            padding: 0.9rem 1.25rem;
        }

        .table-card .table-responsive {
            padding: 0;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .table-card .sup-table {
            border-collapse: separate;
            border-spacing: 0 0.45rem;
            margin: 0;
        }

        .table-card .sup-table thead th {
            border: 0;
            background: #1f2937;
            color: #f8fafc;
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        }

        .table-card .sup-table tbody tr {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .table-card .sup-table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .table-card .sup-table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        /* Mantem cores de prioridade da linha quando vierem do backend */
        .table-card .sup-table tbody td.table-primary,
        .table-card .sup-table tbody td.table-warning,
        .table-card .sup-table tbody td.table-success,
        .table-card .sup-table tbody td.table-danger {
            border-color: rgba(15, 23, 42, 0.08);
        }

        /* Aplica fundo neutro somente quando a celula nao tiver classe de cor */
        .table-card .sup-table tbody td:not(.table-primary):not(.table-warning):not(.table-success):not(.table-danger) {
            background: #f8fafc;
        }

        .table-card .sup-table tbody td:first-child {
            border-left: 1px solid #e2e8f0;
            border-top-left-radius: 0.7rem;
            border-bottom-left-radius: 0.7rem;
        }

        .table-card .sup-table tbody td:last-child {
            border-right: 1px solid #e2e8f0;
            border-top-right-radius: 0.7rem;
            border-bottom-right-radius: 0.7rem;
        }

        .table-card .sup-table .row-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 66px;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            border: 1px solid transparent;
        }

        .table-card .sup-table .chip-partial {
            background: #fff7ed;
            border-color: #fdba74;
            color: #9a3412;
        }

        .table-card .sup-table .chip-final {
            background: #ecfdf5;
            border-color: #6ee7b7;
            color: #065f46;
        }

        .table-card .sup-table .chip-neutral {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #3730a3;
        }

        .table-card .sup-table .chip-muted {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }

        .control-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.9rem;
        }

        .control-card {
            background: linear-gradient(160deg, #ffffff, #f8fafc);
            border: 1px solid #dbe3ef;
            border-radius: 0.9rem;
            padding: 0.85rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .control-card h6 {
            margin-bottom: 0.55rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .quick-actions .btn {
            min-height: 42px;
            border-radius: 0.65rem;
            font-weight: 600;
        }

        .filters-row {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.85rem;
            padding: 0.7rem;
            justify-content: flex-end;
        }

        @media (min-width: 992px) {
            .control-grid {
                grid-template-columns: 1fr 1fr 1fr 1.25fr;
            }

            .quick-actions {
                grid-template-columns: repeat(2, minmax(130px, 1fr));
            }
        }
    </style>

    <div class="container-fluid px-3 px-lg-4">
        <div class="supervision-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>LISTA PARA {{ mb_strtoupper($service->service) }}</h2>
                <div class="supervision-meta">
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="text-lg-end">
                @if ($update)
                    <div class="supervision-meta">Ultima Atualizacao</div>
                    <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </div>
        </div>

        <div class="filter-shell mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="control-grid">
                    <div class="control-card">
                        <h6>Paginacao</h6>
                        <div class="form-floating">
                            <select wire:model="perPage" class="form-select border border-secondary" id="perPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                            <label for="perPage">Registros por pagina</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Busca</h6>
                        <div class="position-relative">
                            <input wire:model.bounce.2s="search" type="text" class="form-control border border-secondary pe-5"
                                id="search" placeholder="Buscar">
                            <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Tipo de Nota</h6>
                        <div class="btn-group w-100" role="group" aria-label="Tipo de nota">
                            <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="1" id="typeNote1">
                            <label class="btn btn-outline-primary" for="typeNote1">Nota</label>
                            <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="2" id="typeNote2">
                            <label class="btn btn-outline-primary" for="typeNote2">OV</label>
                            <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="" id="typeNote3">
                            <label class="btn btn-outline-primary" for="typeNote3">Ambos</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Acoes Rapidas</h6>
                        <div class="quick-actions">
                            <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}" wire:click.prevent="filterStatus()">
                                {{ Notestatus::status(1)->status }}
                                @if ($not_assigned)
                                    <span class="badge text-bg-success">ON</span>
                                @else
                                    <span class="badge text-bg-danger">OFF</span>
                                @endif
                            </button>

                            <button type="button" class="btn btn-secondary" wire:click.prevent="filterD5()">
                                Somente D5
                                @if ($filter_d5)
                                    <span class="badge text-bg-success">ON</span>
                                @else
                                    <span class="badge text-bg-danger">OFF</span>
                                @endif
                            </button>

                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#add_mass_dds">
                                <i class="ri-checkbox-multiple-fill"></i> Att DD
                            </button>
                            <button class="btn btn-primary" wire:click.prevent='go_att_mass'>
                                <i class="ri-checkbox-multiple-fill"></i> Atribuir
                            </button>
                            <button class="btn btn-primary" wire:click.prevent='export_excel'>
                                <i class="ri-file-excel-2-line"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="filters-row d-flex flex-wrap align-items-center justify-content-end gap-2 mt-3">
                    <span class="small text-uppercase fw-semibold text-secondary me-1">Filtros adicionais</span>
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'supervision', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                        @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regiao', 'group_filter' => 'supervision', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('regional'))
                        @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'rdMunicipio', 'filter' => 'Municipio', 'group_filter' => 'supervision', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                        @livewire('components.filter.remove-all', ['group_filter' => 'supervision'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="row align-items-center g-2">
                <div class="col-12 col-lg-6">
                    @if ($lists->count())
                        {{ $lists->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

    <div class="table-card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }} - @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            <div class="card-header fw-bold text-bg-secondary">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-hover mb-0 sup-table">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model.defer="selectAll"
                                    wire:click="setSelectAll()" @disabled($this->checkAllSelect($lists))>
                            </th>
                            <th scope="col" class="fw-bold text-center">Tipo</th>
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">Ordem</th>
                            <th scope="col" class="fw-bold text-center">DD</th>
                            <th scope="col" class="fw-bold text-center">MMGD</th>
                            <th scope="col" class="fw-bold text-center">Postes</th>
                            <th scope="col" class="fw-bold text-center">Informado Em</th>
                            <th scope="col" class="fw-bold text-center">Dt ADS</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Custo</th>
                            <th scope="col" class="fw-bold text-center">Fiscalizações</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center">Dias D5</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $e = $this->needBlock($list); // ['block'=>.., 'command'=>.., 'color'=>.., 'reason'=>..]
                                $rowClass = $e['color'];
                                $block = $e['block'];
                                $command = $e['command'];
                                $production = $e['production'];
                                $reason = $e['reason'];

                                // mantém tua lógica de “parcial” apenas pra exibir a tag:
                                $partial = $e['isPartial'];
                                $latestValidPartial = $list->Partials
                                    ?->where('allow', true)
                                    ->where('deny', false)
                                    ->sortByDesc('created_at')
                                    ->first();

                                if ($list->FiveNote) {
                                    $dateFive = Carbon::parse($list->FiveNote->completed_at);
                                } else {
                                    $dateFive = null;
                                }

                                $adsAt = null;
                                $adsDate = null;
                                if ($list->OldAds->isNotEmpty()) {
                                    $adsDate = $list->OldAds->last()->date;
                                    $adsAt = optional($adsDate)->format('d/m/Y H:i:s');
                                } elseif ($list->Adsform) {
                                    $adsDate = $list->Adsform->created_at;
                                    $adsAt = optional($adsDate)->format('d/m/Y H:i:s');
                                }
                                $isTacitAds = (bool) ($list->Adsform?->tacit ?? false);

                                $informedDate = null;
                                if ($list->WorkForm) {
                                    $informedDate = $list->WorkForm->informed_at;
                                } elseif ($latestValidPartial) {
                                    $informedDate = $latestValidPartial->created_at;
                                }

                                $daysFromInformed = null;
                                if ($informedDate) {
                                    $daysFromInformed = Carbon::parse($informedDate)->diffInDays(Carbon::now(), false);
                                }

                                $adsFromInformedDays = null;
                                if ($informedDate && $adsDate) {
                                    $adsFromInformedDays = Carbon::parse($informedDate)->diffInDays(Carbon::parse($adsDate), false);
                                }
                            @endphp


                            <tr class="align-middle">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled($block)>
                                </td>
                                {{-- @can('management')
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                        </td>
                                    @endcan --}}
                                <td class="text-center {{ $rowClass }}">
                                    <span class="row-chip {{ $partial ? 'chip-partial' : 'chip-final' }}">
                                        {{ $partial ? 'P' : 'F' }}
                                    </span>
                                </td>
                                <td class="fw-bold copy-text text-center {{ $rowClass }}"
                                    data-value="{{ $list->note }}">
                                    @if ($list->FiveNote?->is_completed && !$list->FiveNote?->is_supervisioned)
                                        <span class="badge text-bg-success fs-6">D5 {{ $list->note }}</span>
                                    @else
                                        {{ $list->note }}
                                    @endif
                                    @if ($list->pze == '25')
                                        <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                            data-bs-content="Nota com prazo de execução de {{ $list->pze }} dias"
                                            style="z-index: 9999;" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="ri-fire-line text-danger fw-bold"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center {{ $rowClass }} text-nowrap">
                                    @if ($list->WorkForm)
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @elseif ($latestValidPartial)
                                        @foreach ($latestValidPartial->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="fw-bold text-danger {{ $rowClass }} text-center">
                                    {{ $list->Wpas->count() ? (!$list->Wpas->last()->production_id ? $list->Wpas->last()->dd : '') : '' }}
                                </td>
                                <td class="fw-bold text-danger {{ $rowClass }} text-center">
                                    @if ($list->mmgd)
                                        <span class="row-chip chip-neutral">MMGD</span>
                                    @else
                                        <span class="row-chip chip-muted">---</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-primary {{ $rowClass }} text-center">
                                    {{ isset($list->postes) ? $list->postes : '---' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }} text-nowrap">
                                    @if ($informedDate)
                                        {{ Carbon::parse($informedDate)->format('d/m/Y') }}
                                        <br>
                                        <span class="badge
                                            @if ($daysFromInformed <= 20) text-bg-success
                                            @elseif ($daysFromInformed >= 28)
                                                text-bg-danger
                                            @else
                                                text-bg-warning
                                            @endif">
                                            {{ $daysFromInformed }} dia(s)
                                        </span>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="fw-bold text-primary text-center {{ $rowClass }} text-nowrap">
                                    @if ($adsAt)
                                        {{ $adsAt }}
                                        @if (!is_null($adsFromInformedDays))
                                            <br><span class="badge text-bg-info mt-1">{{ $adsFromInformedDays }} dia(s)</span>
                                        @endif
                                        @if ($isTacitAds)
                                            <br><span class="badge text-bg-warning mt-1">TÁCITO</span>
                                        @endif
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ mb_strtoupper($list->numPedido) }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->rubrica }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if (!empty($list->lexp))
                                        {{ $list->lexp }}
                                    @else
                                        <span tabindex="1" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Editar Município"
                                            data-bs-content="Clique para editar o município faltante para esta nota.">
                                            <button class="btn btn-sm btn-secondary"
                                                wire:click.prevent="$emit('editMunicipio', '{{ $list->id }}')">Edit</button>
                                        </span>
                                    @endif
                                </td>
                                <td class="fw-bold text-center {{ $rowClass }}">
                                    R$ {{ number_format((float) ($list->orders?->sum('service_cost') ?? 0), 2, ',', '.') }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}" tabindex="2"
                                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Levantamentos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($count = $this->hasPublicationCount($list))
                                        @php
                                            if ($production && $production->User) {
                                                $name = explode(' ', $production->User->name);
                                                $name = $name[0] . ' ' . end($name);
                                            } else {
                                                $name = 'Desconhecido';
                                            }
                                        @endphp
                                        <span class="badge text-bg-dark">{{ $count }}</span><br>
                                        {{ $name }}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->nstats }}<br><span>{{ $list->centerjob }}</span>
                                </td>
                                <td scope="col" class="text-center text-bg-info">
                                    @if ($dateFive)
                                        {{ $dateFive?->startOfDay()->diffInDays() }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if ($list->pze_parecer === 'Vencido')
                                        <span class="badge text-bg-danger">VENCIDO</span>
                                    @elseif ($list->pze_parecer === 'Não vencido')
                                        <span class="badge text-bg-success">EM PRAZO</span>
                                    @else
                                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                    @endif
                                </td>


                                <td class="fw-bold text-center {{ $rowClass }}" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="{{ $reason }}">


                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="get_single_note({{ $list->id }})"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Despachar esta Nota/OV"></i>
                                    @else
                                        @php
                                            if ($production && $production->Company) {
                                                $name = explode(' ', $production->Company->name)[0];
                                            } else {
                                                $name = 'Desconhecido';
                                            }
                                        @endphp
                                        <span style="font-size: 11px">{{ $name }}</span>
                                        @if ($command)
                                            <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                                style="cursor: pointer;"
                                                wire:click.prevent="get_single_note({{ $list->id }})"></i>
                                        @endif
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
    </div>
    <div class="summary-bar mt-3">
        <div class="row align-items-center g-2">
            <div class="col-12 col-lg-6">
                {{ $lists->links() }}
            </div>
            <div class="col-12 col-lg-6 text-lg-end">
                <div class="summary-item">
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                    <strong>{{ $lists->lastItem() }}</strong> de
                    <strong>{{ $lists->total() }}</strong> registros.
                </div>
            </div>
        </div>
    </div>
    </div>


    {{-- MODALS --}}

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>


    <div wire:ignore.self class="modal fade" id="add_mass_dds" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Atribuir DD em {{ $service->service }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                            MASSA:</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="10" style="resize: none;"
                            placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                    </div>
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="mass_modal">Atribuir</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="add_mass_notes" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Despachar {{ $service->service }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="row">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type" disabled>
                                    <option selected>Selecione</option>
                                    <option value="1">Pilha</option>
                                    <option value="2">Individual</option>
                                </select>
                            </div>

                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Empresa:</label>

                                <select class="form-select form-select-sm" aria-label="" wire:model="company_s">
                                    <option selected>Selecione</option>

                                    @if ($company_l && $company_l->count())

                                        @foreach ($company_l as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            @if ($type === '2')

                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="exampleFormControlInput1" class="form-label">Buscar
                                            Usuario:</label>
                                        <input wire:model="search_user" class="form-control form-control-sm"
                                            type="text" placeholder="Digite um nome" aria-label="">
                                    </div>
                                    <div class="col">
                                        <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                        <select class="form-select form-select-sm" aria-label=""
                                            wire:model="user_s">

                                            @if ($user_l && $user_l->count())
                                                <option value="" selected>Selecione um Usuário</option>
                                                @foreach ($user_l as $user)
                                                    <option wire:key='{{ $user->id }}'
                                                        value="{{ $user->id }}">{{ $user->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option selected>Escolha uma Empresa Primeiro</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>


                                {{-- <div class="mb-2 ">
                                    <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                                        MASSA:</label>
                                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"
                                        placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                                </div>
                                <div class="mb-3">
                                    <button class="btn-sm btn btn-primary" wire:click.prevent="add_dd">DD em
                                        MASSA</button>
                                </div> --}}
                            @endif

                            <div class="col-12 fw-bold">
                                DESPACHANDO {{ $notes->count() }} OV/NOTA(S)
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Note</th>
                                        <th scope="col">Desc</th>
                                        <th scope="col">DD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            <td>
                                                @if ($this->type === '2' && isset($list))
                                                    @php
                                                        $dd = $list->Wpas->count()
                                                            ? (!$list->Wpas->last()->production_id
                                                                ? $list->Wpas->last()->dd
                                                                : '')
                                                            : '';

                                                        $additionalData[$index] = $dd;
                                                    @endphp


                                                    <input wire:model.defer="additionalData.{{ $index }}"
                                                        class="form-control form-control-sm" type="text"
                                                        placeholder="Informe a DD" aria-label="" value="">
                                                @endif

                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="confirm_att"
                        wire:loading.attr="disabled" wire:target="confirm_att">
                        Despachar
                    </button>
                </div>
            </div>
        </div>
    </div>




    {{-- END MODALS --}}

</div>
