@props([
    'chart' => [],
    'chartId' => null,
    'class' => 'w-full h-64',
])

@php
    $finalId = $chartId ?? 'chart_' . uniqid();
    $type = $chart['type'] ?? 'bar';
    $data = $chart['data'] ?? [];
    $options = $chart['options'] ?? [];
    $options = array_merge(
        [
            'responsive' => true,
            'maintainAspectRatio' => false,
        ],
        $options,
    );
@endphp

<canvas id="{{ $finalId }}" class="{{ $class }} w-full h-full block"></canvas>


@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
@endonce

@push('script')
    <script>
        let chartInstance_{{ $finalId }};

        function renderChart_{{ $finalId }}(data, options, type) {
            const ctx = document.getElementById('{{ $finalId }}').getContext('2d');
            if (chartInstance_{{ $finalId }}) {
                chartInstance_{{ $finalId }}.destroy();
            }
            chartInstance_{{ $finalId }} = new Chart(ctx, {
                type: type,
                data: data,
                options: options
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderChart_{{ $finalId }}(@json($data), @json($options),
                @json($type));
        });

        // OUVIR O EVENTO PARA ATUALIZAR O GRÁFICO
        window.addEventListener('grafico-atualizar-{{ $finalId }}', function(e) {
            // Exemplo: o backend deve disparar esse evento passando data/options/type atualizados
            renderChart_{{ $finalId }}(e.detail.data, e.detail.options, e.detail.type);
        });
    </script>
@endpush
