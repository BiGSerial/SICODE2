<div>
    <canvas id="myChart" class="chart-canvas" width="400" height="400"></canvas>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('myChart{{ $chartId }}').getContext('2d');
            var myChart = new Chart(ctx, {
                type: '{{ $type }}',
                data: {
                    labels: @json($labels),
                    datasets: [
                        @foreach ($datasets as $dataset)
                            {
                                label: '{{ $dataset['label'] }}',
                                data: @json($dataset['data']),
                                backgroundColor: '{{ $dataset['backgroundColor'] ?? 'rgba(75, 192, 192, 0.2)' }}',
                                borderColor: '{{ $dataset['borderColor'] ?? 'rgba(75, 192, 192, 1)' }}',
                                borderWidth: {{ $dataset['borderWidth'] ?? 1 }}
                            },
                        @endforeach
                    ]
                },
                options: {
                    // Configurações do gráfico
                }
            });
        });
    </script>
</div>
