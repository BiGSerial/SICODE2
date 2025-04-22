@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
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
            <th class="align-middle text-center">Data Execução</th>
            <th class="align-middle text-center">Data Informe</th>

            <th class="align-middle text-center">Prazo Pagamento</th>
            <th class="align-middle text-center">Data Vencimento</th>

            <th class="align-middle text-center">Usuario</th>
            <th class="align-middle text-center">Despachado em</th>
            <th class="align-middle text-center">Atribuído em</th>
            <th class="align-middle text-center">Finalizado em</th>
            <th class="align-middle text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @php
            $soma = 0;
        @endphp
        @foreach ($lists as $list)
            @php
                $block = false;
                $command = false;

                // if ($production = $this->hasProduction($list)) {
                //     $block = true;

                //     if ($production->confirmed) {
                //         $command = true;
                //     } else {
                //         $command = false;
                //     }
                // }

                $production = $list->Productions->where('service_id', $service)->last();

                $name = '---';
                $status = '---';

                if ($production) {
                    $name = $production->User?->name ?? 'DESCONHECIDO';
                    $status = Notestatus::status($production->status)->status;
                }

                if ($partial = $list->Partials ? $list->Partials->last() : null) {
                    if (!($partial->allow && $partial->supervision && !$partial->payment)) {
                        $partial = null;
                    }
                } else {
                    $partial = null;
                }

                $rowClass = '';

                if ($block) {
                    if ($production->confirmed) {
                        $rowClass = 'table-danger';
                    } elseif ($production->status == 1) {
                        $rowClass = 'table-danger';
                    } elseif ($production->status == 2) {
                        $rowClass = 'table-primary';
                    } elseif ($production->status == 5) {
                        $rowClass = 'table-success';
                    } else {
                        $rowClass = 'table-primary';
                    }
                }

                $daysLeft = Carbon::parse($list->fimLancado)
                    ->startOfDay()
                    ->diffInDays(Carbon::now()->startOfDay());

            @endphp
            {{-- @dump($list->Productions) --}}

            <tr class="align-middle text-center
                ">


                <td class="fw-light fw-bold text-center {{ $rowClass }}">{{ $list->note }}
                </td>

                <td
                    class="fw-light fw-bold text-center  @if ($partial) text-bg-warning @else text-bg-success @endif">
                    {{ $partial ? 'PARCIAL' : 'TOTAL' }} </td>

                <td class="text-center align-middle {{ $rowClass }}">
                    @if ($list->WorkForm)
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->ordem }}
                            </p>
                        @endforeach
                    @elseif ($partial)
                        @foreach ($partial->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->ordem }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle fw-bold {{ $rowClass }}">
                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty() && !$partial)
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
                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0030')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0030')->first()->status)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle {{ $rowClass }}">
                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0040')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle {{ $rowClass }}">
                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->isNotEmpty() && isset($order->Operations->where('operacao', '0050')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0050')->first()->status)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle {{ $rowClass }}">
                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->isNotEmpty() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                            </p>
                        @endforeach
                    @elseif ($partial)
                        @foreach ($partial->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>

                <td class="fw-light text-center {{ $rowClass }}">
                    @if ($list->WorkForm)
                        {{ $list->WorkForm->Company ? $list->WorkForm->Company->name : '---' }}
                    @elseif ($partial)
                        {{ $list->Partials->last()->Company ? $list->Partials->last()->Company->name : '---' }}
                    @endif
                </td>

                <td class="fw-light text-center {{ $rowClass }}">{{ $list->lexp }}</td>

                <td class="fw-light text-center {{ $rowClass }}">
                    {{ $list->WorkForm ? date('d/m/Y', strToTime($list->WorkForm->date)) : '---' }}
                </td>
                <td class="fw-light {{ $rowClass }}">
                    @if ($list->WorkForm)
                        {{ $list->WorkForm && $list->WorkForm->informed_at ? $list->WorkForm->informed_at->format('d/m/Y H:i:s') : $list->WorkForm->created_at->format('d/m/Y H:i:s') }}
                    @elseif ($list->Partials->isNotEmpty())
                        {{ $list->Partials->last()->created_at->format('d/m/Y H:i:s') }}
                    @endif
                </td>

                <td scope="col"
                    class="text-center text-center
                   @if ($daysLeft <= 2) text-bg-success
                @elseif($daysLeft > 5)
                    text-bg-danger
                @else
                text-bg-warning @endif
                "
                    style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                    data-bs-trigger="hover focus" data-bs-placement="top" data-bs-title="Prazo Pagamento"
                    data-bs-content="
            <p>A Data Corresponde 40 Parcial</p>
            <span class='fs-4 text-success'>&#9632;</span> <= 2 DIAS PARA VENCER <br>
            <span class='fs-4 text-warning'>&#9632;</span> <= 5 DIAS PARA VENCER <br>
            <span class='fs-4 text-danger'>&#9632;</span> > 5 DIAS VENCIDO <br>
            {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
            ">
                    {{ $list->fimLancado ? date('d/m/Y', strToTime($list->fimLancado)) : '' }}
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


                <td class="fw-bold text-center {{ $rowClass }}">
                    {{ $name }}
                </td>

                <td class="fw-bold text-center {{ $rowClass }}">
                    {{ $production ? $production->dispatch_at?->format('d/m/Y H:i:s') : '---' }}
                </td>
                <td class="fw-bold text-center {{ $rowClass }}">
                    {{ $production ? $production->att_at?->format('d/m/Y H:i:s') : '---' }}
                </td>
                <td class="fw-bold text-center {{ $rowClass }}">
                    {{ $production ? $production->completed_at?->format('d/m/Y H:i:s') : '---' }}
                </td>

                <td class="fw-bold text-center {{ $rowClass }}">
                    {{ $status }}
                </td>

            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="table-dark align-middle">
            <td></td>
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
