@php
    use App\Helpers\DaysLeft;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Custom\Viabilitiesstatus;
@endphp



<table>
    <thead>
        <tr>
            <th>Note/OV</th>
            <th>Ordem/DR</th>
            <th>Rubrica</th>
            <th>Município</th>
            <th>Contratado Em</th>
            <th>Enviado Em</th>
            <th>PrazoViabilidade</th>
            <th>Vencido Em</th>
            <th>Prazo Justificativa</th>
            <th>Justificado Em</th>
            <th>Resultado</th>
            <th>Valor MOA</th>
            <th>Penalidade</th>
            <th>Responsável</th>
        </tr>
    </thead>
    <tbody>
        @if ($data)
            @foreach ($data as $viab)
                <tr>
                    <td>{{ $viab->Note->note }}</td>
                    <td>
                        @if ($viab->Orders->isNotEmpty())
                            @foreach ($viab->Orders as $order)
                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $viab->Note->rubrica }}</td>
                    <td>{{ $viab->Note->lexp }}</td>
                    <td>{{ $viab->hired_at ? Carbon::parse($viab->hired_at)->format('d/m/Y') : '' }}</td>
                    <td>{{ $viab->sended_at ? Carbon::parse($viab->sended_at)->format('d/m/Y') : '' }}</td>
                    <td>{{ $viab->sended_at? Carbon::parse($viab->sended_at)->addDays(7 + $viab->getDays())->format('d/m/Y'): '' }}
                    </td>
                    <td>{{ $viab->tacit_at ? Carbon::parse($viab->tacit_at)->format('d/m/Y') : '' }}</td>
                    <td>{{ $viab->tacit_at? Carbon::parse($viab->tacit_at)->addDays(7)->format('d/m/Y'): '' }}</td>
                    <td>{{ $viab->Justification ? Carbon::parse($viab->Justification->created_at)->format('d/m/Y') : '' }}
                    </td>
                    <td>
                        @if (!$viab->Justification)
                            <span class="badge badge-danger">Não Justificado</span>
                        @elseif($viab->Justification->granted == 1 && $viab->Justification->dismissed == 0)
                            <span class="badge badge-success">Deferido</span>
                        @elseif($viab->Justification->granted == 0 && $viab->Justification->dismissed == 1)
                            <span class="badge badge-danger">Indeferido</span>
                        @else
                            <span class="badge badge-warning">Pendente</span>
                        @endif
                    </td>
                    <td> {{ number_format($viab->value, 2, ',', '.') }}</td>

                    <td>{{ number_format($viab->value * 0.01, 2, ',', '.') }}</td>
                    <td>{{ $viab->Engineer ? $viab->Engineer->name : '' }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
