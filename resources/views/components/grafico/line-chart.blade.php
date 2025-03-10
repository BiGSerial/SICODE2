<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '300px' }};">
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let labels = @json($labels);
                let datas = @json($dataset);

                // Se houver dados, calcula a média e define a annotation; caso contrário, deixa vazio.
                let annotationsConfig = {};
                if (datas.length) {
                    let avg = datas.reduce((sum, value) => sum + value, 0) / datas.length;
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
                        name: '{{ $title ?? 'Série 1' }}',
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

                window.chart{{ Str::studly($chartId) }} = new ApexCharts(document.querySelector("#{{ $chartId }}"),
                    options);
                window.chart{{ Str::studly($chartId) }}.render();
            });

            document.addEventListener('updateGraph{{ Str::studly($chartId) }}', function(e) {
                const newLabels = e.detail.labels;
                const newData = e.detail.data;

                let annotationsConfig = {};
                if (newData.length) {
                    let avgNew = newData.reduce((sum, value) => sum + value, 0) / newData.length;
                    annotationsConfig = {
                        yaxis: [{
                            y: avgNew,
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

                window.chart{{ Str::studly($chartId) }}.updateOptions({
                    xaxis: {
                        categories: newLabels
                    },
                    annotations: annotationsConfig
                });
                window.chart{{ Str::studly($chartId) }}.updateSeries([{
                    name: '{{ $title ?? 'Série 1' }}',
                    data: newData
                }]);
            });
        </script>
    @endpush
</div>
