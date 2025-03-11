<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '300px' }};">
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let labels = @json($labels);
                let datas = @json($dataset);
                renderChart('{{ $chartId }}', labels, datas, '{{ $title ?? 'Série 1' }}');
            });

            document.addEventListener('updateGraph{{ Str::studly($chartId) }}', function(e) {
                const newLabels = e.detail.labels;
                const newData = e.detail.data;
                renderChart('{{ $chartId }}', newLabels, newData, '{{ $title ?? 'Série 1' }}');
            });

            function renderChart(chartId, labels, datas, title) {
                // Se houver dados, calcula a média e define a annotation; caso contrário, deixa vazio.
                let annotationsConfig = {};
                if (datas && datas.length > 0) {
                    let sum = 0;
                    for (let i = 0; i < datas.length; i++) {
                        sum += parseFloat(datas[i]); // Garante que os valores sejam números
                    }
                    let avg = sum / datas.length;

                    annotationsConfig = {
                        yaxis: [{
                            y: avg,
                            borderColor: '#FF0000',
                            label: {
                                borderColor: '#FF0000',
                                style: {
                                    color: '#fff',
                                    background: '#FF0000'
                                },
                                text: 'Média'
                            }
                        }]
                    };
                }

                var options = {
                    series: [{
                        name: title,
                        data: datas
                    }],
                    chart: {
                        type: 'line',
                        height: '{{ $height ?? '300' }}',
                        width: '{{ $width ?? '100%' }}'
                    },
                    xaxis: {
                        categories: labels
                    },
                    colors: [
                        '#28FF52', '#212E3E', '#8B0000', '#0000FF',
                        '#FF00FF', '#FFA500', '#00FFFF', '#800080',
                        '#FF0000', '#6D32FF', '#0CD3F8', '#FF1493',
                        '#FFFF00', '#A52A2A', '#FF5733', '#EE82EE',
                        '#40E0D0', '#B22222', '#4B0082', '#FF9933'
                    ],
                    dropShadow: {
                        enabled: true,
                        blur: 5,
                        left: 1,
                        top: 1,
                        opacity: 0.2
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'center'
                    },
                    annotations: annotationsConfig
                };

                let chart = window['chart' + capitalizeFirstLetter(chartId)];
                if (chart) {
                    chart.destroy(); // Destrói o gráfico existente antes de renderizar um novo
                }

                window['chart' + capitalizeFirstLetter(chartId)] = new ApexCharts(document.querySelector("#" + chartId),
                    options);
                window['chart' + capitalizeFirstLetter(chartId)].render();

                function capitalizeFirstLetter(string) {
                    return string.charAt(0).toUpperCase() + string.slice(1);
                }
            }
        </script>
    @endpush
</div>
