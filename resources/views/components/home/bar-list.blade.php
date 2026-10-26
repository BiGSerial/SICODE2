@props([
    'rows'    => [],
    'caption' => 'Dados do gráfico',
    'empty'   => 'Sem dados para os filtros atuais.',
    'headers' => ['Item', 'Valor', 'Detalhe'],
])

{{-- Lista de barras horizontais (CSS) com a mesma informação em tabela, para teclado e leitor de tela.
     rows: label*, text*, pct* (0-100), tone (blue|teal|green|amber|red|muted|group-*), mark (0-100, marcador opcional), sub (detalhe) --}}
@if (count($rows) === 0)
    <x-closure.empty-state icon="ri-bar-chart-horizontal-line" :title="$empty" />
@else
    <ul class="mp-bars" role="list">
        @foreach ($rows as $row)
            <li class="mp-bar-row" title="{{ $row['label'] }}: {{ $row['text'] }}{{ !empty($row['sub']) ? ' · ' . $row['sub'] : '' }}">
                <span class="mp-bar-label">{{ $row['label'] }}</span>
                <span class="mp-bar-track" aria-hidden="true">
                    <span class="mp-bar-fill tone-{{ $row['tone'] ?? 'blue' }}" style="--pct: {{ max(1, min(100, $row['pct'])) }}"></span>
                    @isset($row['mark'])
                        <span class="mp-bar-mark" style="--pct: {{ max(0, min(100, $row['mark'])) }}"></span>
                    @endisset
                </span>
                <span class="mp-bar-value">{{ $row['text'] }}</span>
            </li>
        @endforeach
    </ul>

    <details class="mp-table-toggle">
        <summary>Ver como tabela</summary>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <caption class="visually-hidden">{{ $caption }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ $headers[0] }}</th>
                        <th scope="col" class="text-end">{{ $headers[1] }}</th>
                        <th scope="col">{{ $headers[2] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <th scope="row" class="fw-semibold">{{ $row['label'] }}</th>
                            <td class="text-end">{{ $row['text'] }}</td>
                            <td class="text-muted">{{ $row['sub'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif
