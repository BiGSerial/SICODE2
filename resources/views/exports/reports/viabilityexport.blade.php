@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Viabilitiesstatus;
@endphp
<table class="table table-sm table-condensed table-striped">
    <thead>
        <tr>
            <th scope="col">Contratante</th>
            <th scope="col">Empresa</th>
            <th scope="col">Ordem</th>
            <th scope="col">Nota</th>
            <th scope="col">Contratado</th>
            <th scope="col">Tácito</th>
            <th scope="col">Enviado Em</th>
            <th scope="col">Contratado Em</th>
            <th scope="col">Viabilizado Em</th>
            <th scope="col">Completado em</th>
            <th scope="col">Vencimento Tácito</th>
            <th scope="col">Responsável</th>
            <th scope="col">Empreiteira</th>
            <th scope="col">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $list)
            <tr>
                <td class="align-middlw">{{ $list->User->name }}</td>
                <td class="align-middlw">{{ $list->User->Employee->Contract->Company->name }}</td>
                <td class="align-middlw">
                    @if ($list->Orders->count())
                        @foreach ($list->Orders as $order)
                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                        @endforeach
                    @else
                        @if ($list->Note->Orders->isNotEmpty())
                            @foreach ($list->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                            @endforeach
                        @endif
                    @endif
                </td>
                <td class="align-middlw">{{ $list->Note->note }}</td>
                <td class="align-middlw">{{ $list->hired ? 'SIM' : 'NÃO' }}</td>
                <td class="align-middlw">{{ $list->tacit ? 'SIM' : 'NÃO' }}</td>
                <td class="align-middlw">
                    {{ $list->sended_at ? date('d/m/Y', strToTime($list->sended_at)) : '---' }}
                </td>
                <td class="align-middlw">
                    {{ $list->hired_at ? date('d/m/Y', strToTime($list->hired_at)) : '---' }}
                </td>
                <td class="align-middlw">
                    {{ $list->returned_at ? date('d/m/Y', strToTime($list->returned_at)) : '---' }}
                </td>
                <td class="align-middlw">
                    {{ $list->completed_at ? date('d/m/Y', strToTime($list->completed_at)) : '---' }}
                </td>
                <td class="align-middlw">
                    {{ $list->tacit_at ? date('d/m/Y', strToTime($list->tacit_at)) : '---' }}
                </td>
                <td class="align-middlw">{{ isset($list->Engineer->name) ? $list->Engineer->name : '---' }}</td>
                <td class="align-middlw">{{ isset($list->Company->name) ? $list->Company->name : '---' }}</td>
                <td class="align-middlw">{{ Viabilitiesstatus::status($list->status)->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
