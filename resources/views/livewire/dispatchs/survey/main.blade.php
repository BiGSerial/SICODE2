@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
@endphp
<div class="survey-main-page">

    @push('css')
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

            .survey-main-page .table-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
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
            .survey-main-page .table-card .main-table tbody td.table-danger {
                border-color: rgba(15, 23, 42, 0.08);
            }

            .survey-main-page .table-card .main-table tbody td:not(.table-primary):not(.table-warning):not(.table-success):not(.table-danger):not(.text-bg-secondary) {
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
        </style>
    @endpush
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="row">
        <div class="col-1">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>
        <div class="mb-3 col-md-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="email"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>
        <div class="col-md-9 d-flex mb-3 justify-content-end py-4">
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
            <div class="dropdown mx-1">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Rubrica
                    @if (count($rubrica_s))
                        <span class="badge text-bg-light">{{ count($rubrica_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($rubrica_l) && $rubrica_l->count() > 0)
                            @foreach ($rubrica_l as $rubrica)
                                @if ($rubrica->rubrica)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="rubrica_s"
                                            wire:key="{{ $rubrica->rubrica }}" value="{{ $rubrica->rubrica }}">
                                        <label for="opcao1">{{ $rubrica->rubrica }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>
            </div>

            <div class="dropdown mx-1 ">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Região
                    @if (count($region_s))
                        <span class="badge text-bg-light">{{ count($region_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($region_l) && $region_l->count() > 0)
                            @foreach ($region_l as $region)
                                @if ($region->regiao)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="region_s"
                                            wire:key="{{ $region->regiao }}" value="{{ $region->regiao }}">
                                        <label for="opcao1">{{ $region->regiao }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>
            </div>

            <div class="dropdown mx-1 ">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Regional
                    @if (count($district_s))
                        <span class="badge text-bg-light">{{ count($district_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($district_l) && $district_l->count() > 0)
                            @foreach ($district_l as $district)
                                @if ($district->baseConstrucao)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="district_s"
                                            wire:key="{{ $district->baseConstrucao }}"
                                            value="{{ $district->baseConstrucao }}">
                                        <label for="opcao1">{{ $district->baseConstrucao }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>
            </div>

            <div class="dropdown mx-1 ">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Município
                    @if (count($city_s))
                        <span class="badge text-bg-light">{{ count($city_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($city_l) && $city_l->count() > 0)
                            @foreach ($city_l as $city)
                                @if ($city->cidade)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="city_s"
                                            wire:key="{{ $city->cidade }}" value="{{ $city->cidade }}">
                                        <label for="opcao1">{{ $city->municipio }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>
            </div>


            <div class="mx-1 ">
                <button class="btn btn-primary" wire:click.prevent="filter_save" tabindex="0"
                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                    data-bs-content="Aplicar Filtros"><i class="ri-filter-fill"></i>
                </button>
            </div>
            <div class="mx-1 "><button class="btn btn-primary" wire:click.prevent="filter_clean" tabindex="0"
                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                    data-bs-content="Remover Filtros"><i class="ri-filter-off-fill"></i>
                </button></div>


        </div>

        <div class="mb-3">
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
        </div>
    </div>

    <div class="row">

        @if (!$lists->count())
            {{-- <div class="col-6">
                @livewire('components.manualnote.manualnote', ['service' => $service->uuid])
            </div> --}}
        @elseif ($lists->count())
            <div class="col-6">
                {{ $lists->links() }}
            </div>
        @endif
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.
                @if ($update)
                    Ultima Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </span>
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
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
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
                        <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Adicionar numeração DD em Massa"> <button
                                class="btn btn-sm btn-success me-2" data-bs-toggle="modal"
                                data-bs-target="#add_mass_dds"><i class="ri-checkbox-multiple-fill"></i> Att
                                DD</button></span>

                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Atribuir Notas em Massa"><i class="ri-checkbox-multiple-fill"></i>
                            Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Exportar as Notas SELECIONADAS para o EXCEL"><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
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
                            <th scope="col" class="fw-bold text-center">DD</th>
                            <th scope="col" class="fw-bold text-center">MMGD</th>
                            <th scope="col" class="fw-bold text-center">Criado Em</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Grp1</th>
                            <th scope="col" class="fw-bold text-center">Grp2</th>
                            <th scope="col" class="fw-bold text-center">Grp4</th>
                            <th scope="col" class="fw-bold text-center">Grp5</th>
                            <th scope="col" class="fw-bold text-center">Levantamentos</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            {{-- <th scope="col" class="fw-bold text-center">Pze</th> --}}

                            <th scope="col" class="fw-bold text-center">Prazo Real</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            if (!function_exists('dispatchMainShortName')) {
                                function dispatchMainShortName($name)
                                {
                                    $name = trim((string) $name);

                                    if ($name === '') {
                                        return 'Desconhecido';
                                    }

                                    $parts = collect(explode(' ', $name))->filter()->values();
                                    $shortName = $parts->count() > 1
                                        ? $parts->first() . ' ' . $parts->last()
                                        : $parts->first();

                                    return mb_convert_case(mb_strtolower($shortName, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                                }
                            }
                        @endphp
                        @foreach ($lists as $list)
                            @php
                                $block = null;
                                $production = null;
                                $rowClass = '';
                                $lastUser = '';
                                $lastCompany = '';
                                $currentAssignee = '';

                                $count = $list->Productions
                                    ->where('service_id', $service->uuid)
                                    ->where('noinconsistency', false);

                                $count2 = $list->Productions
                                    ->where('service_id', $service->uuid)
                                    ->where('completed', true);

                                if ($count2->count()) {
                                    // $lastUser = $list->Productions
                                    //     ->where('service_id', $service->uuid)
                                    //     ->where('completed', true)
                                    //     ->last()->User->name;

                                    $lastUser = dispatchMainShortName($count2->last()->User?->name);
                                }

                                if ($count->count()) {
                                    $production = $count->last();

                                    if (isset($production->Company->name)) {
                                        $lastCompany = explode(' ', $production->Company->name);
                                        $lastCompany = mb_convert_case(mb_strtolower($lastCompany[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                                    } else {
                                        $lastCompany = 'Desconhecido';
                                    }

                                    $currentAssignee = $production->User
                                        ? dispatchMainShortName($production->User->name)
                                        : 'Pilha';

                                    if ($production->dt_note == $list->dt_status || !$production->confirmed) {
                                        $block = true;
                                    }

                                    // $block = true;

                                    $chave = array_search($list->id, $selected);

                                    if ($chave !== false) {
                                        unset($selected[$chave]);
                                        $selected = $selected;
                                    }
                                }

                                $stackProductionAvailable = \App\Support\SicodeRules::openCompanyStackProductionFor($list, Auth()->User(), $service->uuid);
                                $canDispatch = !$block || $stackProductionAvailable;

                                if ($block && !$stackProductionAvailable && $production) {
                                    $rowClass = match (true) {
                                        (int) $production->status === 1 => 'table-warning',
                                        (int) $production->status === 2 => 'table-primary',
                                        (int) $production->status === 5 && !$production->confirmed => 'table-success',
                                        (int) $production->status === 5 && $production->confirmed => 'table-danger',
                                        default => 'table-primary',
                                    };
                                }
                            @endphp


                            <tr class="align-middle">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled(!$canDispatch)>
                                </td>
                                {{-- @can('management')
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                        </td>
                                    @endcan --}}
                                <td class="fw-bold copy-text text-center {{ $rowClass }} @if ($list->is45) text-bg-warning @endif"
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
                                    <x-legal.note-demand-tags :note-id="$list->note_id ?? $list->id" :row-key="'dispatchs-survey-main-'.$list->id" />
                                </td>
                                <td class="fw-bold text-danger text-center {{ $rowClass }}">
                                    {{ \App\Support\SicodeRules::dispatchDdFor($list, $service->uuid) ?? '' }}
                                </td>
                                <td class="fw-bold text-danger text-center {{ $rowClass }}">
                                    {{ $list->mmgd ? 'MMGD' : '' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ date('d/m/Y', strToTime($list->dt_created)) }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ mb_strtoupper($list->numPedido) }}</td>
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
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group1 }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group2 ? $list->group2 : '_____' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group4 ? $list->group4 : '_____' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group5 ? $list->group5 : '_____' }}
                                </td>



                                <td class="fw-light text-center {{ $rowClass }}" tabindex="2" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Levantamentos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($count2->count())
                                        <span class="badge text-bg-dark">{{ $count2->count() }}</span><br>
                                        {{ $lastUser }}
                                    @else
                                        --
                                    @endif
                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->nstats }}<br><span>{{ $list->centerjob }}</span></td>
                                {{-- <td class="fw-light text-center">{{ $list->pze }}</td> --}}
                                @php
                                    $days_left = (new DaysLeft($list))->getDaysLeft();
                                @endphp
                                <td scope="col"
                                    class="text-center
                                        @if ($days_left < 0) text-bg-secondary
                                        @elseif($days_left >= 0 && $days_left < 6)
                                        table-danger
                                        @elseif($days_left >= 6 && $days_left < 10)
                                            table-warning
                                        @else
                                            table-success @endif
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
                                    {{ 30 - $days_left }}
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


                                <td class="fw-bold text-center {{ $rowClass }}">

                                    @can('operator')
                                        @if ($canDispatch)
                                            <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                                style="cursor: pointer;"
                                                wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $list->id }}])"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="{{ $stackProductionAvailable ? 'Assumir/atribuir Nota/OV da pilha da empresa' : 'Despachar esta Nota/OV' }}"></i>
                                        @else
                                            <div style="font-size: 11px; line-height: 1.15;">
                                                <strong>{{ $currentAssignee ?: 'Desconhecido' }}</strong>
                                                <div class="text-muted">{{ $lastCompany }}</div>
                                            </div>
                                        @endif
                                    @endcan

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
        </dic>
    </div>
    <div class="row">
        <div class="col-6">
            {{ $lists->links() }}
        </div>
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.</span>
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

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-'.$service->uuid))




    {{-- END MODALS --}}



</div>
