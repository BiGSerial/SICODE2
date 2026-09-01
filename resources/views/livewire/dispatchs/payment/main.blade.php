@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
@endphp
<div class="survey-main-page payment-dispatch-page">

    @include('livewire.dispatchs.partials.list-shell-style')

    <style>
        .payment-dispatch-page .control-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.9rem;
        }

        .payment-dispatch-page .control-card {
            background: linear-gradient(160deg, #ffffff, #f8fafc);
            border: 1px solid #dbe3ef;
            border-radius: 0.9rem;
            padding: 0.85rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .payment-dispatch-page .control-card h6 {
            color: var(--sp-muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 0.65rem;
            text-transform: uppercase;
        }

        .payment-dispatch-page .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .payment-dispatch-page .quick-actions .btn {
            min-height: 42px;
            font-weight: 700;
        }

        .payment-dispatch-page .filters-row {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.8rem;
            padding: 0.75rem;
        }

        .payment-dispatch-page .scope-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            min-height: 24px;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1;
            margin: 0.08rem;
            padding: 0.35rem 0.5rem;
        }

        .payment-dispatch-page .bulk-search-modal .modal-content {
            border: 0;
            border-radius: 0.75rem;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.28);
            overflow: hidden;
        }

        .payment-dispatch-page .bulk-search-modal .modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 75%);
            color: #f8fafc;
            border: 0;
            padding: 1rem 1.25rem;
        }

        .payment-dispatch-page .bulk-search-modal .modal-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .payment-dispatch-page .bulk-search-modal textarea {
            min-height: 12rem;
            resize: vertical;
            border-color: #cbd5e1;
        }

        .payment-dispatch-page .bulk-search-warning {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #92400e;
            border-radius: 0.5rem;
            padding: 0.75rem 0.9rem;
            font-size: 0.88rem;
        }

        @media (min-width: 992px) {
            .payment-dispatch-page .control-grid {
                grid-template-columns: minmax(180px, 0.9fr) minmax(260px, 1fr) minmax(280px, 1fr) minmax(320px, 1.25fr);
            }

            .payment-dispatch-page .quick-actions {
                grid-template-columns: repeat(2, minmax(130px, 1fr));
            }
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
                <div class="control-grid">
                    <div class="control-card">
                        <h6>Paginacao</h6>
                        <div class="form-floating">
                            <select wire:model="perPage" class="form-select border border-secondary" id="paymentPerPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                            <label for="paymentPerPage">Registros por pagina</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Busca</h6>
                        <div class="position-relative">
                            <input wire:model.bounce.2s="search" type="text"
                                class="form-control border border-secondary pe-5" id="paymentSearch"
                                placeholder="Buscar">
                            <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Tipo de Nota</h6>
                        <div class="btn-group w-100" role="group" aria-label="Tipo de nota">
                            <input type="radio" class="btn-check" name="paymentTypeNote" wire:model="typeNote" value="1" id="paymentTypeNote1">
                            <label class="btn btn-outline-primary" for="paymentTypeNote1">Nota</label>
                            <input type="radio" class="btn-check" name="paymentTypeNote" wire:model="typeNote" value="2" id="paymentTypeNote2">
                            <label class="btn btn-outline-primary" for="paymentTypeNote2">OV</label>
                            <input type="radio" class="btn-check" name="paymentTypeNote" wire:model="typeNote" value="" id="paymentTypeNote3">
                            <label class="btn btn-outline-primary" for="paymentTypeNote3">Ambos</label>
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
                        @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'payments', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
                        @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'payments', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                        @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'payments', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                        @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regional', 'group_filter' => 'payments', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('regional'))
                        @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'rdMunicipio', 'filter' => 'Municipio', 'group_filter' => 'payments', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                        @livewire('components.filter.remove-all', ['group_filter' => 'payments'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>

    <div class="summary-bar">
        <div class="row align-items-center g-2">

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
                <table class="table table-sm table-condensed table-hover mb-0 main-table">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall"
                                    wire:click="setSelectAll" @checked($this->checkAllSelect($lists))>
                            </th>
                            <th class="align-middle text-center">Nota</th>
                            <th class="align-middle text-center">Tipo</th>
                            <th class="align-middle text-center">Ordem</th>
                            <th class="align-middle text-center">Escopo</th>
                            <th class="align-middle text-center">MOA</th>
                            {{-- <th class="align-middle text-center">Status</th> --}}
                            <th class="align-middle text-center">OP30</th>
                            <th class="align-middle text-center">OP40</th>
                            <th class="align-middle text-center">OP50</th>
                            <th class="align-middle text-center">CentroTrab</th>
                            <th class="align-middle text-center">Empresa</th>
                            <th class="align-middle text-center">Município</th>
                            <th class="align-middle text-center">Final OP20</th>
                            <th class="align-middle text-center">Data Informe</th>

                            <th class="align-middle text-center">Ads</th>
                            <th class="align-middle text-center">Fiscalizado</th>
                            <th class="align-middle text-center">Data Vencimento</th>
                            <th class="align-middle text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $soma = 0;
                        @endphp
                        @foreach ($lists as $list)
                            @php

                                $eval = $this->needBlock($list);
                                $block = $eval['block'];
                                $rowClass = $eval['color'];
                                $production = $eval['production'] ?? null;
                                $command = $eval['command'];
                                $reason = $eval['reason'] ?? null;
                                $stackProductionAvailable = \App\Support\SicodeRules::openCompanyStackProductionFor($list, Auth()->User(), $service->uuid);
                                $canDispatch = !$block || $command || $stackProductionAvailable;

                                if ($stackProductionAvailable) {
                                    $rowClass = '';
                                    $production = $stackProductionAvailable;
                                    $reason = 'Disponivel na pilha da empresa para atribuicao individual.';
                                }

                                $wf = $list->WorkForm;
                                $partial = !$wf ? $list->Partials->first() ?? null : null;
                                $orders = $wf
                                    ? $wf->Orders ?? collect()
                                    : ($partial
                                        ? $partial->Orders ?? collect()
                                        : collect());

                                $five = $list->FiveNote;
                                $hasD5 = (bool) $five;
                                $d5BadgeClass = '';
                                $d5Msg = '';
                                if ($hasD5) {
                                    if ($five->is_supervisioned ?? false) {
                                        $d5BadgeClass = 'text-bg-success';
                                        $d5Msg = 'D5 Fiscalizada – liberar carta';
                                    } else {
                                        $d5BadgeClass = 'text-bg-primary';
                                        $d5Msg = 'Gerar D5 e reter carta';
                                    }
                                }

                                if ($partial) {
                                    $date = $partial?->supervision_at?->addDays(5);
                                } else {
                                    $date = $list->fimLancado;
                                }

                                $date = $list->fimLancado;
                                $dateC = $date ? Carbon::parse($date) : null;

                                $statusFor = function ($order, $op) {
                                    $match = ($order->Operations ?? collect())->firstWhere('operacao', $op);
                                    return $match && isset($match->status) ? explode(' ', $match->status)[0] : '---';
                                };

                                $centerFor = function ($order) {
                                    $match = ($order->Operations ?? collect())->firstWhere('operacao', '0010');
                                    return $match && isset($match->cenTrab) ? explode(' ', $match->cenTrab)[0] : '---';
                                };

                                $finalOp20 = $orders
                                    ->flatMap(fn ($order) => $order->Operations ?? collect())
                                    ->where('operacao', '0020')
                                    ->pluck('fimReal')
                                    ->filter()
                                    ->sort()
                                    ->first();

                            @endphp
                            {{-- @dump($list->Productions) --}}

                            <tr class="align-middle text-center" wire:key="note-{{ $list->id }}">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary " type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled(!$canDispatch)>
                                </td>

                                <td class="fw-light fw-bold text-center {{ $rowClass }}">
                                    @if ($hasD5)
                                        <span class="badge {{ $d5BadgeClass }} fs-6" tabindex="0"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Nota com D5"
                                            data-bs-content="{{ $d5Msg }}">
                                            <span class="fw-bold">D5</span> {{ $list->note }}
                                        </span>
                                    @else
                                        {{ $list->note }}
                                    @endif
                                    <x-legal.note-demand-tags :note-id="$list->note_id ?? $list->id" :row-key="'dispatchs-payment-main-'.$list->id" />
                                </td>

                                <td
                                    class="fw-light fw-bold text-center  @if ($partial) text-bg-warning @else text-bg-success @endif">
                                    {{ $partial ? 'PARCIAL' : 'TOTAL' }} </td>

                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @if ($wf)
                                        @foreach ($wf->finalScopeBadges() as $scopeBadge)
                                            <span class="badge scope-badge {{ $scopeBadge['class'] }}">{{ $scopeBadge['label'] }}</span>
                                        @endforeach
                                    @elseif ($partial)
                                        <span class="badge scope-badge text-bg-secondary">Parcial</span>
                                    @else
                                        <span class="badge scope-badge text-bg-secondary">Geral</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle fw-bold {{ $rowClass }}">
                                    @if ($wf && $orders->isNotEmpty() && !$partial)
                                        {{-- @foreach ($list->WorkForm->Orders as $order)
                                            @php
                                                $soma += $order->moaberto;
                                            @endphp
                                            <p class="my-0 py-0">
                                                R$ {{ number_format($order->moaberto, 2, ',', '.') }}
                                            </p>
                                        @endforeach --}}
                                        @php
                                            $soma += $list->total_moaberto;
                                        @endphp
                                        <p class="my-0 py-0">
                                            R$ {{ number_format($list->total_moaberto, 2, ',', '.') }}
                                        </p>
                                    @elseif ($partial)
                                        @php
                                            $soma += $partial->value;
                                        @endphp
                                        <p class="my-0 py-0">
                                            R$ {{ number_format($partial->value, 2, ',', '.') }}
                                        </p>
                                    @endif

                                </td>
                                {{-- <td class="text-center align-middle">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->statusSist }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td> --}}

                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        <p class="my-0 py-0">{{ $statusFor($order, '0030') }}</p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        <p class="my-0 py-0">{{ $statusFor($order, '0040') }}</p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        <p class="my-0 py-0">{{ $statusFor($order, '0050') }}</p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        <p class="my-0 py-0">{{ $centerFor($order) }}</p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse

                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if ($wf)
                                        {{ $wf->Company ? $wf->Company->name : '---' }}
                                    @elseif ($partial)
                                        {{ $partial->Company ? $partial->Company->name : '---' }}
                                    @endif
                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->lexp }}</td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $finalOp20 ? Carbon::parse($finalOp20)->format('d/m/Y') : '---' }}
                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    @if ($wf)
                                        {{ $wf->informed_at ? $wf->informed_at->format('d/m/Y H:i:s') : $wf->created_at->format('d/m/Y H:i:s') }}
                                    @elseif ($partial)
                                        {{ $partial->created_at?->format('d/m/Y H:i:s') }}
                                    @endif
                                </td>



                                @php
                                    if ($wf?->Adsform) {
                                        $daysLeft = $wf?->Adsform
                                            ? $wf?->Adsform->created_at->diffInDays(Carbon::now(), true)
                                            : null;
                                    } elseif ($partial) {
                                        $daysLeft = $partial
                                            ? $partial->created_at->diffInDays(Carbon::now(), true)
                                            : null;
                                    } else {
                                        $daysLeft = null;
                                    }

                                    $prazoClass = '';

                                    if ($daysLeft) {
                                        if ($daysLeft && $daysLeft > 20) {
                                            $prazoClass = 'text-bg-danger';
                                        } elseif ($daysLeft && $daysLeft < 15) {
                                            $prazoClass = 'text-bg-success';
                                        } else {
                                            $prazoClass = 'text-bg-warning';
                                        }
                                    }
                                @endphp
                                <td scope="col"
                                    class="text-center text-center
                                    {{ $prazoClass ?? 'text-bg-info' }}"
                                    style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Prazo Pagamento"
                                    data-bs-content="
                            <p>A Data Corresponde a entrega da ADS <br>:</p>
                            <span class='fs-4 text-success'>&#9632;</span> > 15 DIAS PARA VENCER <br>
                            <span class='fs-4 text-warning'>&#9632;</span> <= 5 DIAS PARA VENCER <br>
                            <span class='fs-4 text-danger'>&#9632;</span> VENCIDO <br>
                            {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                                    ">
                                    @if ($wf?->Adsform)
                                        {{ $wf->Adsform->created_at?->format('d/m/Y H:i:s') }}
                                    @else
                                        ----
                                    @endif
                                </td>


                                <td scope="col"
                                    class="text-center text-center
                                   text-bg-secondary
                                "
                                    style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Prazo Pagamento"
                                    data-bs-content="
                            <p>A Data Corresponde 40 Parcial <br> para Parcial, corresponde a partir da data da fiscalização:</p>
                            <span class='fs-4 text-success'>&#9632;</span> >= 5 DIAS PARA VENCER <br>
                            <span class='fs-4 text-warning'>&#9632;</span> < 5 DIAS PARA VENCER <br>
                            <span class='fs-4 text-danger'>&#9632;</span> VENCIDO <br>
                            {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                            ">
                                    {{ $dateC ? $dateC->format('d/m/Y') : '---' }}
                                </td>

                                @php
                                    $daysLeft = new DaysLeft($list);
                                    $prazoClass = '';

                                    if ($daysLeft->getDaysLeft() < 0) {
                                        $prazoClass = 'text-bg-danger';
                                    } elseif ($daysLeft->getDaysLeft() > 15) {
                                        $prazoClass = 'text-bg-success';
                                    } else {
                                        $prazoClass = 'text-bg-warning';
                                    }
                                @endphp

                                <!-- Prioridade de estilo da célula 'Prazo Restante' -->
                                <td scope="col" class="text-center {{ $rowClass }}">
                                    {{ $daysLeft->getLastDate() }}
                                </td>

                                @php
                                    $assignee = $production?->User?->name ?: ($production?->Company?->name ?: 'DESCONHECIDO');
                                    $name = explode(' ', $assignee);
                                    $name = count($name) > 1 ? $name[0] . ' ' . end($name) : $name[0];
                                @endphp
                                <td class="fw-bold text-center {{ $rowClass }}" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="{{ $reason }}">
                                    @if ($canDispatch)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $list->id }}])"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ $stackProductionAvailable ? 'Assumir/atribuir Nota/OV da pilha da empresa' : 'Despachar nota' }}"></i>
                                        @if ($command)
                                            <p style="font-size: 11px">{{ $name }}</p>
                                        @endif
                                    @else
                                        <span style="font-size: 11px">{{ $name }}</span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-dark align-middle">
                            <td colspan="5" class="text-end">Total:</td>
                            <td class="fw-bold"> R$ {{ number_format($soma, 2, ',', '.') }}</td>
                            <td colspan="12"></td>
                        </tr>
                    </tfoot>
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

    </div>

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade bulk-search-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="advanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch"
                        wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusPayment"
                            wire:model="bulkSearchAnyStatus">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusPayment">
                            Buscar em qualquer Status
                        </label>
                    </div>

                    @if ($bulkSearchAnyStatus)
                        <div class="bulk-search-warning mt-3">
                            Confirme que deseja ignorar o filtro de Status da lista. A busca continuará respeitando as regras de contrato e acesso.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger" wire:click="clean">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">
                        <i class="ri-search-line"></i> Aplicar busca
                    </button>
                </div>
            </div>
        </div>
    </div>


    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-'.$service->uuid))


    {{-- END MODALS --}}



</div>
