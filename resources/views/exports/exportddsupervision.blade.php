@php
    use App\Helpers\DaysLeft;
    use App\Custom\Notestatus;

@endphp

<table>
    <thead>
        <tr>
            <th>NOTA/OV</th>
            <th>ORDEM</th>
            <th>DT CONCLUSAO</th>
            <th>DT INFORME</th>
            <th>DD</th>
            <th>NumPedido</th>
            <th>RUBRICA</th>
            <th>LONG</th>
            <th>ATIVIDADE</th>
            <th>Grp1</th>
            <th>Grp2</th>
            <th>Grp4</th>
            <th>Grp5</th>
            <th>MUNICIPIO</th>
            <th>STATUS</th>
            <th>CENTRO DE TRABALHO</th>
            <th>PRAZO REAL</th>
            <th>SITUAÇAO</th>
            <th>USUARIO</th>
            <th>EMPRESA</th>
            <th>DESPACHANTE</th>
            <th>DATA DESPACHO</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($exports as $export)
            <tr>
                <td>{{ $export->note }}</td>
                <td>
                    @if ($export->WorkForm)
                        @if ($export->WorkForm->Orders->count())
                            @foreach ($export->WorkForm->Orders as $order)
                                <p class="py-0 my-0">
                                    {{ $order->ordem }}
                                </p>
                            @endforeach
                        @endif
                    @endif
                </td>
                <td>
                    @if ($export->WorkForm)
                        {{ $export->WorkForm->date ? date('d/m/Y', strToTime($export->WorkForm->date)) : '---' }}
                    @endif
                </td>
                <td>
                    @if ($export->WorkForm)
                        {{ $export->WorkForm->informed_at ? date('d/m/Y', strToTime($export->WorkForm->informed_at)) : '---' }}
                    @endif
                </td>
                <td>{{ $export->Wpas->count() ? (!$export->Wpas->last()->production_id ? $export->Wpas->last()->dd : '') : '' }}
                </td>
                <td>{{ $export->numPedido }}</td>
                <td>{{ mb_strtoupper($export->rubrica) }}</td>
                <td>{{ mb_strtoupper($export->material) }}</td>
                <td>
                    @if ($export->rubrica == 'Acompanhamento')
                        ACOMPANHAMENTO
                    @else
                        {{ mb_strtoupper($service->service) }}
                    @endif

                </td>
                <td>{{ $export->group1 }}</td>
                <td>{{ $export->group2 }}</td>
                <td>{{ $export->group4 }}</td>
                <td>{{ $export->group5 }}</td>
                <td>{{ $export->lexp }}</td>
                <td>{{ $export->nstats }}</td>
                <td>{{ $export->centerjob }}</td>
                <td>{{ (new DaysLeft($export))->getDaysLeft() }}</td>
                @php
                    $production = null;

                    if (
                        !$export->Productions->isEmpty() &&
                        $export->Productions
                            ->where('service_id', $service->uuid)
                            ->where('completed', false)
                            ->count()
                    ) {
                        $production = $export->Productions
                            ->where('service_id', $service->uuid)
                            ->where('completed', false)
                            ->last();
                    }
                @endphp
                <td>
                    @if ($production)
                        {{ Notestatus::status($production->status)->status }}
                    @endif
                </td>
                <td>
                    @if ($production)
                        {{ $production->User->name }}
                    @endif
                </td>
                <td>
                    @if ($production)
                        {{ $production->Company->name }}
                    @endif
                </td>
                <td>
                    @if ($production)
                        {{ $production->Dispatcher->name }}
                    @endif
                </td>
                <td>
                    @if ($production)
                        {{ $production->dispatch_at }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
