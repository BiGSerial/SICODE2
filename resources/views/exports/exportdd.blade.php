<table>
    <thead>
        <tr>
            <th>NOTA/OV</th>
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
        </tr>
    </thead>
    <tbody>
        @foreach ($exports as $export)
            <tr>
                <td>{{ $export->note }}</td>
                <td>{{ $export->Wpas->count() ? (!$export->Wpas->last()->production_id ? $export->Wpas->last()->dd : '') : '' }}
                </td>
                <td>{{ $export->numPedido }}</td>
                <td>{{ mb_strtoupper($export->rubrica) }}</td>
                <td>{{ mb_strtoupper($export->material) }}</td>
                <td>
                    @if ($export->rubrica == 'Acompanhamento')
                        ACOMPANHAMENTO
                    @else
                        {{ mb_strtoupper($service) }}
                    @endif

                </td>
                <td>{{ $export->group1 }}</td>
                <td>{{ $export->group2 }}</td>
                <td>{{ $export->group4 }}</td>
                <td>{{ $export->group5 }}</td>
                <td>{{ $export->lexp }}</td>
                <td>{{ $export->nstats }}</td>
                <td>{{ $export->centerjob }}</td>
                <td>{{ 30 - $export->days_left }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
