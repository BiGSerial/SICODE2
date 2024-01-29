@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
@endphp
<table>
    <thead>
        <tr>
            <th>Despachante</th>
            <th>Empresa</th>
            <th>Usuario</th>
            <th>Empresa</th>
            <th>Serviço</th>
            <th>Rubrica</th>
            <th>TipoNota</th>
            <th>Nota</th>
            <th>Grp2</th>
            <th>Descricao</th>
            <th>Municipio</th>
            <th>Despachado em</th>
            <th>Atribuído em</th>
            <th>Finalizado em</th>
            <th>ODI/DR</th>
            <th>ODD</th>
            <th>ODS</th>
            <th>EO</th>
            <th>iProject</th>
            <th>Cadastro</th>
            <th>Postes Cadastro</th>
            <th>Postes</th>
            <th>Parado</th>
            <th>Situação</th>
            <th>Produção</th>
            <th>Conclusão</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($exports as $export)
            <tr>
                <td>{{ isset($export->User->name) ? $export->User->name : 'Desconhecido' }}</td>
                <td>{{ explode(' ', $export->Company->name)[0] }}</td>
                <td>{{ isset($export->User->name) ? $export->User->name : 'Desconhecido' }}</td>
                <td>{{ explode(' ', $export->Company->name)[0] }}</td>
                <td>{{ $export->Service->service }}</td>
                <td>{{ $export->Note->rubrica }}</td>
                <td>{{ $export->Note->type_note }}</td>
                <td>{{ $export->Note->note }}</td>
                <td>{{ $export->Note->group2 }}</td>
                <td>{{ $export->Note->material }}</td>
                <td>{{ $export->Note->lexp }}</td>
                <td>{{ date('d/m/Y H:i:s', strToTime($export->dispatch_at)) }}</td>
                <td>
                    @if ($export->att_at)
                        {{ date('d/m/Y H:i:s', strToTime($export->att_at)) }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if ($export->completed_at)
                        {{ date('d/m/Y H:i:s', strToTime($export->completed_at)) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $export->odi ? $export->odi : '---' }}</td>
                <td>{{ $export->odd ? $export->odd : '---' }}</td>
                <td>{{ $export->ods ? $export->ods : '---' }}</td>
                <td>{{ $export->eo ? 'SIM' : 'NÃO' }}</td>
                <td>{{ $export->iproject ? 'SIM' : 'NÃO' }}</td>
                <td>{{ $export->cadastro ? 'SIM' : 'NÃO' }}</td>
                <td>{{ $export->postes_c ? $export->postes_c : '--' }}</td>
                <td>{{ $export->postes_u ? $export->postes_u : '--' }}</td>
                <td>{{ CarbonInterval::seconds($export->stopped)->cascade()->forHumans(['short' => true]) }}</td>
                <td>
                    @if ($export->confirmed)
                        Contabilizado
                    @else
                        Não Contabilizado
                    @endif
                </td>
                <td>
                    @if ($export->completed)
                        Finalizado
                    @else
                        Em Aberto
                    @endif
                </td>
                <td>
                    <span class="fw-bold" style="font-size: 10px">
                        @if ($export->Analise)
                            {{ $export->Analise->conclusion }}
                        @endif
                    </span>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
