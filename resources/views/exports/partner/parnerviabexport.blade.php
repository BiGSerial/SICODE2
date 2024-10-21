@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp

@if ($lists->count())
    <table class="table table-sm table-condensed table-striped table-hover">
        <thead>
            <tr>
                <th scope="col" class="text-center align-middle">Nota/OV</th>
                <th scope="col" class="text-center align-middle">Ordem</th>
                <th scope="col" class="text-center align-middle">Cliente</th>
                <th scope="col" class="text-center align-middle">Contratado</th>
                <th scope="col" class="text-center align-middle">Recebido</th>
                <th scope="col" class="text-center align-middle">Prazo Viab</th>
                <th scope="col" class="text-center align-middle">Prazo Obra</th>
                <th scope="col" class="text-center align-middle">Rubrica</th>
                <th scope="col" class="text-center align-middle">Descrição</th>
                <th scope="col" class="text-center align-middle">Regiao</th>
                <th scope="col" class="text-center align-middle">Municipio</th>
                <th scope="col" class="text-center align-middle">Status</th>
                <th scope="col" class="text-center align-middle">Em Atividade</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            @foreach ($lists as $index => $list)
                @php
                    $status = null;

                    $dueDate = Carbon::parse($list->sended_at)->addDays($list->getDays() + 7);
                    $today = Carbon::now();
                    $daysDifference = 0;

                    if ($dueDate) {
                        $daysDifference = $today->diffInDays($dueDate);

                        if ($dueDate->isBefore($today)) {
                            $daysDifference *= -1;
                        }

                        if ($daysDifference < 1) {
                            $status = [
                                'color' => 'text-bg-danger',
                                'info' => 'VENCIDO',
                            ];
                        } elseif ($daysDifference >= 1 && $daysDifference < 3) {
                            $status = [
                                'color' => 'text-bg-warning',
                                'info' => 'VENCENDO',
                            ];
                        } elseif ($daysDifference >= 3) {
                            $status = [
                                'color' => 'text-bg-success',
                                'info' => 'NO PRAZO',
                            ];
                        }
                    }

                    $count = 0;
                    $block = null;
                    $color = 'grey';
                    $days_left = (new DaysLeft($list->Note))->getDaysLeft();

                    if ($list->approved) {
                        $count++;
                        $block = [
                            'color' => 'success',
                            'command' => true,
                        ];

                        $color = 'green';
                    } elseif ($list->rejected) {
                        $count++;
                        $block = [
                            'color' => 'danger',
                            'command' => true,
                        ];

                        $color = 'red';
                    }

                    if (($list->rejected || $list->approved) && !$list->completed) {
                        $status = [
                            'color' => 'text-bg-primary',
                            'info' => 'EM AVALIAÇÂO',
                        ];
                    }

                    $color = '';

                    if ($list->approved && !$list->rejected && !$list->tacit) {
                        $color = 'green';
                    } elseif (!$list->approved && $list->rejected && !$list->tacit) {
                        $color = 'red';
                    } elseif ($list->tacit) {
                        $color = 'yellow';
                    }

                    $tcolor = '';

                    if ($list->hired) {
                        $tcolor = 'table-success';
                    }

                @endphp
                <tr wire:key="viability-{{ $list->id }}"
                    wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $list }})"
                    style="cursor: pointer; border-left: 8px solid {{ $color }};">
                    <td class="text-center align-middle">{{ $list->Note->note }}</td>

                    <td class="text-center align-middle">
                        @if ($list->Orders->isNotEmpty())
                            @foreach ($list->Orders as $order)
                                <p class="p-0 m-1">
                                    {{ $order->ordem }}
                                </p>
                            @endforeach
                        @else
                            @if ($viability->Note->Orders->isNotEmpty())
                                @foreach ($viability->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                    <p class="p-0 m-1">
                                        {{ $order->ordem }}
                                    </p>
                                @endforeach
                            @endif
                        @endif


                    </td>
                    <td class="text-center align-middle">{{ $list->Note->client }}</td>
                    <td class="text-center align-middle">
                        {{ $list->hired ? 'SIM' : 'NÃO' }}</td>
                    <td class="text-center align-middle fw-bold">
                        {{ Carbon::parse($list->sended_at)->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle text-danger fw-bold">
                        {{ $dueDate->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle text-primary fw-bold">
                        {{ Carbon::parse($list->sended_at)->addDays($days_left)->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                    <td class="text-center align-middle">{{ $list->Note->material }}</td>
                    <td class="text-center align-middle">
                        {{ $cities->Where('rdMunicipio', $list->Note->nexp)->first() ? $cities->Where('rdMunicipio', $list->Note->nexp)->first()->regiao : '' }}
                    </td>

                    <td class="text-center align-middle">{{ $list->Note->lexp }}</td>

                    <td class="text-center align-middle">
                        {{ Viabilitiesstatus::status($list->status)->status }}
                    </td>

                    <td class="text-center align-middle">
                        {{ isset($list->inActivity) && $list->inActivity ? 'SIM' : 'NÃO' }}
                    </td>


                </tr>
            @endforeach
        </tbody>
    </table>
@endif
