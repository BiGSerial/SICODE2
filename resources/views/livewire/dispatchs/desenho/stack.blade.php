@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>

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

            @if (!Auth()->User()->contract)
                <div class="dropdown mx-1">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Empresa
                        @if (count($company_fs))
                            <span class="badge text-bg-light">{{ count($company_fs) }}</span>
                        @endif

                    </button>

                    <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                        <form wire:submit.prevent="filter_save">
                            @if (isset($company_l) && $company_l->count() > 0)

                                @foreach ($company_l as $company)
                                    @if ($company->name)
                                        <div class="dropdown-item">
                                            <input type="checkbox" wire:model.defer="company_fs"
                                                wire:key="{{ $company->id }}" value="{{ $company->id }}">
                                            <label for="opcao1">{{ $company->name }}</label>
                                        </div>
                                    @endif
                                @endforeach

                            @endif


                        </form>
                    </div>
                </div>
            @endif


            <div class="dropdown mx-1">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Usuário
                    @if (count($user_fs))
                        <span class="badge text-bg-light">{{ count($user_fs) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">

                        @if (isset($user_fl) && $user_fl->count() > 0)

                            @foreach ($user_fl->sortBy('User.name', SORT_LOCALE_STRING) as $user_f)
                                @if ($user_f->User)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="user_fs"
                                            value="{{ $user_f->user_id }}">
                                        <label for="opcao1">{{ $user_f->User->name }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>
            </div>

            <div class="dropdown mx-1">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Status
                    @if (count($status_s))
                        <span class="badge text-bg-light">{{ count($status_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">

                        @if (isset($status_l) && count($status_l) > 0)
                            @foreach ($status_l as $value)
                                @if ($value)
                                    <div class="dropdown-item {{ Notestatus::status($value)->colorbg }}">
                                        <input type="checkbox" class="form-check-input" wire:model.defer="status_s"
                                            wire:key="status-{{ $value }}" value="{{ $value }}">
                                        <label for="opcao1">{{ Notestatus::status($value)->status }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>
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
                        @if (isset($region_l) && $region_l && $region_l->count() > 0)
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
                        @if (isset($district_l) && $district_l && $district_l->count() > 0)
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
                        @if (isset($city_l) && $city_l && $city_l->count() > 0)
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
                <button class="btn btn-primary" wire:click.prevent="filter_save"><i class="ri-filter-fill"></i>
                </button>
            </div>
            <div class="mx-1 "><button class="btn btn-primary" wire:click.prevent="filter_clean"><i
                        class="ri-filter-off-fill"></i>
                </button>
            </div>


        </div>


        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type"
                    value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type"
                    value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type"
                    value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>
        </div>

        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0"
                data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="right"
                data-bs-title="Filtragem Direta por Status"
                data-bs-content="
        <p>Ao apertar o botão, o sistema filtrará a lista pelo status escolhido. Para remover o filtro, basta limpar os filtros.</p>

       ">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click.prevent="filterStatus(1)">
                    {{ Notestatus::status(1)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 1)->count() }}</span></button>
                <button type="button" class="btn btn-{{ Notestatus::status(2)->color }}"
                    wire:click.prevent="filterStatus(2)">
                    {{ Notestatus::status(2)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 2)->count() }}</span></button>
                <button type="button" class="btn btn-{{ Notestatus::status(4)->color }}"
                    wire:click.prevent="filterStatus(4)">
                    {{ Notestatus::status(4)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 4)->count() }}</span></button>

                <button type="button" class="btn btn-{{ Notestatus::status(5)->color }}"
                    wire:click.prevent="filterStatus(5)">
                    {{ Notestatus::status(5)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 5)->count() }}</span></button>
            </div>
        </div>

    </div>

    @if ($lists->count())
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
    @endif
    <dic class="card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM NOTAS SELECIONADAS PARA CONTROLE EM
                    <strong>{{ mb_strtoupper($service->service) }}</strong>
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            {{-- <h4 class="card-header fw-bold text-bg-danger">ACOMPANHAMENTO -
                {{ mb_strtoupper($service->service) }} - @if ($service->Status->count())
                    @foreach ($service->Status as $sts)
                        ({{ $sts->status }})
                    @endforeach
                @endif
            </h4> --}}
            <div class="card-header text-bg-danger">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">CONTROLE DE {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        {{-- <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button> --}}

                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary me-2 dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Ações em Massa
                            </button>
                            <ul class="dropdown-menu">
                                <li tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="left" data-bs-title="Atribuir em Massa"
                                    data-bs-content="
                                    <p>A Atribuição em Massa possibilita a modificação dos responsáveis por uma tarefa,
                                        mesmo que ela já tenha sido atribuída a outra pessoa.
                                        No entanto, essa ação só é possível se a atividade não estiver FINALIZADA ou em PAUSA.</p>
                                   ">
                                    <a class="dropdown-item" href="#" wire:click.prevent='go_att_mass'><i
                                            class="ri-user-add-line text-primary"></i> Atribuir
                                        em Massa</a>
                                </li>
                                <li tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="left" data-bs-title="Desatribuir em Massa"
                                    data-bs-content="
                                <p>A Desatribuição em Massa possibilita a remoção total responsável pela atividade liberando-a na LISTA PARA DESPACHO.
                                    No entanto, essa ação só é possível se a atividade <span class='fw-bold'>NÃO</span> estiver FINALIZADA ou em PAUSA.</p>
                                    <span class='fs-4 text-white fw-bold'>&#9632;</span> <span class='text-white fw-bold text-uppercase'>Marque a caixa no final do botão para forçar e ignorar o PAUSE.</span>
                               ">

                                    <a class="dropdown-item" href="#">
                                        <i class="ri-user-shared-line text-danger"></i>
                                        <span wire:click.prevent='go_des_att_mass'>Desatribuir em Massa</span>
                                        <input class="form-check-input border border-1 border-secondary"
                                            type="checkbox" wire:model.defer="forcar">
                                    </a>
                                </li>
                                <li tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="left" data-bs-title="Priorizar em Massa"
                                    data-bs-content="
                            <p>A priorização em massa permite priorizar um grande números de registros, porém, o texto de motivo será unico para todos eles.</p>

                           ">
                                    <a class="dropdown-item" href="#">
                                        <i class="ri-alert-fill text-danger align-middle"></i>
                                        <span wire:click.prevent='go_priority_mass'>Priorizar em Massa</span>

                                    </a>


                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="ri-alert-fill align-middle text-success"></i>
                                        <span wire:click.prevent='go_des_priority_mass'>Despriorizar em Massa</span>

                                    </a>
                                </li>
                            </ul>
                        </div>



                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                        wire:click="setSelectAll()" @checked($this->checkAllSelect($lists))>
                                </th>
                                <th scope="col" class="fw-bold text-center">Note</th>
                                <th scope="col" class="fw-bold text-center">-</th>
                                <th scope="col" class="fw-bold text-center">Grp2</th>
                                <th scope="col" class="fw-bold text-center">Rubrica</th>
                                <th scope="col" class="fw-bold text-center">Centro</th>
                                <th scope="col" class="fw-bold text-center">Municipio</th>
                                <th scope="col" class="fw-bold text-center">Zona</th>
                                <th scope="col" class="fw-bold text-center">Descrição</th>
                                <th scope="col" class="fw-bold text-center">Empresa</th>
                                <th scope="col" class="fw-bold text-center">Postes L</th>
                                <th scope="col" class="fw-bold text-center">Usuário</th>
                                <th scope="col" class="fw-bold text-center">Dias Despachado</th>
                                <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
                                <th scope="col" class="fw-bold text-center">DStatus</th>
                                <th scope="col" class="fw-bold text-center">Prazo Real</th>
                                <th scope="col" class="fw-bold text-center">Status</th>
                                <th scope="col" class="fw-bold text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $getDaysStatus = static function ($list): array {
                                    $days = $list->dt_status->diffInDays(now());

                                    if ($days > 6) {
                                        $bgColor = 'text-bg-danger';
                                    } elseif ($days < 4) {
                                        $bgColor = 'text-bg-success';
                                    } else {
                                        $bgColor = 'text-bg-warning';
                                    }

                                    return [
                                        'days' => $days,
                                        'bgColor' => $bgColor,
                                    ];
                                };
                            @endphp
                            @foreach ($lists as $list)
                                @php
                                    $dstatus = $getDaysStatus($list->note);
                                @endphp
                                <tr wire:key="line-{{ $list->id }}"
                                    class="align-middle
                                    @if ($list->block) table-primary @endif

                                    ">
                                    <td>
                                        <input class="form-check-input border border-1 border-primary" type="checkbox"
                                            value="{{ $list->id }}" wire:model.defer="selected">
                                    </td>
                                    <td
                                        class="fw-bold @if ($list->priority) text-danger fw-bold @endif @if ($list->Note->is45) bg-warning @endif">

                                        @if ($list->d5)
                                            <span class="badge text-bg-primary fs-6">{{ $list->Note->note }}
                                                (RI)
                                            </span>
                                        @else
                                            {{ $list->Note->note }}
                                            <span class="copy-text" data-value="{{ $list->Note->note }}"
                                                style="cursor: pointer;"> <i class="ri-file-copy-line"></i></span>
                                        @endif

                                        @if ($list->Note->is45)
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


                                        @if ($list->priority)
                                            <i class="ri-alert-fill text-danger align-middle"
                                                wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                style="cursor: pointer;"></i>
                                        @endif
                                    </td>
                                    <td
                                        class="fw-bold text-success text-center @if ($list->priority) text-danger fw-bold @endif">

                                        @if ($list->Note->doe)
                                            <span class="badge text-bg-success" style="font-size: 0.5rem;">DOE</span>
                                        @endif

                                        @if ($list->Note->mmgd)
                                            <span class="badge text-bg-warning" style="font-size: 0.5rem;">MMGD</span>
                                        @endif
                                    </td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->group2 }}</td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->rubrica }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->centerjob }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->lexp }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->group1 }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @if ($list->Note->rubrica == 'BT Zero')
                                            {{ $list->Note->numPedido }}
                                        @else
                                            {{ $list->Note->material }}
                                        @endif
                                    </td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @php
                                            $companyName = $list->Company->name ?? null;
                                            $companyLabel = '-';

                                            if ($companyName) {
                                                $parts = array_values(array_filter(explode(' ', trim($companyName))));
                                                $firstPart = $parts[0] ?? '';
                                                $countParts = count($parts);

                                                if ($countParts >= 3) {
                                                    $lastInitial = substr($parts[$countParts - 1], 0, 1);
                                                    $beforeLastInitial = substr($parts[$countParts - 2], 0, 1);
                                                    $companyLabel = trim($firstPart . ' ' . $beforeLastInitial . $lastInitial);
                                                } elseif ($countParts >= 2) {
                                                    $companyLabel = trim($firstPart . ' ' . substr($parts[1], 0, 2));
                                                } else {
                                                    $companyLabel = substr($firstPart, 0, 1);
                                                }
                                            }
                                        @endphp
                                        {{ $companyLabel }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">

                                        {{ $list->Note?->postes }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @php
                                            $nome = $list->User ? explode(' ', $list->User->name) : '----';
                                            if (is_array($nome)) {
                                                $nome = $nome[0] . ' ' . substr(end($nome), 0, 1);
                                            }
                                        @endphp
                                        {{ $nome }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ Carbon::now()->diffInDays(Carbon::parse($list->dispatch_at)->format('Y-m-d')) }}
                                    </td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                                    </td>
                                    <td class="fw-light text-center {{ $dstatus['bgColor'] }}" tabindex="0"
                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                        data-bs-placement="top" data-bs-title="Dias no Status"
                                        data-bs-content="
                                    <p>OBS: Os prazos para Nota não seguem com precisão, os prazos regulatórios como as OVs e deverão ser avaliados caso a caso.</p>
                                    <span class='fs-4 text-success'>&#9632;</span> < 4 NO PRAZO <br>
                                    <span class='fs-4 text-warning'>&#9632;</span> >= 4 VENCENDO <br>
                                    <span class='fs-4 text-danger'>&#9632;</span> > 6 VENCIDO <br>
                                    {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                                    ">
                                        {{ $dstatus['days'] }}
                                    </td>
                                    @php
                                        $daysleft = new DaysLeft($list->note);
                                    @endphp
                                    <td scope="col"
                                        class="text-center
                                    @if ($daysleft->getDaysLeft() < 0) text-bg-secondary
                                    @elseif($daysleft->getDaysLeft() >= 0 && $daysleft->getDaysLeft() < 6)
                                    table-danger
                                    @elseif($daysleft->getDaysLeft() >= 6 && $daysleft->getDaysLeft() < 10)
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
                                        {{ 30 - $daysleft->getDaysLeft() }}
                                    </td>
                                    {{-- <td class="fw-light text-center">
                                        <span
                                            class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                                    </td> --}}
                                    <td class="fw-light text-center">

                                        <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                            wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                            style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                    </td>
                                    <td>

                                        <x-production.action-production :production="$list" />

                                    </td>


                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif


    </dic>
    @if ($lists->count())
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
    @endif


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

    <div wire:ignore.self class="modal fade" id="add_mass_notes" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Despachar {{ $service->service }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="row">
                            {{-- <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type">
                                    <option selected>Selecione</option>
                                    <option value="1">Pilha</option>
                                    <option value="2">Individual</option>
                                </select>
                            </div> --}}
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



                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="user_s">

                                    @if ($user_l && $user_l->count())
                                        <option value="" selected>Selecione um Usuário</option>
                                        @foreach ($user_l->sortBy('name', SORT_LOCALE_STRING) as $user)
                                            <option wire:key='{{ $user->id }}' value="{{ $user->id }}">
                                                {{ $user->name }}</option>
                                        @endforeach
                                    @else
                                        <option selected>Escolha uma Empresa Primeiro</option>
                                    @endif
                                </select>
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
                                        {{-- <th scope="col">DD</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            {{-- <td>

                                                <input wire:model.defer="additionalData.{{ $index }}"
                                                    class="form-control form-control-sm" type="text"
                                                    placeholder="Informe a DD" aria-label="">


                                            </td> --}}

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



    @stack('modals')
    {{-- END MODALS --}}
    @livewire('audits.info')
    @livewire('components.status.show-status', key('show_status'))
    @livewire('production.actions.new-production', key('new-production'))

</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy',
                    `Valor "${value}" copiado para a área de transferência.`);
                // alert(`Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    </script>
@endpush
