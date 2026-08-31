@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
@endphp
<div class="survey-main-page">

    <style>
        @keyframes flame {
            0% {
                transform: scaleX(1) scaleY(1);
            }

            25% {
                transform: scaleX(1) scaleY(0.8);
            }

            50% {
                transform: scaleX(-1) scaleY(0.8);
            }

            75% {
                transform: scaleX(-1) scaleY(1);
            }
        }

        .survey-main-page {
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

        .survey-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .survey-header h2 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .survey-meta {
            color: rgba(248, 250, 252, 0.8);
            font-size: 0.9rem;
        }

        .survey-main-page .filter-shell,
        .survey-main-page .summary-bar,
        .survey-main-page .table-card {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 0.9rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .survey-main-page .summary-bar {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .survey-main-page .summary-item {
            color: var(--sp-muted);
            font-size: 0.92rem;
        }

        .survey-main-page .summary-item strong {
            color: var(--sp-ink);
        }

        .survey-main-page .table-card {
            overflow: hidden;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .survey-main-page .table-card .card-header {
            padding: 0.9rem 1.25rem;
        }

        .survey-main-page .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .survey-main-page .table-card .main-table {
            border-collapse: separate;
            border-spacing: 0 0.45rem;
            margin: 0;
        }

        .survey-main-page .table-card .main-table thead th {
            border: 0;
            background: #1f2937;
            color: #f8fafc;
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        }

        .survey-main-page .table-card .main-table tbody tr {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .survey-main-page .table-card .main-table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .survey-main-page .table-card .main-table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .survey-main-page .table-card .main-table tbody td.table-primary,
        .survey-main-page .table-card .main-table tbody td.table-warning,
        .survey-main-page .table-card .main-table tbody td.table-success,
        .survey-main-page .table-card .main-table tbody td.table-danger,
        .survey-main-page .table-card .main-table tbody td.table-secondary {
            border-color: rgba(15, 23, 42, 0.08);
        }

        .survey-main-page .table-card .main-table tbody td:not(.table-primary):not(.table-warning):not(.table-success):not(.table-danger):not(.table-secondary):not(.text-bg-secondary) {
            background: #f8fafc;
        }

        .survey-main-page .table-card .main-table tbody td:first-child {
            border-left: 1px solid #e2e8f0;
            border-top-left-radius: 0.7rem;
            border-bottom-left-radius: 0.7rem;
        }

        .survey-main-page .table-card .main-table tbody td:last-child {
            border-right: 1px solid #e2e8f0;
            border-top-right-radius: 0.7rem;
            border-bottom-right-radius: 0.7rem;
        }

        .survey-main-page .filter-shell .form-label {
            color: var(--sp-muted);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .survey-main-page .filter-shell .btn,
        .survey-main-page .filter-shell .form-select,
        .survey-main-page .filter-shell .form-control {
            border-radius: 0.65rem;
        }
    </style>

    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="container-fluid px-3 px-lg-4">
        <div class="survey-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>LISTA PARA {{ mb_strtoupper($service->service) }}
                    @if ($contractCompanyName)
                        - {{ mb_strtoupper($contractCompanyName) }}
                    @endif
                </h2>
                <div class="survey-meta">
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="text-lg-end">
                @if ($update)
                    <div class="survey-meta">Última Atualização</div>
                    <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </div>
        </div>

        <div class="filter-shell mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-3 align-items-end">
        <div class="col-12 col-md-2 col-xl-1">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="text"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>
        <div class="col-12 col-xl-9 d-flex flex-wrap align-items-center justify-content-xl-end gap-2">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>

            <span class="small text-uppercase fw-semibold text-secondary me-1">Filtros adicionais</span>
            @livewire('components.filter.filter', ['myKey' => 'group1', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'group1', 'filter' => 'Grupo1', 'group_filter' => 'desenho', 'values' => 'group1', 'direction' => 'ASC', 'query' => ''], key('desenho-group1'))
            @livewire('components.filter.filter', ['myKey' => 'group2', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'group2', 'filter' => 'Grupo2', 'group_filter' => 'desenho', 'values' => 'group2', 'direction' => 'ASC', 'query' => ''], key('desenho-group2'))
            @livewire('components.filter.filter', ['myKey' => 'group5', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'group5', 'filter' => 'Grupo5', 'group_filter' => 'desenho', 'values' => 'group5', 'direction' => 'ASC', 'query' => ''], key('desenho-group5'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'desenho', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('desenho-rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'desenho', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('desenho-region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regional', 'group_filter' => 'desenho', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('desenho-regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'rdMunicipio', 'filter' => 'Municipio', 'group_filter' => 'desenho', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('desenho-city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'desenho'], key('desenho-removeAll'))


                </div>
                <div class="mt-3">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0"
                data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="right"
                data-bs-title="Exibir Apenas Notas Nao Atribuidas"
                data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribu[ida. </p> <pA palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click.prevent="filterStatus()">
                    {{ Notestatus::status(1)->status }}
                    @if ($not_assigned)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>

            </div>
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click="$set('only_27', {{ !$only_27 }})">
                    27 Dias
                    @if ($only_27)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>

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
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> até
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
                            @if ($contractCompanyName)
                                - {{ mb_strtoupper($contractCompanyName) }}
                            @endif
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive" wire:ignore.self>
                <table class="table table-sm table-condensed table-hover mb-0 main-table">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall">
                            </th>
                            {{-- @can('management')
                                    <th scope="col" class="fw-bold">Note</th>
                                @endcan --}}
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">DOE</th>
                            <th scope="col" class="fw-bold text-center">MMGD</th>
                            <th scope="col" class="fw-bold text-center">Art90</th>
                            <th scope="col" class="fw-bold text-center">Criado Em</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Material</th>
                            <th scope="col" class="fw-bold text-center">Grp2</th>

                            <th scope="col" class="fw-bold text-center">Grp5</th>
                            <th scope="col" class="fw-bold text-center">Postes L</th>
                            <th scope="col" class="fw-bold text-center">Retorno</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center">DStatus</th>
                            <th scope="col" class="fw-bold text-center">Prazo Real</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            if (!function_exists('reduceName')) {
                                function reduceName($name, bool $first = false): string
                                {
                                    if ($name) {
                                        $name = explode(' ', $name);
                                    } else {
                                        return '';
                                    }

                                    if ($first) {
                                        return $name[0];
                                    } else {
                                        return $name[0] . ' ' . end($name);
                                    }
                                }
                            }

                            if (!function_exists('dstatus')) {
                                function dstatus($note): array
                                {
                                    $dstatus = [
                                        'days' => 0,
                                        'bgColor' => '',
                                    ];

                                    $days = $note->dt_status?->diffInDays();
                                    $dstatus['days'] = $days;

                                    if ($days < 4) {
                                        $dstatus['bgColor'] = 'text-bg-success';
                                    } elseif ($days > 5) {
                                        $dstatus['bgColor'] = 'text-bg-danger';
                                    } else {
                                        $dstatus['bgColor'] = 'text-bg-warning';
                                    }

                                    return $dstatus;
                                }
                            }
                        @endphp
                        @foreach ($lists as $list)
                            @php

                                $e = $this->needBlock($list); // ['block'=>.., 'command'=>.., 'color'=>.., 'reason'=>..]
                                $rowClass = $e['color'];
                                $block = $e['block'];
                                $command = $e['command'];
                                $production = $e['production'];
                                $reason = $e['reason'];
                                $stackProductionAvailable = \App\Support\SicodeRules::openCompanyStackProductionFor($list, Auth()->User(), $service->uuid);
                                $canDispatch = !$block || $command || $stackProductionAvailable;

                                if ($stackProductionAvailable) {
                                    $rowClass = '';
                                    $production = $stackProductionAvailable;
                                    $reason = 'Disponivel na pilha da empresa para atribuicao individual.';
                                }

                                $user = $production?->User;
                                $company = $production?->Company;
                                $assignee = $user?->name ?: ($company?->name ?: 'Desconhecido');

                            @endphp



                            <tr wire:key="{{ $list->id }}"
                                class="align-middle
                                    ">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled(!$canDispatch)>
                                </td>
                                {{-- @can('management')
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                        </td>
                                    @endcan --}}
                                <td class="fw-bold copy-text @if ($list->is45) text-bg-warning @endif {{ $rowClass }}"
                                    data-value="{{ $list->note }}">
                                    <span>
                                        {{ $list->note }}
                                        @if ($list->is45)
                                            <span tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="top"
                                                data-bs-title="NOTA EXPRESSA"
                                                data-bs-content="Nota com prazo de execução de 45 dias"
                                                style="z-index: 9999;" data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                <i class="ri-fire-line text-danger fw-bold"
                                                    style="display: inline-block; animation: flame 1s steps(1) infinite;"></i>
                                            </span>
                                        @endif
                                    </span>
                                    <x-legal.note-demand-tags :note-id="$list->note_id ?? $list->id" :row-key="'dispatchs-desenho-main-'.$list->id" />
                                </td>
                                <td class="fw-bold text-success text-center {{ $rowClass }}">
                                    @if ($list->doe)
                                        <i class="ri-checkbox-circle-line"></i>
                                    @endif
                                </td>
                                <td class="fw-bold text-danger text-center {{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        wire:click.prevent="check_mmgd({{ $list->id }})"
                                        value='{{ $list->id }}' @checked($list->mmgd)>
                                </td>
                                <td class="fw-bold text-danger text-center {{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        wire:click.prevent="check_is45({{ $list->id }})"
                                        value='{{ $list->id }}' @checked($list->is45)>
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ date('d/m/Y', strToTime($list->dt_created)) }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->rubrica }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->lexp }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->material }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->group2 ? $list->group2 : '_____' }}
                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->group5 ? $list->group5 : '_____' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->postes }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}" tabindex="0"
                                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Desenhos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($user)
                                        <span class="badge text-bg-dark">{{ $e['count'] }}</span><br>
                                        {{ $user->name }}
                                    @else
                                        --
                                    @endif

                                </td>

                                @if ($list->type_note != 1)
                                    <td class="fw-light text-center {{ $rowClass }}">{{ $list->nstats }} </td>
                                @else
                                    <td class="fw-light text-center">{{ $list->centerjob }} <span class="text-danger"
                                            style="font-size: 8px;">{{ $list->nstats }}</span></td>
                                @endif

                                @php
                                    $days = dstatus($list);
                                @endphp
                                <td class="fw-light text-center {{ $days['bgColor'] }}" tabindex="0"
                                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Dias no Status"
                                    data-bs-content="
                                    <p>OBS: Os prazos para Nota não seguem com precisão, os prazos regulatórios como as OVs e deverão ser avaliados caso a caso.</p>
                                    <span class='fs-4 text-success'>&#9632;</span> < 4 NO PRAZO <br>
                                    <span class='fs-4 text-warning'>&#9632;</span> >= 4 VENCENDO <br>
                                    <span class='fs-4 text-danger'>&#9632;</span> > 6 VENCIDO <br>
                                    {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                                    ">
                                    {{ $days['days'] }}
                                </td>
                                <td scope="col"
                                    class="text-center
                                    @if ($list->days_left < 0) text-bg-secondary
                                    @elseif($list->days_left >= 0 && $list->days_left < 6)
                                    text-bg-danger
                                    @elseif($list->days_left >= 6 && $list->days_left < 10)
                                        text-bg-warning
                                    @else
                                        text-bg-success @endif
                                "
                                    tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="top" data-bs-title="Prazo Real"
                                    data-bs-content="
                                    <p>Os prazos contados já foram expurgado os tempos em status não contabilizáveis.</p>
                                    <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                                    <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                                    <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                                    <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                                    ">
                                    {{ 30 - $list->days_left }}
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
                                    data-bs-placement="top" data-bs-title="{{ $e['reason'] }}">
                                    @if ($canDispatch)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $list->id }}])"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ $stackProductionAvailable ? 'Assumir/atribuir Nota/OV da pilha da empresa' : 'Despachar nota' }}"></i>
                                    @else
                                        <span style="font-size: 11px" data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-title="Responsavel atual">{{ reduceName($assignee) }}</span>
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
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> até
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

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-'.$service->uuid))


    {{-- END MODALS --}}

</div>
