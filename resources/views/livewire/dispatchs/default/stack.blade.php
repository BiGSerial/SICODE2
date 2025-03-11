@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div>
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
                            @foreach ($user_fl as $user_f)
                                @if ($user_f->User)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="user_fs"
                                            wire:key="{{ $user_f->user_id }}" value="{{ $user_f->user_id }}">
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
                </button></div>


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
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
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
                                    <input class="form-check-input" type="checkbox" wire:model="selectall">
                                </th>
                                <th scope="col" class="fw-bold text-center">Note</th>
                                {{-- <th scope="col" class="fw-bold text-center">DD</th> --}}
                                <th scope="col" class="fw-bold text-center">MMGD</th>
                                <th scope="col" class="fw-bold text-center">Grp2</th>
                                <th scope="col" class="fw-bold text-center">Rubrica</th>
                                <th scope="col" class="fw-bold text-center">Municipio</th>
                                <th scope="col" class="fw-bold text-center">Zona</th>
                                <th scope="col" class="fw-bold text-center">Descrição</th>
                                <th scope="col" class="fw-bold text-center">Empresa</th>
                                <th scope="col" class="fw-bold text-center">Usuário</th>
                                <th scope="col" class="fw-bold text-center">Dias Despachado</th>
                                <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
                                <th scope="col" class="fw-bold text-center">Dias da Nota</th>
                                <th scope="col" class="fw-bold text-center">Status</th>
                                <th scope="col" class="fw-bold text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr
                                    class="align-middle
                                    @if ($list->block) table-primary @endif

                                    ">
                                    <td>
                                        <input class="form-check-input border border-1 border-primary" type="checkbox"
                                            value="{{ $list->id }}" wire:model.defer="selected">
                                    </td>
                                    <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->note }}
                                        <span class="copy-text" data-value="{{ $list->Note->note }}"
                                            style="cursor: pointer;"> <i class="ri-file-copy-line"></i></span>

                                        @if ($list->priority)
                                            <i class="ri-alert-fill text-danger align-middle"
                                                wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                style="cursor: pointer;"></i>
                                        @endif
                                    </td>
                                    {{-- <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @if ($list->Wpas->count())
                                            <a class="link-primary fw-bold"
                                                href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $list->Wpas()->orderBy('created_at', 'DESC')->first()->dd }}"">{{ $list->Wpas()->orderBy('created_at', 'DESC')->first()->dd }}</a>
                                        @else
                                            -----
                                        @endif

                                    </td> --}}
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->mmgd ? 'MMGD' : '' }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->group2 }}</td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->rubrica }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->lexp }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->group1 }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->material }}</td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">

                                        {{ $list->Company ? explode(' ', $list->Company->name)[0] : '-' }}</td>
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
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ Carbon::now()->diffInDays(Carbon::parse($list->Note->dt_status)->format('Y-m-d')) }}
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
                                    <td class="fw-bold fs-5">
                                        @if (!$list->block)
                                            {{-- @if (!$list->completed)
                                                <span class="d-inline-block" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                    data-bs-title="Iniciar.">
                                                    <i class="ri-play-circle-line m-0 align-middle text-success"
                                                        style="cursor: pointer;"
                                                        wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})"></i>
                                                </span>
                                                <span class="d-inline-block" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                    data-bs-title="Transferir.">
                                                    <i class="ri-exchange-fill m-0 align-middle text-primary"
                                                        style="cursor: pointer;"
                                                        wire:click.prevent="goTransferProd({{ $list->id }})"></i>
                                                </span>
                                            @endif --}}
                                            <div class="dropdown" style="position: inherit">
                                                <button class="btn btn-danger btn-sm dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-menu-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu  edp-bg-gray">
                                                    @if ($list->status == 1)
                                                        <li><a class="dropdown-item" href="#"
                                                                wire:click.prevent="get_single_note({{ $list->id }})"><i
                                                                    class="ri-user-shared-fill text-primary align-middle"></i>
                                                                Atribuir</a></li>
                                                    @else
                                                        @if (!$list->completed)
                                                            <li><a class="dropdown-item" href="#"
                                                                    wire:click.prevent="to_remove_add({{ $list->id }})"><i
                                                                        class="ri-user-received-2-line text-danger align-middle"></i>
                                                                    Desatribuir</a></li>
                                                        @endif
                                                        {{-- <li><a class="dropdown-item" href="#"><i
                                                                class="ri-exchange-line text-primary align-middle"></i>
                                                            Transferir</a></li> --}}
                                                    @endif
                                                    {{-- @livewire('production.actions.attribute', ['production' => $list->id, 'chave' => hash('sha512', $list->id)], key('attribute-' . $list->id)) --}}
                                                    @livewire('production.actions.reattribute', ['production' => $list, 'chave' => hash('sha512', $list->id)], key('reatt-' . $list->id))
                                                    @livewire('production.actions.priority', ['production' => $list, 'chave' => hash('sha512', $list->id)], key('priority-' . $list->id))
                                                    @livewire('production.actions.delete', ['production' => $list, 'chave' => hash('sha512', $list->id)], key('delete-' . $list->id))
                                                </ul>
                                            </div>
                                        @endif
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
                                        @foreach ($user_l as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
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




    {{-- END MODALS --}}
    @livewire('components.status.show-status', key('show_status_note'))

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
