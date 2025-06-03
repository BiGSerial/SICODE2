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
                <!-- Left Column (Multiselect) -->
                <div class="col-md-3">
                    <!-- Service Select -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="form-floating" style="height: 200px;">
                                <select class="form-select h-100 border border-secondary" wire:model="service" multiple
                                    id="serviceSelect">
                                    @if (count($service_list))
                                        @foreach ($service_list as $list)
                                            <option value="{{ $list->service_id }}">{{ $list->Service->service }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <label for="serviceSelect">Serviços</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Other Inputs) -->
                <div class="col-md-9">
                    <div class="row g-3">
                        <!-- Search Input -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="form-floating">
                                <input wire:model.bounce.2s="search" type="text"
                                    class="form-control border border-secondary" id="search" placeholder="Buscar">
                                <label for="search">Buscar</label>
                                <button
                                    class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                    data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                    <i class="ri-checkbox-multiple-blank-line"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Month Reference -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="form-floating">
                                <input type="month" class="form-control border border-secondary" id="monthYear"
                                    min="{{ $month_list->oldest }}" max="{{ $month_list->newest }}"
                                    wire:model="monthYear" placeholder="Mês">
                                <label for="monthYear">Mês Referência</label>
                            </div>
                        </div>

                        @if (!$monthYear)
                            <!-- Date Range -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="form-floating">
                                    <input type="date" class="form-control border border-secondary" id="dt_init"
                                        wire:model="dt_init" placeholder="Data Inicial">
                                    <label for="dt_init">A partir de</label>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="form-floating">
                                    <input type="date" class="form-control border border-secondary" id="dt_end"
                                        wire:model="dt_end" min="{{ $dt_init }}" placeholder="Data Final">
                                    <label for="dt_end">Até</label>
                                </div>
                            </div>
                        @endif

                        @if (!Auth()->User()->contract)
                            <!-- Company Select -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="company"
                                        id="companySelect">
                                        <option value="" selected>Selecione a Empresa</option>
                                        @if ($company_list)
                                            @foreach ($company_list as $company)
                                                <option value="{{ $company->company_id }}">
                                                    {{ explode(' ', $company->Company->name)[0] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <label for="companySelect">Empresa</label>
                                </div>
                            </div>
                        @endif

                        <!-- Checkboxes -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="h-100 d-flex flex-column justify-content-center">
                                <div class="form-check mb-2">
                                    <input class="form-check-input border-secondary" type="checkbox" id="complete"
                                        wire:model="complete">
                                    <label class="form-check-label" for="complete">
                                        Incluir em Aberto
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input border-secondary" type="checkbox" id="d5"
                                        wire:model="d5">
                                    <label class="form-check-label" for="d5">
                                        Incluir (RI)
                                    </label>
                                </div>
                                <button class="btn btn-danger btn-sm" wire:click="cleanAll">Limpar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($lists)
        <div class="row">
            <div class="col-2 d-flex justify-content-start">
                <button class="btn btn-sm btn-primary mb-3 me-2" wire:click.prevent='Export'>Exportar</button>
                {{-- <button class="btn btn-sm btn-primary mb-3" wire:click.prevent='Export2'>Exportar2</button> --}}
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
                                <th scope="col">DOE</th>
                                <th scope="col">Grp2</th>
                                <th scope="col">Grp5</th>
                                <th scope="col">Material</th>
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
                                    <td>{{ $list->Note->doe ? 'SIM' : 'NÃO' }}</td>
                                    <td>{{ $list->Note->group2 }}</td>
                                    <td>{{ $list->Note->group5 }}</td>
                                    <td>{{ $list->Note->material }}</td>
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

</div>
