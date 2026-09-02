@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp

<div class="survey-main-page payment-services-page">

    @include('livewire.dispatchs.partials.list-shell-style')

    <style>
        .payment-services-page .control-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.9rem;
        }

        .payment-services-page .control-card {
            background: linear-gradient(160deg, #ffffff, #f8fafc);
            border: 1px solid #dbe3ef;
            border-radius: 0.9rem;
            padding: 0.85rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .payment-services-page .control-card h6 {
            color: var(--sp-muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 0.65rem;
            text-transform: uppercase;
        }

        .payment-services-page .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .payment-services-page .quick-actions .btn {
            min-height: 42px;
            font-weight: 700;
        }

        .payment-services-page .filters-row {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.8rem;
            padding: 0.75rem;
        }

        .payment-services-page .scope-badge {
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

        .payment-services-page .bulk-search-modal .modal-content {
            border: 0;
            border-radius: 0.75rem;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.28);
            overflow: hidden;
        }

        .payment-services-page .bulk-search-modal .modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 75%);
            color: #f8fafc;
            border: 0;
            padding: 1rem 1.25rem;
        }

        .payment-services-page .bulk-search-modal .modal-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .payment-services-page .bulk-search-modal textarea {
            min-height: 12rem;
            resize: vertical;
            border-color: #cbd5e1;
        }

        .payment-services-page .bulk-search-warning {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #92400e;
            border-radius: 0.5rem;
            padding: 0.75rem 0.9rem;
            font-size: 0.88rem;
        }

        @media (min-width: 992px) {
            .payment-services-page .control-grid {
                grid-template-columns: minmax(180px, 0.9fr) minmax(260px, 1fr) minmax(280px, 1fr) minmax(320px, 1.25fr);
            }

            .payment-services-page .quick-actions {
                grid-template-columns: repeat(2, minmax(130px, 1fr));
            }
        }
    </style>

    <x-show-loading />

    <x-showselected :count="count($selected)" />

    <div class="container-fluid px-3 px-lg-4">
        <div class="survey-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>LISTA PARA {{ mb_strtoupper($service->service) }}</h2>
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
                            <select wire:model="perPage" class="form-select border border-secondary" id="servicePaymentPerPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                            <label for="servicePaymentPerPage">Registros por pagina</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Busca</h6>
                        <div class="position-relative">
                            <input wire:model.bounce.2s="search" type="text"
                                class="form-control border border-secondary pe-5" id="servicePaymentSearch" placeholder="Buscar">
                            <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Tipo de Nota</h6>
                        <div class="btn-group w-100" role="group" aria-label="Tipo de nota">
                            <input type="radio" class="btn-check" name="servicePaymentTypeNote" wire:model="typeNote" value="1" id="servicePaymentTypeNote1">
                            <label class="btn btn-outline-primary" for="servicePaymentTypeNote1">Nota</label>
                            <input type="radio" class="btn-check" name="servicePaymentTypeNote" wire:model="typeNote" value="2" id="servicePaymentTypeNote2">
                            <label class="btn btn-outline-primary" for="servicePaymentTypeNote2">OV</label>
                            <input type="radio" class="btn-check" name="servicePaymentTypeNote" wire:model="typeNote" value="" id="servicePaymentTypeNote3">
                            <label class="btn btn-outline-primary" for="servicePaymentTypeNote3">Ambos</label>
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

                            <button class="btn btn-outline-success" wire:click.prevent="go_att_mass" @disabled(!count($selected))>
                                <i class="ri-user-add-line"></i> Atribuir Selecionadas ({{ count($selected) }})
                            </button>

                            <button class="btn btn-primary" wire:click.prevent="export_excel"
                                wire:loading.attr="disabled" wire:target="export_excel">
                                <span wire:loading.remove wire:target="export_excel">
                                    <i class="ri-file-excel-2-line"></i> Exportar
                                </span>
                                <span wire:loading wire:target="export_excel">Exportando...</span>
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
                        @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'payments', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
                        @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'payments', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                        @livewire('components.filter.remove-all', ['group_filter' => 'payments'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="row align-items-center g-2">
                @if (!$lists->count())
                    <div class="col-6"></div>
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
                <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }}</h4>
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
                                <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                    wire:click="setSelectAllFiltered" @checked($this->checkAllSelect())>
                            </th>
                            <th class="align-middle text-center">Nota</th>
                            <th class="align-middle text-center">Tipo</th>
                            <th class="align-middle text-center">Escopo</th>

                            <th class="align-middle text-center">Ordem</th>
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
                            <th class="align-middle text-center">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $soma = 0;

                            if (!function_exists('FiveStatus')) {
                                function FiveStatus($list): object
                                {
                                    $object = (object) [
                                        'exists' => false,
                                        'bgColor' => '',
                                        'message' => '',
                                    ];

                                    if ($five = $list->fiveNote) {
                                        if (!$five->is_supervisioned) {
                                            $object->exists = true;
                                            $object->bgColor = 'text-bg-primary';
                                            $object->message = 'Gerar D5 e reter carta';
                                        } else {
                                            $object->exists = true;
                                            $object->bgColor = 'text-bg-success';
                                            $object->message = 'D5 Fiscalizada Liberar carta';
                                        }
                                    }

                                    return (object) $object;
                                }
                            }
                        @endphp
                        @foreach ($lists as $list)
                            @php
                                // 1) Avaliação (já usa dados em memória por causa do load() no componente)
                                $eval = $this->needBlock($list);
                                $block = $eval['block'];
                                $rowClass = $eval['color'];
                                $production = $eval['production'] ?? null;

                                // 2) Derivados locais: WorkForm, última parcial válida (já limitada a 1), e conjunto de pedidos
                                $wf = $list->WorkForm;
                                $partial = !$wf ? $list->Partials->first() ?? null : null;

                                // Escolhe o conjunto de orders em UM lugar só (evita vários ifs abaixo)
                                $orders = $wf
                                    ? $wf->Orders ?? collect()
                                    : ($partial
                                        ? $partial->Orders ?? collect()
                                        : collect());

                                // 3) FiveNote (só pra badge D5)
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

                                // 4) Data de referência já veio da query como 'fimLancado' (evita recomputar)
                                $date = $list->fimLancado;
                                $dateC = $date ? \Carbon\Carbon::parse($date) : null;

                                // 5) Prazo geral (mantive tua lógica, só evitando repetir parse)
                                if ($dateC) {
                                    $diff = now()->startOfDay()->diffInDays($dateC->startOfDay(), true);
                                    $daysLeftSigned = $dateC->isBefore(now()->startOfDay()) ? -$diff : $diff;
                                } else {
                                    $daysLeftSigned = null;
                                }

                                // 6) Helperzinho local para status por operação (evita where() repetido na Collection)
                                $statusFor = function ($order, $op) {
                                    $ops = $order->Operations ?? collect();
                                    $match = $ops->firstWhere('operacao', $op);
                                    return $match && isset($match->status) ? explode(' ', $match->status)[0] : '---';
                                };

                                $finalOp20 = $orders
                                    ->flatMap(fn ($order) => $order->Operations ?? collect())
                                    ->where('operacao', '0020')
                                    ->pluck('fimReal')
                                    ->filter()
                                    ->sort()
                                    ->first();
                            @endphp

                            <tr class="align-middle text-center" wire:key="service-payment-note-{{ $list->id }}">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected">
                                </td>

                                {{-- Nota + D5 badge --}}
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
                                </td>

                                {{-- Tipo: PARCIAL/TOTAL (sem revalidar flags – já veio filtrada) --}}
                                <td
                                    class="fw-light fw-bold text-center {{ $partial ? 'text-bg-warning' : 'text-bg-success' }}">
                                    {{ $partial ? 'PARCIAL' : 'TOTAL' }}
                                </td>

                                <td class="fw-light fw-bold text-center {{ $rowClass }}">
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

                                {{-- Ordem --}}
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse
                                </td>

                                {{-- MOA (usar total_moaberto pra WF; value pra parcial) --}}
                                <td class="text-center align-middle fw-bold {{ $rowClass }}">
                                    @php
                                        if ($wf && !$partial) {
                                            $soma += $list->total_moaberto;
                                            $moa = $list->total_moaberto;
                                        } elseif ($partial) {
                                            $soma += $partial->value ?? 0;
                                            $moa = $partial->value ?? 0;
                                        } else {
                                            $moa = 0;
                                        }
                                    @endphp
                                    R$ {{ number_format($moa, 2, ',', '.') }}
                                </td>

                                {{-- OP30 / OP40 / OP50 --}}
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

                                {{-- CentroTrab (OP 0010) --}}
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @forelse ($orders as $order)
                                        @php $op10 = $order->Operations?->firstWhere('operacao','0010'); @endphp
                                        <p class="my-0 py-0">
                                            {{ $op10 && isset($op10->cenTrab) ? explode(' ', $op10->cenTrab)[0] : '---' }}
                                        </p>
                                    @empty
                                        <p class="my-0 py-0">---</p>
                                    @endforelse
                                </td>

                                {{-- Empresa --}}
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $wf ? $wf->Company->name ?? '---' : $partial?->Company?->name ?? '---' }}
                                </td>

                                {{-- Município --}}
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->lexp }}</td>

                                {{-- Final OP20 --}}
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $finalOp20 ? Carbon::parse($finalOp20)->format('d/m/Y') : '---' }}
                                </td>

                                @php
                                    $dtInf = $wf?->informed_at ?? ($wf?->created_at ?? $partial?->created_at);
                                @endphp
                                <td class="fw-light {{ $rowClass }}">
                                    {{ $dtInf?->format('d/m/Y H:i:s') ?? '---' }}
                                </td>

                                {{-- ADS (mantido como no teu, sem consultas extras) --}}
                                @php
                                    if ($wf?->Adsform) {
                                        $adsDiff = $wf->Adsform->created_at->diffInDays(now(), true);
                                    } elseif ($partial) {
                                        $adsDiff = $partial->created_at?->diffInDays(now(), true);
                                    } else {
                                        $adsDiff = null;
                                    }

                                    $prazoClass = '';
                                    if (!is_null($adsDiff)) {
                                        $prazoClass =
                                            $adsDiff > 20
                                                ? 'text-bg-danger'
                                                : ($adsDiff < 15
                                                    ? 'text-bg-success'
                                                    : 'text-bg-warning');
                                    }
                                @endphp
                                <td class="text-center {{ $prazoClass ?: 'text-bg-info' }}"
                                    style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Prazo Pagamento"
                                    data-bs-content="
                <p>A Data Corresponde a entrega da ADS:</p>
                <span class='fs-4 text-success'>&#9632;</span> > 15 DIAS PARA VENCER <br>
                <span class='fs-4 text-warning'>&#9632;</span> <= 5 DIAS PARA VENCER <br>
                <span class='fs-4 text-danger'>&#9632;</span> VENCIDO <br>
            ">
                                    {{ $wf?->Adsform?->created_at?->format('d/m/Y H:i:s') ?? '----' }}
                                </td>

                                {{-- Data Vencimento (fimLancado) --}}
                                <td class="text-center text-bg-secondary" style="background-color: inherit;"
                                    tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="top" data-bs-title="Prazo Pagamento"
                                    data-bs-content="
                <p>A Data Corresponde 40 Parcial (a partir da fiscalização):</p>
                <span class='fs-4 text-success'>&#9632;</span> >= 5 DIAS PARA VENCER <br>
                <span class='fs-4 text-warning'>&#9632;</span> < 5 DIAS PARA VENCER <br>
                <span class='fs-4 text-danger'>&#9632;</span> VENCIDO <br>
            ">
                                    {{ $dateC ? $dateC->format('d/m/Y') : '---' }}
                                </td>

                                {{-- Prazo Restante (usa teu helper DaysLeft sem hits extras) --}}
                                @php
                                    $dl = new \App\Helpers\DaysLeft($list);
                                    $prazoClass2 =
                                        $dl->getDaysLeft() < 0
                                            ? 'text-bg-danger'
                                            : ($dl->getDaysLeft() > 15
                                                ? 'text-bg-success'
                                                : 'text-bg-warning');
                                @endphp
                                <td class="text-center {{ $rowClass }}">
                                    {{ $dl->getLastDate() }}
                                </td>

                                {{-- Ação (auto atribuição — sweetalert2 de confirmação, sem modal) --}}
                                <td class="fw-bold text-center {{ $rowClass }}" tabindex="0"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="custom-tooltip" data-bs-title="{{ $eval['reason'] }}">
                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="to_accompany({{ $list->id }})"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Atribuir esta Nota/OV para você"></i>
                                    @else
                                        @php
                                            if (isset($production?->User?->name)) {
                                                $nameParts = explode(' ', $production->User->name);
                                                $name = $nameParts[0] . ' ' . end($nameParts);
                                            } elseif ($partial && ($partial->deny ?? false) && !$wf) {
                                                $name = 'PARCIAL REJEITADA';
                                            } else {
                                                $name = 'DESCONHECIDO';
                                            }
                                        @endphp
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
    <div class="container-fluid px-3 px-lg-4">
        <div class="row my-3 align-items-center">
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
    <div wire:ignore.self class="modal fade bulk-search-modal" id="buscar_multi" tabindex="-1" aria-labelledby="serviceBuscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceBuscarMultiLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="serviceAdvanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="serviceAdvanceSearch"
                        wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusServicePayment"
                            wire:model="bulkSearchAnyStatus">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusServicePayment">
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
</div>
