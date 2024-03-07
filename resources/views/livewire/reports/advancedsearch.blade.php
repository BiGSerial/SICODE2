<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="card">
        <h4 class="card-header">
            BUSCAR NOTAS/OV AVANÇADO
        </h4>
        <div class="card-body">
            <div class="row">
                <div class="col-6 d-flex justify-content-start">
                    <div class="col-2 mb-3 me-2">
                        <label for="" class="form-label">Por Página</label>
                        <select wire:model="perPage"
                            class="form-select form-control-sm  border border-2 border-secondary">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="mb-3 col-2 me-2">
                        <label for="exampleFormControlInput1" class="form-label">Buscar</label>
                        <input class="form-control border border-2 border-secondary" type="text"
                            placeholder="Informe a Nota/OV" wire:model.defer='search'>
                    </div>

                    <div class="mb-3 col-1">
                        <label for=""></label>
                        <button class="btn btn-primary form-control mt-2" wire:click.prevent="Search">Buscar</button>
                    </div>
                </div>
                <div class="col-6 d-flex justify-content-end">
                    <div class="col-6 mt-3 d-flex justify-content-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input border border-2 border-secondary" type="radio"
                                name="note_type" wire:model="note_type" value="1">
                            <label class="form-check-label" for="inlineRadio1">Nota</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input border border-2 border-secondary" type="radio"
                                name="note_type" wire:model="note_type" value="2">
                            <label class="form-check-label" for="inlineRadio1">OV</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input border border-2 border-secondary" type="radio"
                                name="note_type" wire:model="note_type" value="">
                            <label class="form-check-label" for="inlineRadio1">Ambos</label>
                        </div>
                    </div>

                    <div class="col-6 d-flex justify-content-end">
                        <div class="btn-group mt-2 absolute-position" role="group" aria-label="Basic example"
                            x-date="{isShow: false}" wire:ignore.self>
                            <div class="dropdown mx-1 ">
                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false" @click="isShow=true">
                                    CentroTrabalho
                                    @if (count($centerJob_s))
                                        <span class="badge text-bg-light">{{ count($centerJob_s) }}</span>
                                    @endif

                                </button>


                                <div class="dropdown-menu thin-scrollbar py-2" x-show="isShow"
                                    @click.away="isShow=false" wire:ignore.self>
                                    <form wire:submit.prevent="filter_save">
                                        <div class="dropdown-item">
                                            <input type="text" class="form-control border border-2 border-secondary"
                                                wire:model.debounce.1s="search_f" placeholder="Buscar...">
                                        </div>
                                        <div class="border-top border-2 border-secondary"
                                            style="max-height: 350px; width: 200px; overflow-y: auto;">
                                            @if ($centerJob->count())
                                                @foreach ($centerJob as $job)
                                                    @if ($job->centerjob)
                                                        <div class="dropdown-item">
                                                            <input type="checkbox"
                                                                class="form-check-input border border-2 border-secondary"
                                                                wire:model.defer="centerJob_s"
                                                                wire:key="{{ $job->centerjob }}"
                                                                value="{{ $job->centerjob }}">
                                                            <label for="opcao1">{{ $job->centerjob }}</label>
                                                        </div>
                                                    @endif
                                                @endforeach

                                            @endif
                                        </div>

                                    </form>
                                </div>

                            </div>
                            <div class="dropdown mx-1 ">
                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Grupo 2
                                    @if (count($group2))
                                        <span class="badge text-bg-light">{{ count($group2) }}</span>
                                    @endif

                                </button>

                                <div class="dropdown-menu thin-scrollbar" style="max-height: 350px; overflow-y: auto;">
                                    <form wire:submit.prevent="filter_save">
                                        @if ($filtros->count())
                                            @foreach ($filtros->select('group2')->OrderBy('group2')->groupBy('group2')->get()->unique('group2') as $list)
                                                @if ($list->group2)
                                                    <div class="dropdown-item">
                                                        <input type="checkbox" class="form-check-input"
                                                            wire:model.defer="group2" wire:key="{{ $list->group2 }}"
                                                            value="{{ $list->group2 }}">
                                                        <label for="opcao1">{{ $list->group2 }}</label>
                                                    </div>
                                                @endif
                                            @endforeach

                                        @endif
                                    </form>
                                </div>
                            </div>
                            <div class="dropdown mx-1 ">
                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Status
                                    @if (count($status))
                                        <span class="badge text-bg-light">{{ count($status) }}</span>
                                    @endif

                                </button>

                                <div class="dropdown-menu thin-scrollbar"
                                    style="max-height: 350px; overflow-y: auto; ::-webkit-scrollbar { width: 5px; };">
                                    <form wire:submit.prevent="filter_save">
                                        @if ($filtros->count())
                                            @foreach ($filtros->select('nstats')->OrderBy('nstats')->groupBy('nstats')->get()->unique('nstats') as $list)
                                                @if ($list->nstats)
                                                    <div class="dropdown-item">
                                                        <input type="checkbox" class="form-check-input border-1"
                                                            wire:model.defer="status" wire:key="{{ $list->nstats }}"
                                                            value="{{ $list->nstats }}">
                                                        <label for="opcao1">{{ $list->nstats }}</label>
                                                    </div>
                                                @endif
                                            @endforeach

                                        @endif


                                    </form>
                                </div>
                            </div>

                            <div class="mx-1 ">
                                <button class="btn btn-primary" wire:click.prevent="applyFilter"><i
                                        class="ri-filter-fill"></i>
                                </button>
                            </div>
                            <div class="mx-1 "><button class="btn btn-primary" wire:click.prevent="removeFilter"><i
                                        class="ri-filter-off-fill"></i>
                                </button></div>

                        </div>
                    </div>
                </div>








            </div>
        </div>
    </div>

    <div class="col-12">
        @if ($lists->count())

            <div class="row">
                <div class="col-6">
                    {{ $lists->links() }}

                </div>
                <div class="col-6 d-flex justify-content-end align-middle">
                    <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                        {{ $lists->lastItem() }}
                        de {{ $lists->total() }}
                        registros.
                        {{-- @if ($update)
                        Ultima Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                    @endif --}}
                    </span>
                </div>
                <div class="col-1 py-2">
                    <button class="btn btn-sm btn-primary" wire:click.prevent="exportToExcel">Exportar Excel</button>
                </div>
            </div>
            <div class="card">
                <dic class="card-body">
                    <table class="table table-condensed table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th scope="col" class="fw-bold text-center">Note</th>
                                {{-- <th scope="col" class="fw-bold text-center">DOE</th>
                                <th scope="col" class="fw-bold text-center">MMGD</th> --}}
                                {{-- <th scope="col" class="fw-bold text-center">Criado Em</th> --}}
                                <th scope="col" class="fw-bold text-center">numPedido</th>
                                <th scope="col" class="fw-bold text-center">demConjunto</th>
                                <th scope="col" class="fw-bold text-center">Rubrica</th>
                                <th scope="col" class="fw-bold text-center">Municipio</th>
                                <th scope="col" class="fw-bold text-center">CentroTrabalho</th>
                                <th scope="col" class="fw-bold text-center">Grp1</th>
                                <th scope="col" class="fw-bold text-center">Grp2</th>
                                <th scope="col" class="fw-bold text-center">Grp4</th>
                                <th scope="col" class="fw-bold text-center">Grp5</th>
                                <th scope="col" class="fw-bold text-center">Postes L</th>
                                <th scope="col" class="fw-bold text-center">Status</th>
                                <th scope="col" class="fw-bold text-center">Prazo Real</th>
                                <th scope="col" class="fw-bold text-center">Situação</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            @foreach ($lists as $index => $list)
                                <tr>
                                    <td scope="col" class="text-center fw-bold">{{ ++$index }}</td>
                                    <td scope="col" class="text-end fw-bold">{{ $list->note }}</td>
                                    {{-- <td scope="col" class="text-center">{{ $list->doe ? 'S' : 'N' }}</td>
                                    <td scope="col" class="text-center">{{ $list->mmgd ? 'S' : 'N' }}</td> --}}
                                    {{-- <td scope="col" class="text-center">Criado Em</td> --}}
                                    <td scope="col" class="text-start">{{ $list->numPedido }}</td>
                                    <td scope="col" class="text-start">{{ $list->material }}</td>
                                    <td scope="col" class="text-start">{{ $list->rubrica }}</td>
                                    <td scope="col" class="text-start">{{ $list->lexp }}</td>
                                    <td scope="col" class="text-start">{{ $list->centerjob }}</td>
                                    <td scope="col" class="text-start">{{ $list->group1 }}</td>
                                    <td scope="col" class="text-start">{{ $list->group2 }}</td>
                                    <td scope="col" class="text-start">{{ $list->group4 }}</td>
                                    <td scope="col" class="text-start">{{ $list->group5 }}</td>
                                    <td scope="col" class="text-start">{{ $list->postes }}</td>
                                    <td scope="col" class="text-start">{{ $list->nstats }}</td>
                                    <td scope="col"
                                        class="text-center 
                                        @if ($list->days_left < 0) text-bg-secondary
                                        @elseif($list->days_left >= 0 && $list->days_left < 6)
                                        table-danger
                                        @elseif($list->days_left >= 6 && $list->days_left < 10)
                                            table-warning
                                        @else
                                            table-success @endif
                                    ">
                                        {{ 30 - $list->days_left }}
                                    </td>
                                    <td class="fw-light text-center">
                                        @if ($list->pze_parecer === 'Vencido')
                                            <span class="badge text-bg-danger">VENCIDO</span>
                                        @elseif ($list->pze_parecer === 'Não vencido')
                                            <span class="badge text-bg-success">EM PRAZO</span>
                                        @else
                                            <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </dic>
            </div>
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
                    {{-- @if ($update)
                        Ultima Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                    @endif --}}
                </span>
            </div>
        @endif
    </div>
</div>
