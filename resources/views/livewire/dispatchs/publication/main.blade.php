@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div>

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
                    Grupo1
                    @if (count($group1_s))
                        <span class="badge text-bg-light">{{ count($group1_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($group1_l) && $group1_l && $group1_l->count() > 0)
                            @foreach ($group1_l as $group)
                                @if (trim($group))
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="group1_s"
                                            wire:key="{{ $group }}" value="{{ $group }}">
                                        <label for="opcao1">{{ $group }}</label>
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
                    Grupo2
                    @if (count($group2_s))
                        <span class="badge text-bg-light">{{ count($group2_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($group2_l) && $group2_l && $group2_l->count() > 0)
                            @foreach ($group2_l as $group)
                                @if (trim($group))
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="group2_s"
                                            wire:key="{{ $group }}" value="{{ $group }}">
                                        <label for="opcao1">{{ $group }}</label>
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
                    Grupo5
                    @if (count($group5_s))
                        <span class="badge text-bg-light">{{ count($group5_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($group5_l) && $group5_l->count() > 0)
                            @foreach ($group5_l as $group)
                                @if (trim($group))
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="group5_s"
                                            wire:key="{{ $group }}" value="{{ $group }}">
                                        <label for="opcao1">{{ $group }}</label>
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
                {{-- @dump($city_l) --}}
                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($city_l) && $city_l && $city_l->count() > 0)
                            @foreach ($city_l as $city)
                                @if ($city->cidade)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="city_s"
                                            wire:key="{{ $city->rdMunicipio }}" value="{{ $city->cidade }}">
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

    <div class="card">

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

            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall">
                            </th>
                            {{-- @can('management')
                                    <th scope="col" class="fw-bold">Note</th>
                                @endcan --}}
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th class="align-middle text-center">Empresa</th>
                            <th class="align-middle text-center">Município</th>
                            <th class="align-middle text-center">Data Execução</th>
                            <th class="align-middle text-center">Data Informe</th>
                            <th class="align-middle text-center">Dias Pilha</th>
                            <th scope="col" class="fw-bold text-center">Retorno</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th class="align-middle text-center">Prazo Restante</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $block = 0;

                                if ($production = $this->hasPublication($list)) {
                                    if ($production->confirmed) {
                                        $block = 4;
                                    } elseif ($production->completed) {
                                        $block = 3;
                                    } elseif ($production->status == 1) {
                                        $block = 2;
                                    } else {
                                        $block = 1;
                                    }
                                }

                                

                                $days = $list->WorkForm
                                    ? Carbon::parse($list->WorkForm->created_at)->diffInDays(Carbon::now(), false)
                                    : 0;

                                $daysLeft = 3 - $days;
                            @endphp



                            <tr
                                class="align-middle text-center
                                   @if ($block == 4) table-danger
                                      @elseif ($block == 3)
                                       table-success
                                        @elseif ($block == 2)
                                       table-warning
                                        @elseif ($block == 1)
                                       table-primary @endif
                                    ">
                                <td>
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled($block)>
                                </td>

                                <td class="fw-bold copy-text" data-value="{{ $list->note }}">
                                    {{ $list->note }}
                                </td>
                                <td class="fw-light">
                                    {{ $list->WorkForm ? $list->WorkForm->Company->name : '---' }}
                                </td>

                                <td class="fw-light">{{ $list->lexp }}</td>

                                <td class="fw-light">
                                    {{ $list->WorkForm ? date('d/m/Y', strToTime($list->WorkForm->date)) : '---' }}
                                </td>
                                <td class="fw-light">
                                    {{ $list->WorkForm ? date('d/m/Y H:i:s', strToTime($list->WorkForm->created_at)) : '---' }}
                                </td>

                                <td scope="col" class="text-center">
                                    {{ $list->WorkForm ? Carbon::parse($list->WorkForm->created_at)->diffInDays(Carbon::now(), false) : '---' }}
                                </td>
                                <td class="fw-light text-center" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Desenhos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($production)
                                        <span
                                            class="badge text-bg-dark">{{ $this->hasPublicationCount($list) }}</span><br>
                                        @php
                                            $name = explode(' ', $production->User->name);

                                            if (is_array($name)) {
                                                $name = $name[0] . ' ' . end($name);
                                            } else {
                                                $name = 'DESCONHECIDO';
                                            }
                                        @endphp
                                        {{ $name }}
                                    @else
                                        --
                                    @endif

                                </td>

                                @if ($list->type_note != 1)
                                    <td class="fw-light text-center">{{ $list->nstats }} </td>
                                @else
                                    <td class="fw-light text-center">{{ $list->centerjob }} <span class="text-danger"
                                            style="font-size: 8px;">{{ $list->nstats }}</span></td>
                                @endif
                                <td scope="col"
                                    class="text-center
                                            @if ($daysLeft < 0) text-bg-secondary
                                            @elseif($daysLeft >= 0 && $daysLeft < 1)
                                            table-danger
                                            @elseif($daysLeft >= 1 && $daysLeft < 2)
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
                                    {{ $daysLeft }}
                                </td>


                                <td class="fw-bold text-center">
                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="get_single_note({{ $list->id }})"></i>
                                    @else
                                        <span
                                            style="font-size: 11px">{{ explode(' ', $production->Company->name)[0] }}</span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
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
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type">
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
                                        <input wire:model.bounce.500ms="search_user"
                                            class="form-control form-control-sm" type="text"
                                            placeholder="Digite um nome" aria-label="">
                                    </div>
                                    <div class="col">
                                        <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                        <select class="form-select form-select-sm" aria-label=""
                                            wire:model="user_s">

                                            @if ($user_l && $user_l->count())

                                                <option value="" selected>Selecione um Usuário</option>
                                                @foreach ($user_l->sortBy('name', SORT_LOCALE_STRING) as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option selected>Escolha uma Empresa Primeiro</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                {{-- <div class="mb-3 ">
                                    <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                    <select class="form-select form-select-sm" aria-label="" wire:model="user_s">

                                        @if ($user_l && $user_l->count())
                                            <option value="" selected>Selecione um Usuário</option>
                                            @foreach ($user_l as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        @else
                                            <option selected>Escolha uma Empresa Primeiro</option>
                                        @endif
                                    </select>
                                </div> --}}


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
                                                @if ($this->type === '2')
                                                    <input wire:model.defer="additionalData.{{ $index }}"
                                                        class="form-control form-control-sm" type="text"
                                                        placeholder="Informe a DD" aria-label="">
                                                @endif

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
                    <button class="btn-sm btn btn-primary" wire:click.prevent="confirm_att">Despachar</button>
                </div>
            </div>
        </div>
    </div>




    {{-- END MODALS --}}

</div>
