{{-- resources/views/exports/dispatchs/surveyStack.blade.php --}}
@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;

    /** Normaliza para Carbon|null (aceita Carbon|string|null) */
    if (!function_exists('toCarbon')) {
        function toCarbon($v): ?Carbon
        {
            if (empty($v)) {
                return null;
            }
            if ($v instanceof Carbon) {
                return $v;
            }
            try {
                return Carbon::parse($v);
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    /** Cor por prazo (verde ≤70% do limite, amarelo entre 70% e limite, vermelho > limite) */
    if (!function_exists('getColor')) {
        function getColor($date, int $limit): string
        {
            $d = toCarbon($date)?->startOfDay();
            if (!$d) {
                return '#ffffff';
            }
            $diff = $d->diffInDays(Carbon::now()->startOfDay());
            $warn = (int) ceil($limit * 0.7);
            return $diff > $limit ? '#f8d7da' : ($diff <= $warn ? '#d1e7dd' : '#fff3cd');
        }
    }

    /** Nome curto do usuário */
    if (!function_exists('shortUser')) {
        function shortUser($name): string
        {
            if (empty($name)) {
                return 'Desconhecido';
            }
            $parts = preg_split('/\s+/', trim((string) $name));
            return $parts[0] . ' ' . (count($parts) > 1 ? end($parts) : '');
        }
    }

    $agora = Carbon::now()->startOfDay();
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

        .fw-bold {
            font-weight: bold;
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
                    // Usar relação NOTE (sem alias) — dt_created vem do Note com cast (recomendado no Model Note)
                    $dtCreated = toCarbon($item->note?->dt_created);
                    $dispatchAt = toCarbon($item->dispatch_at); // tem cast no Production
                    $attAt = toCarbon($item->att_at); // tem cast no Production

                    // Cores
                    $colorPrazo = getColor($dtCreated, 30);
                    $colorDispatch = getColor($dispatchAt, 30);
                    $colorAtt = getColor($attAt, 9);

                    // WPA (último registro)
                    $lastWpa = optional($item->wpas)->last();
                    $wpa = WpaStatus::status(
                        $lastWpa->dd ?? null,
                        $lastWpa->execstats ?? null,
                        $lastWpa->completed_at ?? null,
                    );
                @endphp

                <tr>
                    <td class="fw-bold">{{ $item->note?->note }}</td>

                    <td>
                        {{ $lastWpa->dd ?? '' }}
                        {{-- Ex.: {{ $wpa->label ?? '' }} --}}
                    </td>

                    <td>{{ $item->note?->rubrica ?? '---' }}</td>
                    <td>{{ $item->note?->lexp ?? '---' }}</td>
                    <td>{{ $item->note?->group2 ?? '---' }}</td>
                    <td>{{ shortUser($item->user?->name ?? '') }}</td>

                    {{-- Prazo real: dt_created + 30 --}}
                    <td style="background-color: {{ $colorPrazo }}">
                        {{ $dtCreated ? $dtCreated->copy()->addDays(30)->format('d/m/Y') : '---' }}
                    </td>

                    {{-- Prazo (dias) desde dt_created --}}
                    <td>
                        @php $diffPrazo = $dtCreated ? $dtCreated->startOfDay()->diffInDays($agora) : null; @endphp
                        {{ $diffPrazo !== null ? $diffPrazo . ' dias' : '---' }}
                    </td>

                    {{-- Em despacho --}}
                    <td style="background-color: {{ $colorDispatch }}">
                        {{ $dispatchAt ? $dispatchAt->format('d/m/Y') : '---' }}
                    </td>
                    <td>
                        @php $diffDispatch = $dispatchAt ? $dispatchAt->startOfDay()->diffInDays($agora) : null; @endphp
                        {{ $diffDispatch !== null ? $diffDispatch . ' dias' : '---' }}
                    </td>

                    {{-- Em atendimento --}}
                    <td style="background-color: {{ $colorAtt }}">
                        {{ $attAt ? $attAt->format('d/m/Y') : '---' }}
                    </td>
                    <td>
                        @php $diffAtt = $attAt ? $attAt->startOfDay()->diffInDays($agora) : null; @endphp
                        {{ $diffAtt !== null ? $diffAtt . ' dias' : '---' }}
                    </td>

                    {{-- Status geral da produção --}}
                    <td>{{ Notestatus::status($item->status)->status ?? '---' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
