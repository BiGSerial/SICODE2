{{-- resources/views/exports/levantamento.blade.php --}}
@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;

    if (!function_exists('shortUser')) {
        function shortUser($name)
        {
            if (empty($name)) {
                return 'Desconhecido';
            }
            $parts = explode(' ', trim($name));
            return $parts[0] . ' ' . (count($parts) > 1 ? end($parts) : '');
        }
    }

    if (!function_exists('getColor')) {
        function getColor($dateField, int $limit)
        {
            if (!$dateField) {
                return '#ffffff';
            }
            $diff = Carbon::parse($dateField)
                ->startOfDay()
                ->diffInDays(Carbon::now()->startOfDay());
            $warn = ceil($limit * 0.7);
            return $diff > $limit
                ? '#f8d7da' // vermelho claro
                : ($diff <= $warn
                    ? '#d1e7dd'
                    : '#fff3cd'); // verde ou amarelo
        }
    }
@endphp

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Controle de Levantamento</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h2 {
            text-align: center;
            margin-bottom: 8px;
        }

        p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 5px 6px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #2f2f2f;
            color: #fff;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .small {
            font-size: 11px;
        }
    </style>
</head>

<body>
    <table>
        <thead>
            <tr>

                <th>Nota</th>
                <th>DD / Status WPA</th>
                <th>Rubrica</th>
                <th>Município</th>
                <th>Grupo 2</th>
                <th>Usuário</th>

                <th>Prazo Real</th>
                <th>Prazo (dias)</th>
                <th>Em Despacho</th>
                <th>Despacho (dias)</th>
                <th>Em Att</th>
                <th>Att (dias)</th>
                <th>Status Produção</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($lists as $item)
                @php
                    // Cores por data
                    $colorPrazo = getColor($item->dt_created, 30);
                    $colorDispatch = getColor($item->dispatch_at, 30);
                    $colorAtt = getColor($item->att_at, 9);

                    $wpa = WpaStatus::status(
                        $item->wpas?->last()?->dd,
                        $item->wpas?->last()?->execstats,
                        $item->wpas?->last()?->completed_at,
                    );
                @endphp

                <tr>

                    <td class="fw-bold">{{ $item->note?->note }}</td>
                    <td>
                        {{ $item->wpas?->last()?->dd }}
                    </td>
                    <td>{{ $item->note?->rubrica ?? '---' }}</td>
                    <td>{{ $item->note?->lexp ?? '---' }}</td>
                    <td>{{ $item->note?->group2 ?? '---' }}</td>
                    <td>{{ shortUser($item->user?->name ?? '') }}</td>

                    {{-- Última atualização --}}


                    {{-- Prazo real --}}
                    <td style="background-color: {{ $colorPrazo }}">
                        {{ $item->dt_created ? Carbon::parse($item->dt_created)->addDays(30)->format('d/m/Y') : '---' }}
                    </td>
                    <td>{{ $item->dt_created ? $item->dt_created->diffInDays(Carbon::now()) . ' dias' : '---' }}</td>
                    {{-- Em despacho --}}
                    <td style="background-color: {{ $colorDispatch }}">
                        {{ $item->dispatch_at ? Carbon::parse($item->dispatch_at)->format('d/m/Y') : '---' }}
                    </td>

                    <td>{{ $item->dispatch_at ? $item->dispatch_at->diffInDays(Carbon::now()) . ' dias' : '---' }}</td>

                    {{-- Em atendimento --}}
                    <td style="background-color: {{ $colorAtt }}">
                        {{ $item->att_at ? Carbon::parse($item->att_at)->format('d/m/Y') : '---' }}
                    </td>

                    <td>{{ $item->att_at ? $item->att_at->diffInDays(Carbon::now()) . ' dias' : '---' }}</td>

                    {{-- Status geral --}}
                    <td>
                        {{ Notestatus::status($item->status)->status ?? '---' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


</body>

</html>
