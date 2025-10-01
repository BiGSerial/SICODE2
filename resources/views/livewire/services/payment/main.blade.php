@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp

<div>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        .exibir {
            animation: fadeIn 0.5s forwards;
        }

        .remover {
            animation: fadeOut 0.5s forwards;
        }
    </style>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row mb-3 justify-content-end">
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

        <div class="col-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="text"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>

        <div class="col-md-9 d-flex mb-3 justify-content-end py-4">
            <label for="search" class="form-label"> </label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>

            @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'payments', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'payments', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'payments', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'payments', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'payments', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'payments'], key('removeAll'))
        </div>



    </div>


    <div class="btn-group">
        <div class="mb-3 mx-1">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right"
                data-bs-title="Exibir Apenas Notas Nao Atribuidas"
                data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribu[ida. </p> <p> A palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
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


        <div class="mb-3 mx-1">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Exibir Apenas Notas D5"
                data-bs-content="<p>Ao clicar, apenas as notas que possuem D5 estarão visíveis. </p> <p>A palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                <button type="button" class="btn btn-warning" wire:click.prevent="filterD5()">
                    Apenas D5
                    @if ($filter_d5)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>
            </div>
        </div>


        {{-- <div class="mb-3 mx-1">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Exibir Apenas Notas MMGD"
                data-bs-content="<p>Ao clicar, Apenas as notas de MMGD estarão visíveis. </p> <p>A palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click.prevent="filterMMGD()">
                    Somente MMGD
                    @if ($assigned_mmgd)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>

            </div>
        </div> --}}
    </div>

    {{-- <ul class="nav nav-tabs mb-3 border-bottom border-light" wire:poll.60s='count'>
        <li class="nav-item">
            <a class="nav-link @if (!$partials) active @endif" aria-current="page" href="#"
                wire:click="$set('partials', false)">TOTAL @if ($count['total'])
                    <span class="badge text-bg-danger">{{ $count['total'] }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if ($partials) active @endif" href="#"
                wire:click="$set('partials', true)">PARCIAL @if ($count['partials'])
                    <span class="badge text-bg-danger">{{ $count['partials'] }}</span>
                @endif
            </a>
        </li>

    </ul> --}}

    <div class="row">

        @if (!$lists->count())
            <div class="col-6">
                {{-- @livewire('components.manualnote.manualnote', ['service' => $service->uuid]) --}}
            </div>
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
                <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }}</h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-secondary">LISTA PARA {{ mb_strtoupper($service->service) }}
                @if ($service->Status->count())
                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                        ({{ $sts->value }})
                    @endforeach
                @endif
            </h4>

            <div class="table-responsive exiobir">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th class="align-middle text-center">Nota</th>
                            <th class="align-middle text-center">Tipo</th>

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
                            @endphp

                            <tr class="align-middle text-center">
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
                                    {{ $wf?->earliest_fim_real?->format('d/m/Y') ?? '---' }}
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

                                {{-- Ação (play/ocupado) --}}
                                <td class="fw-bold text-center {{ $rowClass }}" tabindex="0"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="custom-tooltip" data-bs-title="{{ $eval['reason'] }}">
                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="to_accompany({{ $list->id }})"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Enviar para Acompanhamento"></i>
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
                            <td></td>
                            <td></td>
                            <td class="text-end">Total:</td>
                            <td class="fw-bold"> R$ {{ number_format($soma, 2, ',', '.') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
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
