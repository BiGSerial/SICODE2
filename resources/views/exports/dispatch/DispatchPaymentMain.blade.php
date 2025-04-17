@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<table class="table table-sm table-striped table-condensed">
    <thead class="table-dark">
        <tr>

            <th class="align-middle text-center">Nota</th>
            <th class="align-middle text-center">Ordem</th>
            <th class="align-middle text-center">MOA</th>
            <th class="align-middle text-center">OP30</th>
            <th class="align-middle text-center">OP40</th>
            <th class="align-middle text-center">OP50</th>
            <th class="align-middle text-center">CentroTrab</th>
            <th class="align-middle text-center">Empresa</th>
            <th class="align-middle text-center">Município</th>
            <th class="align-middle text-center">Data Execução</th>
            <th class="align-middle text-center">Data Informe</th>
            <th scope="col" class="fw-bold text-center">Retorno</th>
            <th scope="col" class="fw-bold text-center">Status</th>
            <th class="align-middle text-center">Prazo Pagamento</th>
            <th class="align-middle text-center">Prazo Obra</th>

        </tr>
    </thead>
    <tbody>
        @php
            $soma = 0;
        @endphp
        @foreach ($lists as $list)
            @php
                $block = 0;
                $exception = false;
                $production = '';
                $user = [];

                $production = $list->Productions->where('service_id', $service);

                if ($production->where('completed', false)->where('confirmed', false)->count()) {
                    $block = 1;

                    $lastProduction = $production->where('completed', false)->where('confirmed', false)->last();

                    $lastName = $lastProduction->User->name ?? 'Desconhecido';
                    $company = $lastProduction->Company->name ?? 'Desconhecido';
                    $status = $lastProduction->status ?? 'Desconhecido';

                    $count = $production->count();

                    $lastName = explode(' ', $lastName);
                    $lastName = count($lastName) > 1 ? $lastName[0] . ' ' . end($lastName) : $lastName[0];

                    $company = explode(' ', $company)[0];

                    $user = [
                        'lastUser' => $lastName,
                        'countProd' => $count,
                        'status' => $status,
                        'company' => $company,
                    ];
                } elseif ($production->where('completed', true)->where('confirmed', false)->count()) {
                    $block = 2;

                    $lastProduction = $production->where('completed', true)->where('confirmed', false)->last();

                    $lastName = $lastProduction->User->name ?? 'Desconhecido';
                    $company = $lastProduction->Company->name ?? 'Desconhecido';
                    $status = $lastProduction->status ?? 'Desconhecido';

                    $count = $production->count();

                    $lastName = explode(' ', $lastName);
                    $lastName = $lastName[0] . ' ' . end($lastName);

                    $company = explode(' ', $company)[0];

                    $user = [
                        'lastUser' => $lastName,
                        'countProd' => $count,
                        'status' => $status,
                        'company' => $company,
                    ];
                } elseif ($production->where('completed', true)->where('confirmed', true)->count()) {
                    if (
                        $production
                            ->where('completed', true)
                            ->where('confirmed', true)
                            ->where('dt_note', $list->dt_status)
                            ->where('noinconsistency', false)
                            ->where('type_note', 2)
                            ->count()
                    ) {
                        $block = 3;

                        $lastProduction = $production
                            ->where('completed', true)
                            ->where('confirmed', true)
                            ->where('dt_note', $list->dt_status)
                            ->where('noinconsistency', false)
                            ->where('type_note', 2)
                            ->last();

                        $lastName = $lastProduction->User->name ?? 'Desconhecido';
                        $company = $lastProduction->Company->name ?? 'Desconhecido';
                        $status = $lastProduction->status ?? 'Desconhecido';

                        $count = $production->count();

                        // Get First and Last name from User Name,
                        $lastName = explode(' ', $lastName);
                        $lastName = $lastName[0] . ' ' . end($lastName);

                        // Get just first Company name.
                        $company = explode(' ', $company)[0];

                        $user = [
                            'lastUser' => $lastName,
                            'countProd' => $count,
                            'status' => $status,
                            'company' => $company,
                        ];
                    } else {
                        $lastProduction = $production->where('completed', true)->where('confirmed', true)->last();

                        $lastName = $lastProduction->User->name ?? 'Desconhecido';
                        $company = $lastProduction->Company->name ?? 'Desconhecido';
                        $status = $lastProduction->status ?? 'Desconhecido';

                        $count = $production->count();

                        $company = explode(' ', $company)[0];

                        $lastName = explode(' ', $lastName);
                        $lastName = $lastName[0] . ' ' . end($lastName);

                        $user = [
                            'lastUser' => $lastName,
                            'countProd' => $count,
                            'status' => $status,
                            'company' => $company,
                        ];
                    }
                }

                $daysLeft = $list->days_left;

            @endphp



            <tr
                class="align-middle
                    @if ($block == 1 && $user['lastUser'] != 'Desconhecido') table-primary
                    @elseif($block == 1 && $user['lastUser'] == 'Desconhecido')
                        table-warning
                    @elseif($block == 2)
                        table-success
                    @elseif($block == 3)
                        table-danger @endif
                    ">

                {{-- @can('management')
                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                        </td>
                    @endcan --}}
                <td class="fw-bold copy-text" data-value="{{ $list->note }}">
                    {{ $list->note }}
                </td>
                <td class="text-center align-middle">
                    @if ($list->WorkForm?->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->ordem }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle fw-bold">
                    @if ($list->WorkForm?->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            @php
                                $soma += $order->moaberto;
                            @endphp
                            <p class="my-0 py-0">
                                R$ {{ number_format($order->moaberto, 2, ',', '.') }}
                            </p>
                        @endforeach
                    @endif

                </td>


                <td class="text-center align-middle">
                    @if ($list->WorkForm?->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0030')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0030')->first()->status)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle">
                    @if ($list->WorkForm?->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0040')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle">
                    @if ($list->WorkForm?->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0050')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0050')->first()->status)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle">
                    @if ($list->WorkForm?->Orders->isNotEmpty())
                        @foreach ($list->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>

                <td class="fw-light text-center">
                    {{ $list->WorkForm ? $list->WorkForm->Company->name : '---' }}
                </td>

                <td class="fw-light text-center">{{ $list->lexp }}</td>

                <td class="fw-light text-center">
                    {{ $list->WorkForm ? date('d/m/Y', strToTime($list->WorkForm->date)) : '---' }}
                </td>
                <td class="fw-light">
                    {{ $list->WorkForm ? date('d/m/Y H:i:s', strToTime($list->WorkForm->informed_at)) : '---' }}
                </td>
                <td>
                    @if ($user)
                        {{ $user['lastUser'] }}
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
                    class="text-center text-center
                    @if ($daysLeft < 0) table-dark
                    @elseif($daysLeft >= 0 && $daysLeft < 3)
                    table-danger
                    @elseif($daysLeft >= 3 && $daysLeft < 6)
                        table-warning
                    @else
                        table-success @endif
                ">
                    {{ isset($list->fimLancado) ? date('d/m/Y', strtotime($list->fimLancado)) : '---' }}
                </td>
                <td>
                    {{ (new DaysLeft($list))->getLastDate() }}
                </td>



            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="table-dark align-middle">

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
        </tr>
    </tfoot>

</table>
