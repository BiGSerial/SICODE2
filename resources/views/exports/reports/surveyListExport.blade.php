@php
    use App\Helpers\DaysLeft;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp

<table>
    <thead>
        <tr>
            <th>Note</th>
            <th>DD</th>
            <th>DT_Created</th>
            <th>MMGD</th>
            <th>Rubrica</th>
            <th>Municipio</th>
            <th>Grupo1</th>
            <th>Grupo2</th>
            <th>Grupo3</th>
            <th>Grupo4</th>
            <th>Grupo5</th>
            <th>Ultimo Levantamento</th>
            <th>Prazo Real</th>
            <th>Dt Final</th>
            <th>Status</th>
            <th>Levantador</th>
            <th>Empresa</th>
        </tr>
    </thead>
    <tbody>
        @if ($datas->count())
            @foreach ($datas as $data)
                <tr>
                    <td>{{ $data->note }}</td>
                    <td>
                        @if ($data->Wpas->count())
                            {{ $data->Wpas->last()->dd }}
                        @endif
                    </td>
                    <td>{{ Carbon::parse($data->dt_created)->format('d/m/Y') }}</td>
                    <td>{{ $data->mmgd ? 'SIM' : 'NÃO' }}</td>
                    <td>{{ $data->rubrica }}</td>
                    <td>{{ $data->lexp }}</td>
                    <td>{{ $data->group1 }}</td>
                    <td>{{ $data->group2 }}</td>
                    <td>{{ $data->group3 }}</td>
                    <td>{{ $data->group4 }}</td>
                    <td>{{ $data->group5 }}</td>
                    <td>
                        @if (
                            $data->Productions->isNotEmpty() &&
                                $data->Productions->where('completed', true)->where('service_id', $service_id)->isNotEmpty())
                            {{ Carbon::parse($data->Productions->where('completed', true)->where('service_id', $service_id)->last()->completed_at)->format('d/m/Y H:i:s') }}
                        @else
                            {{ '' }}
                        @endif

                    </td>
                    <td>{{ (new Daysleft($data))->getDaysLeft() }}</td>
                    <td>{{ (new Daysleft($data))->getLastDate() }}</td>
                    <td>
                        @if (
                            $data->Productions->isNotEmpty() &&
                                $data->Productions->where('service_id', $service_id)->where('confirmed', false)->isNotEmpty())
                            {{ Notestatus::status($data->Productions->where('service_id', $service_id)->where('confirmed', false)->last()->status)->status }}
                        @else
                            {{ 'Sem Atribução' }}
                        @endif
                    </td>
                    <td>
                        @if (
                            $data->Productions->isNotEmpty() &&
                                $data->Productions->where('service_id', $service_id)->where('confirmed', false)->isNotEmpty())
                            {{ $data->Productions->where('service_id', $service_id)->where('confirmed', false)->last()->User ? $data->Productions->where('service_id', $service_id)->where('confirmed', false)->last()->User->name : '' }}
                        @endif
                    </td>
                    <td>
                        @if (
                            $data->Productions->isNotEmpty() &&
                                $data->Productions->where('service_id', $service_id)->where('confirmed', false)->isNotEmpty())
                            {{ $data->Productions->where('service_id', $service_id)->where('confirmed', false)->last()->Company ? $data->Productions->where('service_id', $service_id)->where('confirmed', false)->last()->Company->name : '' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
