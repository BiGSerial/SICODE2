@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="card">
        <h4 class="card-header">
            RELATÓRIO DE PRODUÇÃO
        </h4>
        <div class="card-body">
            <div class="row">
                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Serviço</label>
                    <select class="form-select form-select-sm" aria-label="Small select example" wire:model="service">
                        <option value="" selected>Todos</option>
                        @if (count($service_list))
                            @foreach ($service_list as $list)
                                <option value="{{ $list->service_id }}">{{ $list->Service->service }}</option>
                            @endforeach
                        @endif

                    </select>
                </div>
                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Mês Referência</label>
                    <select class="form-select form-select-sm" aria-label="Small select example" wire:model="monthYear">
                        <option value="" selected>Por Intervalo</option>
                        @if (count($month_list))
                            @foreach ($month_list as $list)
                                <option value="{{ $list['date'] }}">{{ $list['desc'] }}</option>
                            @endforeach
                        @endif

                    </select>
                </div>
                @if (!$monthYear)
                    <div class="mb-3 col-2">
                        <label for="exampleFormControlInput1" class="form-label">Apartir de:</label>
                        <input type="date" class="form-control form-control-sm" wire:model="dt_init">
                    </div>
                    <div class="mb-3 col-2">
                        <label for="exampleFormControlInput1" class="form-label">Até:</label>
                        <input type="date" class="form-control form-control-sm" wire:model.defer="dt_end"
                            min="{{ $dt_init }}">
                    </div>
                @endif
                @if (!Auth()->User()->contract)
                    <div class="mb-3 col-2">
                        <label for="exampleFormControlInput1" class="form-label">Empresa</label>
                        <select class="form-select form-select-sm" aria-label="Small select example"
                            wire:model="company">
                            <option value="" selected>Selecione a Empresa</option>
                            @if ($company_list)
                                @foreach ($company_list as $company)
                                    <option value="{{ $company->company_id }}">
                                        {{ explode(' ', $company->Company->name)[0] }}</option>
                                @endforeach
                            @endif

                        </select>
                    </div>
                @endif
                <div class="mb-3 col-1">
                    <div class="form-check">
                        <input class="form-check-input border-secondary" type="checkbox" wire:model="complete">
                        <label class="form-check-label" for="flexCheckDefault">
                            Incluir em Aberto
                        </label>
                    </div>
                </div>
                <div class="mb-3 col-1">
                    <div class="form-check">
                        <input class="form-check-input border-secondary" type="checkbox" wire:model="d5">
                        <label class="form-check-label" for="flexCheckDefault">
                            Incluir (RI)
                        </label>
                    </div>
                </div>
                {{-- <div class="mb-3 col-1">
                    <label for=""></label>
                    <button class="btn btn-sm btn-primary form-control mt-2" wire:click.prevent="Search">Gerar</button>
                </div> --}}
            </div>
        </div>
    </div>

    @if ($lists)
        <div class="row">
            <div class="col-1">
                <button class="btn btn-sm btn-primary mb-3" wire:click.prevent='Export'>Exportar</button>
            </div>

            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-5 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>

        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-condensed table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Usuario</th>
                                <th scope="col">Company</th>
                                <th scope="col">Serviço</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Grp2</th>
                                <th scope="col">Inicio</th>
                                <th scope="col">Fim</th>
                                <th scope="col">Parado</th>
                                <th scope="col">Postes</th>
                                <th scope="col">D5</th>
                                <th scope="col">Situação</th>
                                <th scope="col">Conclusão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr>
                                    <td>{{ isset($list->User->name) ? $list->User->name : 'Desconhecido' }}</td>
                                    <td>{{ explode(' ', $list->Company->name)[0] }}</td>
                                    <td>{{ $list->Service->service }}</td>
                                    <td>{{ $list->Note->note }}</td>
                                    <td>{{ $list->Note->group2 }}</td>
                                    <td>
                                        @if ($list->att_at)
                                            {{ date('d/m/Y H:i:s', strToTime($list->att_at)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($list->completed_at)
                                            {{ date('d/m/Y H:i:s', strToTime($list->completed_at)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $list->stopped? CarbonInterval::seconds($list->stopped)->cascade()->forHumans(['short' => true]): '-' }}
                                    </td>
                                    <td>
                                        @if ($list->postes_u)
                                            @if ($list->eo + $list->iproject != 0)
                                                {{ ($list->eo + $list->iproject) * $list->postes_u }}
                                            @else
                                                {{ $list->postes_u }}
                                            @endif
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td>
                                        {{ $list->d5 ? 'SIM' : 'NÃO' }}
                                    </td>
                                    <td>
                                        @if ($list->confirmed)
                                            Contabilizado
                                        @else
                                            Não Contabilizado
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold" style="font-size: 10px">
                                            @if ($list->Analise)
                                                {{ $list->Analise->conclusion }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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

    @endif

</div>
