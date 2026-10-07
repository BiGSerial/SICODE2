@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<table class="table table-sm table-condensed table-striped-columns">
    <thead>
        <tr>
            <th scope="col" class="text-center">Origem</th>
            <th scope="col" class="text-center">Nota</th>
            <th scope="col" class="text-center">Rubrica</th>
            <th scope="col" class="text-center">Municipio</th>
            <th scope="col" class="text-center">Grupo 5</th>
            <th scope="col" class="text-center">Material</th>
            <th scope="col" class="text-center">Solicitante</th>
            <th scope="col" class="text-center">Empresa Solicitante</th>
            <th scope="col" class="text-center">Categoria</th>
            <th scope="col" class="text-center">Texto</th>
            <th scope="col" class="text-center">Data Envio</th>
            <th scope="col" class="text-center">Em Atividade</th>
            <th scope="col" class="text-center">Status</th>
            <th scope="col" class="text-center">Responsável</th>
            <th scope="col" class="text-center">Empresa Responsável</th>
        </tr>
    </thead>
    <tbody class="table-group-divider">
        @if ($lists)
            @foreach ($lists as $list)
                @php
                    $vencido = false;
                    $vencimento = Carbon::now()->subHours(24)->toDateTimeString();
                    if ($list->updated_at < $vencimento) {
                        $vencido = true;
                    }
                @endphp

                <tr wire:key="row-{{ $list->id }}">
                    @php($row = \App\Services\Reclaims\ReclaimRowPresenter::row($list))
                    <td class="text-center align-middle fw-bold">{{ mb_strtoupper($row['origin']) }}</td>
                    <td class="text-center align-middle fw-bold">{{ $row['note'] }}</td>
                    <td class="text-center align-middle">{{ $row['rubrica'] }}</td>
                    <td class="text-center align-middle">{{ $row['municipality'] }}</td>
                    <td class="text-center align-middle">{{ $row['group5'] }}</td>
                    <td class="text-center align-middle">{{ $row['material'] }}</td>
                    <td class="text-center align-middle">{{ $row['requester'] }}</td>
                    <td class="text-center align-middle">{{ $row['requester_company'] }}</td>
                    <td class="text-center align-middle">{{ $row['category'] }}</td>
                    <td class="text-center align-middle">{{ $row['text'] }}</td>
                    <td class="text-center align-middle">
                        {{ Carbon::parse($list->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td
                        class="text-center align-middle
                    @if ($vencido) text-bg-danger @endif
                    ">
                        {{ Carbon::parse($list->created_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                    </td>
                    <td class="text-center align-middle">{{ $row['status'] }}</td>
                    <td class="text-center align-middle">{{ $row['responsible'] }}</td>
                    <td class="text-center align-middle">{{ $row['responsible_company'] }}</td>
                </tr>
            @endforeach
        @endif

    </tbody>
</table>
