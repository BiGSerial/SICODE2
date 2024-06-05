@php
    use Carbon\Carbon;
    use App\Custom\Viabilitiesstatus;
@endphp
@push('css')
    <style>
        .thinScroll::-webkit-scrollbar {
            width: 8px;
            /* Espessura da barra de rolagem */
        }

        .thinScroll::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 4px;
            /* Cor da barra de rolagem */
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .blinking {
            animation: blink 1s infinite;
        }
    </style>
@endpush
<div>
    <x-show-loading />
    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-8">
                <div class="row">

                    <div class="col-xxl-4 col-md-6">
                        <div class="card info-card revenue-card @if ($this->filterStatus['column'] == 'hired') border border-5 border-success @endif"
                            wire:click.prevent="setFilterStatus('hired')" style="cursor: pointer;">


                            <div class="card-body">
                                <h5 class="card-title">Contratadas <span>| {{ date('M') }}</span></h5>

                                <div class="d-flex align-items-center">
                                    <div
                                        class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bx bxs-certification"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6>{{ $countHiring }}</h6>
                                        @if ($evolutionHiring == 0)
                                            <span class="text-primary small pt-1 fw-bold"><i
                                                    class="bx bx-square fs-4 align-middle"></i>{{ $evolutionHiring }}%</span>
                                            <span class="text-muted small pt-2 ps-1">--</span>
                                        @elseif ($evolutionHiring > 0)
                                            <span class="text-success small pt-1 fw-bold"><i
                                                    class="bx bxs-up-arrow-circle fs-4 align-middle"></i>
                                                {{ $evolutionHiring }}%</span>
                                            <span class="text-muted small pt-2 ps-1">Maior ao mês anterior</span>
                                        @else
                                            <span class="text-danger small pt-1 fw-bold"><i
                                                    class="bx bxs-down-arrow-circle fs-4 align-middle"></i>
                                                {{ $evolutionHiring * -1 }}%</span>
                                            <span class="text-muted small pt-2 ps-1">Menor ao mês anterior</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-4 col-md-6">
                        <div class="card info-card customers-card @if ($this->filterStatus['column'] == 'completed') border border-5 border-success @endif"
                            wire:click.prevent="setFilterStatus('completed')" style="cursor: pointer;">


                            <div class="card-body">
                                <h5 class="card-title">Em Viabilidade <span>| {{ date('M') }}</span></h5>

                                <div class="d-flex align-items-center">
                                    <div
                                        class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bx bxs-car-mechanic text-warning"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6>{{ $countViability }}</h6>
                                        @if ($evolutionViability == 0)
                                            <span class="text-primary small pt-1 fw-bold"><i
                                                    class="bx bx-square fs-4 align-middle"></i>{{ $evolutionViability }}%</span>
                                            <span class="text-muted small pt-2 ps-1">--</span>
                                        @elseif ($evolutionViability > 0)
                                            <span class="text-success small pt-1 fw-bold"><i
                                                    class="bx bxs-up-arrow-circle fs-4 align-middle"></i>
                                                {{ $evolutionViability }}%</span>
                                            <span class="text-muted small pt-2 ps-1">Maior ao mês anterior</span>
                                        @else
                                            <span class="text-danger small pt-1 fw-bold"><i
                                                    class="bx bxs-down-arrow-circle fs-4 align-middle"></i>
                                                {{ $evolutionViability * -1 }}%</span>
                                            <span class="text-muted small pt-2 ps-1">Menor ao mês anterior</span>
                                        @endif


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-4 col-md-6">
                        <div class="card info-card sales-card @if ($this->filterResponser) border border-5 border-success @endif"
                            wire:click.prevent="setFilterStatus('responser')" style="cursor: pointer;">


                            <div class="card-body">
                                <h5 class="card-title">Aguardando Responsável <span>| {{ date('M') }}</span></h5>

                                <div class="d-flex align-items-center">
                                    <div
                                        class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bx bxs-user-detail"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6>{{ $countResponsers }}</h6>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
                <div>
                    <div class="mb-3 d-flex justify-content-end">

                        <select name="" id="" class="form-select form-select-sm ms-2"
                            style="max-width: 110px;" wire:model="perPage">
                            <option value="25">25 page</option>
                            <option value="50">50 page</option>
                            <option value="100">100 page</option>
                            <option value="200">200 page</option>
                            <option value="500">500 page</option>
                        </select>
                        <select name="" id="" class="form-select form-select-sm ms-2"
                            style="max-width: 250px;" wire:model.defer="company">
                            <option value="" selected>Selecione Empresa</option>
                            @if ($companies->count())
                                @foreach ($companies as $corp)
                                    <option value="{{ $corp->id }}">{{ $corp->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <input type="text" class="form-control form-control-sm ms-2" style="max-width: 200px;"
                            wire:model.defer="search">
                        <button class="btn btn-sm btn-primary ms-2" wire:click.prevent='searching'
                            wire:target="searching" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="Buscar"><i
                                class="bx bx-search-alt fs-4 m-0 align-middle" wire:target="searching"
                                wire:loading.remove></i>
                            <div class="spinner-border spinner-border-sm" role="status" wire:target="searching"
                                wire:loading>
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>

                        <button class="btn btn-sm btn-danger ms-2" wire:click.prevent='searchOff'
                            wire:target="searchOff" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="Limpar Busca"><i
                                class="ri-filter-off-line fs-5 m-0 align-middle" wire:target="searchOff"
                                wire:loading.remove></i>
                            <div class="spinner-border spinner-border-sm" role="status" wire:target="searchOff"
                                wire:loading>
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>

                        <button class="btn btn-sm btn-primary ms-2" wire:click.prevent='' wire:target=""
                            wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="Copiar Selecionados para área de Transferência"><i
                                class="bx bxs-copy-alt fs-4 m-0 align-middle" wire:target="" wire:loading.remove></i>
                            <div class="spinner-border spinner-border-sm" role="status" wire:target="" wire:loading>
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>

                    </div>
                    <div class="row mt-3">
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
                    <div class="table-responsive rounded">
                        @if (!$lists->count())
                            <div class="card">
                                <div class="card-body text-center">
                                    <h4>SEM RESULTADOS</h4>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-condensed table-striped table-hover">
                                    <thead class="sticky-top">

                                        <th class="text-center">Note</th>
                                        <th class="text-center">Ordem</th>
                                        <th class="text-center">Contratada</th>
                                        <th class="text-center">Enviado</th>
                                        <th class="text-center">Esperado</th>
                                        <th class="text-center">Real</th>
                                        <th class="text-center">PZE</th>
                                        <th class="text-center">Rubrica</th>
                                        <th class="text-center">Municipio</th>
                                        <th class="text-center">Empreiteira</th>
                                        <th class="text-center">Responsável</th>
                                        <th class="text-center">Status</th>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        @foreach ($lists as $list)
                                            @php
                                                $days_left = '';

                                                // Dias Restantes
                                                if ($list->type_note == 1) {
                                                    if ($list->mesalization && $list->mesalization != 'erro') {
                                                        preg_match('/\d+\/\d+/', $list->mesalization, $matches);

                                                        if (!empty($matches)) {
                                                            [$mes, $ano] = explode('/', $matches[0]);

                                                            if ($mes >= 1) {
                                                                $data = "{$ano}-{$mes}-28 23:59:59";

                                                                $hoje = Carbon::now();

                                                                $dataCarbon = Carbon::createFromFormat(
                                                                    'Y-m-d H:i:s',
                                                                    $data,
                                                                );

                                                                $days_left = $hoje->diffInDays($dataCarbon, false);
                                                            } else {
                                                                $data = "{$ano}-12-28 23:59:59";

                                                                $hoje = Carbon::now();

                                                                $dataCarbon = Carbon::createFromFormat(
                                                                    'Y-m-d H:i:s',
                                                                    $data,
                                                                );

                                                                $days_left = $hoje->diffInDays($dataCarbon, false);
                                                            }
                                                        }
                                                    }
                                                } elseif ($list->type_note == 2) {
                                                    $days_left = $list->days_left;
                                                }

                                                $color = '';

                                                if (
                                                    $list->Viabilities->first()->approved &&
                                                    !$list->Viabilities->first()->rejected &&
                                                    !$list->Viabilities->first()->tacit
                                                ) {
                                                    $color = 'green';
                                                } elseif (
                                                    !$list->Viabilities->first()->approved &&
                                                    $list->Viabilities->first()->rejected &&
                                                    !$list->Viabilities->first()->tacit
                                                ) {
                                                    $color = 'red';
                                                } elseif ($list->Viabilities->first()->tacit) {
                                                    $color = 'yellow';
                                                }

                                                $tcolor = '';

                                                if ($list->Viabilities->first()->hired) {
                                                    $tcolor = 'table-success';
                                                }

                                            @endphp
                                            <tr wire:key="viability-{{ $list->id }}"
                                                wire:dblclick.prevet="$emitTo('construction.responser.actions.responserinfo', 'getInfoResponse', {{ $list }})"
                                                style="cursor: pointer; border-left: 8px solid {{ $color }};"
                                                data-bs-toggle="tooltip" data-bs-placement="right"
                                                data-bs-title="Duplo Clique para mais Opções">
                                                <td class="text-center align-middle fw-bold {{ $tcolor }}">
                                                    {{ $list->note }}</td>
                                                <td class="text-center align-middle">
                                                    @if ($list->Viabilities->count())
                                                        @foreach ($list->Viabilities as $viab)
                                                            <p class="py-0 my-0">{{ $viab->Order->ordem }}</p>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle">
                                                    {{ $list->Viabilities->first()->hired ? 'SIM' : 'NÃO' }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    {{ $list->Viabilities->count() ? date('d/m/Y', strToTime($list->Viabilities->first()->sended_at)) : '' }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    {{ Carbon::parse($list->Viabilities->first()->sended_at)->addDays(7 + $list->Viabilities->last()->Days->sum('days'))->format('d/m/Y') }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    {{ $list->Viabilities->count() && isset($list->Viabilities->first()->returned_at) ? date('d/m/Y H:i:s', strToTime($list->Viabilities->first()->returned_at)) : '' }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    {{ Carbon::now()->addDays($days_left)->format('d/m/Y') }}
                                                </td>
                                                <td class="text-center align-middle">{{ $list->rubrica }}</td>
                                                <td class="text-center align-middle">{{ $list->lexp }}</td>
                                                <td class="text-center align-middle">
                                                    @php

                                                        $name = '';

                                                        if (
                                                            $list->Viabilities->count() &&
                                                            isset($list->Viabilities->first()->Engineer->name)
                                                        ) {
                                                            $nameParts = preg_split(
                                                                '/\s+/',
                                                                $list->Viabilities->first()->Engineer->name,
                                                            );
                                                            $name = $nameParts[0] . ' ' . end($nameParts);
                                                        }

                                                    @endphp
                                                    {{ $list->Viabilities->count() && isset($list->Viabilities->first()->Company->name)
                                                        ? $list->Viabilities->first()->Company->name
                                                        : '' }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    {{ $name }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span
                                                        class="badge {{ Viabilitiesstatus::status($list->Viabilities->first()->status)->colorbg }}">{{ Viabilitiesstatus::status($list->Viabilities->first()->status)->status }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mt-3">
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
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card info-card sales-card" wire:poll.60s>


                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="align-middle">
                                <h5 class="card-title">Aguardando Responsável <span>| {{ date('M') }}</span></h5>
                            </div>

                            <div class="align-middle my-3">
                                <select class="form-select form-select-sm align-middle border-secondary"
                                    aria-label="Responsible Select" wire:model="responser">
                                    <option value="" selected>Todos</option>
                                    @if ($responsers)
                                        @foreach ($responsers as $response)
                                            <option value="{{ $response->id }}">{{ $response->name }}</option>
                                        @endforeach
                                    @endif

                                </select>
                            </div>
                        </div>


                        @if (!$listResponsers->count())
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="text-center">SEM REGISTROS</h4>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive rounded overflow-auto thinScroll" style="max-height: 315px">
                                <table class="table table-condensed table-striped table-hover">
                                    <thead class="sticky-top">
                                        <tr class="table-primary">
                                            <th class="text-center"></th>
                                            <th class="text-center">Note</th>
                                            <th class="text-center">Empreiteira</th>
                                            <th class="text-center">Responsável</th>
                                            <th class="text-center">Tempo</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">

                                        @foreach ($listResponsers as $responser)
                                            @php

                                                $name = '';
                                                if (
                                                    $responser->Viabilities->count() &&
                                                    isset($responser->Viabilities->first()->Engineer->name)
                                                ) {
                                                    $nameParts = preg_split(
                                                        '/\s+/',
                                                        $responser->Viabilities->first()->Engineer->name,
                                                    );
                                                    $name = $nameParts[0] . ' ' . end($nameParts);
                                                }

                                                // 24h passada
                                                $twentyFourHours = false;

                                                if (isset($responser->Viabilities->first()->updated_at)) {
                                                    $updatedDate = Carbon::parse(
                                                        $responser->Viabilities->first()->updated_at,
                                                    );

                                                    if ($updatedDate->diffInHours(Carbon::now()) > 24) {
                                                        $twentyFourHours = true;
                                                    } else {
                                                        $twentyFourHours = false;
                                                    }
                                                }
                                                // Verifica se O Contratante precisa Respnder
                                                if (
                                                    isset($responser->Viabilities->first()->status) &&
                                                    $responser->Viabilities->first()->status == 4
                                                ) {
                                                    $answer = true;
                                                } else {
                                                    $answer = false;
                                                }

                                            @endphp
                                            <tr wire:key="responser-{{ $responser->id }}"
                                                wire:dblclick.prevent="$emitTo('construction.responser.actions.responserpartners', 'getInfoPartnerViab', {{ $responser }})"
                                                style="cursor: pointer;" data-bs-toggle="tooltip"
                                                data-bs-placement="left"
                                                data-bs-title="Duplo Clique para mais Opções">
                                                <td class="text-center align-middle">
                                                    @if ($twentyFourHours)
                                                        <i class="ri-24-hours-line text-danger blinking fs-5"></i>
                                                    @endif

                                                    @if ($answer)
                                                        <i class="ri-alert-fill text-warning blinking fs-5"></i>
                                                    @endif

                                                </td>
                                                <td class="text-center align-middle">{{ $responser->note }}</td>
                                                <td class="text-center align-middle">
                                                    {{ $responser->Viabilities->count() && isset($responser->Viabilities->first()->Company->name)
                                                        ? explode(' ', $responser->Viabilities->first()->Company->name)[0]
                                                        : '' }}
                                                </td>
                                                <td class="text-center align-middle">{{ $name }}</td>
                                                <td class="text-center align-middle">
                                                    {{ Carbon::parse($responser->Viabilities->first()->updated_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                                </td>
                                                <td class="text-center align-middle"><span
                                                        class="badge {{ Viabilitiesstatus::status($responser->Viabilities->first()->status)->colorbg }}">{{ Viabilitiesstatus::status($responser->Viabilities->first()->status)->status }}</span>
                                                </td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        @endif

                    </div>
                </div>



                @if (!Auth()->User()->engineer)
                    <div class="card info-card sales-card">


                        <div class="card-body">
                            <h5 class="card-title">Retorno Interno(RI) <span>| {{ date('M') }}</span></h5>


                            <div class="table-responsive rounded overflow-auto thinScroll" style="max-height: 315px">

                                @if (!$waitingLists->count())
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="text-center">SEM REGISTROS</h4>
                                        </div>
                                    </div>
                                @else
                                    <table class="table table-condensed table-striped">
                                        <thead class="sticky-top">
                                            <tr class="table-primary">
                                                <th class="text-center"></th>
                                                <th class="text-center">Note</th>
                                                <th class="text-center">Motivo</th>
                                                <th class="text-center">Serviço</th>
                                                <th class="text-center">tempo</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Responsável</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-group-divider">
                                            @foreach ($waitingLists as $waiting)
                                                @php
                                                    $name = '';
                                                    if (
                                                        $waiting->Reclaim->Production &&
                                                        isset($waiting->Reclaim->Production->user_id)
                                                    ) {
                                                        $nameParts = preg_split(
                                                            '/\s+/',
                                                            $waiting->Reclaim->Production->User->name,
                                                        );
                                                        $name = $nameParts[0] . ' ' . end($nameParts);
                                                    }

                                                    // Status Retorno
                                                    $status = 0;
                                                    if ($waiting->Reclaim) {
                                                        if ($waiting->Reclaim->completed) {
                                                            $status = 1;
                                                        } elseif ($waiting->Reclaim->Production) {
                                                            $status = 2;
                                                        } else {
                                                            $status = 3;
                                                        }
                                                    }

                                                    // Verifica se registro está a mais de 24h sem movimentação.
                                                    $twentyFourHours = false;

                                                    if (isset($waiting->Reclaim->updated_at)) {
                                                        $updatedDate = Carbon::parse($waiting->Reclaim->updated_at);

                                                        if ($updatedDate->diffInHours(Carbon::now()) > 24) {
                                                            $twentyFourHours = true;
                                                        } else {
                                                            $twentyFourHours = false;
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-center align-middle">
                                                        @if ($twentyFourHours)
                                                            <i class="ri-24-hours-line text-danger blinking fs-5"></i>
                                                        @endif
                                                    </td>
                                                    <td class="text-center align-middle">{{ $waiting->Note->note }}
                                                    </td>
                                                    <td class="text-center align-middle" style="font-size: 10px;">
                                                        {{ $waiting->Reclaim->category }}
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        {{ $waiting->Reclaim->Service->service }}</td>
                                                    <td class="text-center align-middle">
                                                        {{ Carbon::parse($waiting->Reclaim->updated_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        @if ($status == 1)
                                                            <span class="badge text-bg-success">Completo</span>
                                                        @elseif ($status == 2)
                                                            <span class="badge text-bg-primary">Atribuído</span>
                                                        @elseif ($status == 3)
                                                            <span class="badge text-bg-secondary">Não Atribuido</span>
                                                        @else
                                                            <span class="badge text-bg-dark">Desconhecido</span>
                                                        @endif


                                                    </td>
                                                    <td class="text-center align-middle">{{ $name }}</td>


                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif

                            </div>

                        </div>
                    </div>
                @endif


            </div>

        </div>
</div>
</section>

{{-- LIVEWIRE COMPONENTS --}}
@livewire('construction.responser.actions.responserpartners', key('responser-partner'))
@livewire('construction.responser.actions.responserinfo', key('responser-info'))
</div>
