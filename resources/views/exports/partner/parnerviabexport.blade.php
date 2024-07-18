@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
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

                    $dueDate = $list->Viabilities->count()
                        ? Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)
                        : null;
                    $today = Carbon::now();
                    $daysDifference = 0;

                    if ($dueDate) {
                        $daysDifference = $dueDate ? $today->diffInDays($dueDate) : null;

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

                    $block = null;
                    $color = 'grey';
                    $days_left = 0;

                    // Dias Restantes
                    if ($list->type_note == 1) {
                        if ($list->mesalization && $list->mesalization != 'erro') {
                            preg_match('/\d+\/\d+/', $list->mesalization, $matches);

                            if (!empty($matches)) {
                                [$mes, $ano] = explode('/', $matches[0]);

                                if ($mes >= 1) {
                                    $data = "{$ano}-{$mes}-28 23:59:59";

                                    $hoje = Carbon::now();

                                    $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                    $days_left = $hoje->diffInDays($dataCarbon, false);
                                } else {
                                    $data = "{$ano}-12-28 23:59:59";

                                    $hoje = Carbon::now();

                                    $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                    $days_left = $hoje->diffInDays($dataCarbon, false);
                                }
                            }
                        }
                    } elseif ($list->type_note == 2) {
                        $days_left = $list->days_left;
                    }

                    if ($list->Viabilities->count()) {
                        $count = 0;

                        foreach ($list->Viabilities as $order) {
                            if ($order->approved) {
                                $count++;

                                $block = [
                                    'color' => 'green',
                                    'command' => true,
                                ];

                                $color = 'green';
                            } elseif ($order->rejected) {
                                $count++;

                                $block = [
                                    'color' => 'danger',
                                    'command' => true,
                                ];

                                $color = 'red';
                            }

                            if (($order->rejected || $order->approved) && !$order->completed) {
                                $status = [
                                    'color' => 'text-bg-primary',
                                    'info' => 'EM AVALIAÇÂO',
                                ];
                            }
                        }

                        if ($count == $list->Viabilities->count()) {
                            $block = array_merge($block, ['command' => false]);
                        }
                    }

                    $color = '';

                    if (
                        $list->Viabilities->last()->approved &&
                        !$list->Viabilities->last()->rejected &&
                        !$list->Viabilities->last()->tacit
                    ) {
                        $color = 'green';
                    } elseif (
                        !$list->Viabilities->last()->approved &&
                        $list->Viabilities->last()->rejected &&
                        !$list->Viabilities->last()->tacit
                    ) {
                        $color = 'red';
                    } elseif ($list->Viabilities->last()->tacit) {
                        $color = 'yellow';
                    }

                    $tcolor = '';

                    if ($list->Viabilities->last()->hired) {
                        $tcolor = 'table-success';
                    }

                @endphp
                <tr wire:key="viability-{{ $list->id }}"
                    wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $list }})"
                    style="cursor: pointer; border-left: 8px solid {{ $color }};">
                    <td class="text-center align-middle">{{ $list->note }}</td>

                    <td class="text-center align-middle">
                        @if ($list->Viabilities->count())
                            @foreach ($list->Viabilities as $viab)
                                <p class="p-0 m-1">
                                    {{ $viab->Order->ordem }}
                                </p>
                            @endforeach
                        @endif
                    </td>
                    <td class="text-center align-middle">{{ $list->client }}</td>
                    <td class="text-center align-middle">
                        {{ $list->Viabilities->last()->hired ? 'SIM' : 'NÃO' }}</td>
                    <td class="text-center align-middle fw-bold">
                        {{ Carbon::parse($list->Viabilities->last()->sended_at)->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle text-danger fw-bold">
                        {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle text-primary fw-bold">
                        {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays($days_left)->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle">{{ $list->rubrica }}</td>
                    <td class="text-center align-middle">{{ $list->material }}</td>
                    <td class="text-center align-middle">
                        {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->regiao : '' }}
                    </td>

                    <td class="text-center align-middle">{{ $list->lexp }}</td>

                    <td class="text-center align-middle">
                        {{ Viabilitiesstatus::status($list->Viabilities->last()->status)->status }}
                    </td>

                    <td class="text-center align-middle">
                        {{ isset($list->Viabilities->last()->inActivity) && $list->Viabilities->last()->inActivity ? 'SIM' : 'NÃO' }}
                    </td>


                </tr>
            @endforeach
        </tbody>
    </table>
@endif
