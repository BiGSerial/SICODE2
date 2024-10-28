@php
    use App\Helpers\DaysLeft;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Custom\Viabilitiesstatus;
@endphp

@if ($myLists)
    <table class="table table-sm table-condensed table-hover table-striped">
        <thead>
            <tr>

                <th scope="col" class="text-center align-middle">Nota/OV</th>
                <th scope="col" class="text-center align-middle">Arquivos</th>
                <th scope="col" class="text-center align-middle">Ordem</th>
                <th scope="col" class="text-center align-middle">Contratado</th>
                <th scope="col" class="text-center align-middle">Viabilizado Em</th>
                <th scope="col" class="text-center align-middle">Motivo</th>
                <th scope="col" class="text-center align-middle">Responsável</th>
                <th scope="col" class="text-center align-middle">Rubrica</th>
                <th scope="col" class="text-center align-middle">Municipio</th>
                <th scope="col" class="text-center align-middle">Status</th>
                <th scope="col" class="text-center align-middle">Empreiteira</th>
                <th scope="col" class="text-center align-middle">Tempo</th>


            </tr>
        </thead>
        <tbody>
            @foreach ($myLists as $myViab)
                @php
                    $status = null;

                    $dueDate = Carbon::parse($myViab->sended_at)->addDays($myViab->getDays() + 7);

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

                    $block = null;
                    $color = 'grey';
                    $days_left = (new DaysLeft($myViab->Note))->getDaysLeft();
                    $count = 0;

                    if ($myViab->approved) {
                        $count++;
                        $block = [
                            'color' => 'success',
                            'command' => true,
                        ];

                        $color = 'green';
                    } elseif ($myViab->rejected) {
                        $count++;
                        $block = [
                            'color' => 'danger',
                            'command' => true,
                        ];

                        $color = 'red';
                    }

                    if (($myViab->rejected || $myViab->approved) && !$myViab->completed) {
                        $status = [
                            'color' => 'text-bg-primary',
                            'info' => 'EM AVALIAÇÂO',
                        ];
                    }

                    $color = '';

                    if ($myViab->approved && !$myViab->rejected && !$myViab->tacit) {
                        $color = 'green';
                    } elseif (!$myViab->approved && $myViab->rejected && !$myViab->tacit) {
                        $color = 'red';
                    } elseif ($myViab->tacit) {
                        $color = 'yellow';
                    }

                    $tcolor = '';

                    if ($myViab->hired) {
                        $tcolor = 'table-success';
                    }
                @endphp

                <tr wire:key='Myviab_{{ $myViab->id }}'>
                    <td class="text-center align-middle fw-bold">{{ $myViab->Note->note }}</td>
                    <td class="text-center align-middle"> {{ $myViab->Note->Files->isNotEmpty() ? 'SIM' : 'NÃO' }}
                    </td>
                    <td class="text-center align-middle">
                        @if ($myViab->Orders->isNotEmpty())
                            @foreach ($myViab->Orders as $order)
                                <p class="p-0 m-1">
                                    {{ $order->ordem }}
                                </p>
                            @endforeach
                        @elseif ($myViab->Note->Orders->isNotEmpty())
                            @foreach ($myViab->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                <p class="p-0 m-1">
                                    {{ $order->ordem }}
                                </p>
                            @endforeach
                        @endif
                    </td>
                    <td class="text-center align-middle">{{ $myViab->hired ? 'SIM' : 'NÃO' }}</td>
                    <td class="text-center align-middle">
                        {{ Carbon::parse($myViab->returned_at)->format('d/m/Y') }}</td>
                    <td class="text-center align-middle fw-bold">
                        @if ($myViab->Form)
                            {{ $myViab->Form->reason }}
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @if ($myViab->Engineer)
                            <p class="my-0 py-0">{{ $myViab->Engineer->name }}</p>
                            <p class="my-0 py-0 text-primary">{{ $myViab->Engineer->email }}</p>
                        @endif
                    </td>
                    <td class="text-center align-middle">{{ $myViab->Note->rubrica }}</td>
                    <td class="text-center align-middle">{{ $myViab->Note->lexp }}</td>
                    <td class="text-center align-middle"><span
                            class="badge {{ Viabilitiesstatus::status($myViab->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($myViab->status)->status }}</span>
                    </td>
                    <td class="text-center align-middle">{{ $myViab->Company->name }}</td>
                    <td class="text-center align-middle text-danger">
                        {{ Carbon::parse($myViab->updated_at)->diffForHumans() }}</td>
            @endforeach
        </tbody>
    </table>
@endif
