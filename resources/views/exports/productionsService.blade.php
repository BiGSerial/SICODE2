@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;
@endphp
<table>
    <thead>
        <tr>
            <th scope="col" class="fw-bold">Note</th>
            <th scope="col" class="fw-bold">DD</th>
            <th scope="col" class="fw-bold">Criado Em</th>
            <th scope="col" class="fw-bold">numPedido</th>
            <th scope="col" class="fw-bold">Rubrica</th>
            <th scope="col" class="fw-bold">Municipio</th>
            <th scope="col" class="fw-bold">Zona</th>
            <th scope="col" class="fw-bold">Descrição</th>
            <th scope="col" class="fw-bold">Dias Atribuido</th>
            <th scope="col" class="fw-bold">Prazo Real</th>
            <th scope="col" class="fw-bold">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $list)
            <tr class="align-middle @if ($list->priority) table-danger @endif">
                <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->note }}


                    @if ($list->priority)
                        <i class="ri-alert-fill align-middle"></i>
                    @endif
                </td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    @if ($list->Wpas->count())
                        {{ $list->Wpas()->orderBy('created_at', 'DESC')->first()->dd }}
                    @else
                        -----
                    @endif

                </td>
                <td class="fw-light">
                    {{ date('d/m/Y', strToTime($list->Note->dt_created)) }}</td>
                <td class="fw-light">{{ $list->Note->numPedido }}</td>
                <td class="fw-light">{{ $list->Note->rubrica }}</td>
                <td class="fw-light">{{ $list->Note->lexp }}</td>
                <td class="fw-light">{{ $list->Note->group1 }}</td>
                <td class="fw-light">{{ $list->Note->material }}</td>
                <td class="fw-light">
                    {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                </td>
                <td scope="col" class="text-center">
                    {{ 30 - $list->Note->days_left }}
                </td>
                {{-- <td class="fw-light">
                {{ Carbon::now()->diffInDays(Carbon::parse($list->Note->dt_status)->format('Y-m-d')) }}
            </td> --}}

                <td class="fw-light">
                    @if ($list->transferred && $list->block_wpa)
                        Aguardando Despacho
                    @else
                        {{ Notestatus::status($list->status)->status }}
                    @endif
                </td>
        @endforeach
    </tbody>
</table>
