<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '300px' }};">
    </div>
    <div class="card" style="display: none;" id="msg-{{ $chartId }}">
        <div class="card-body">
            <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
        </div>
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let labels = @json($labels);
                let datas = @json($dataset);
                const showDataLabels = @json((bool) ($showDataLabels ?? false));

                if (datas.length === 0) {
                    document.getElementById('msg-{{ $chartId }}').style.display = 'block';
                    document.getElementById('{{ $chartId }}').style.display = 'none';

                } else {
                    document.getElementById('msg-{{ $chartId }}').style.display = 'none';
                    document.getElementById('{{ $chartId }}').style.display = 'block';
                }
                // Se houver dados, calcula a média e define a annotation; caso contrário, deixa vazio.
                renderChart('{{ $chartId }}', labels, datas, '{{ $title ?? 'Série 1' }}', showDataLabels);
            });

            document.addEventListener('updateGraph{{ Str::studly($chartId) }}', function(e) {
                const newLabels = e.detail.labels;
                const newData = e.detail.data;



                if (newData.length === 0) {
                    document.getElementById('msg-{{ $chartId }}').style.display = 'block';
                    document.getElementById('{{ $chartId }}').style.display = 'none';

                } else {
                    document.getElementById('msg-{{ $chartId }}').style.display = 'none';
                    document.getElementById('{{ $chartId }}').style.display = 'block';
                }

                const showDataLabels = @json((bool) ($showDataLabels ?? false));
                renderChart('{{ $chartId }}', newLabels, newData, '{{ $title ?? 'Série 1' }}', showDataLabels);
            });

            function renderChart(chartId, labels, datas, title, showDataLabels = false) {
                const edpSemantic = {
                    blue: '#263CC8',
                    blueSoft: '#A8B1E9',
                    green: '#225E66',
                    greenSoft: '#91AFB3',
                    red: '#E32C2C',
                    redSoft: '#EDD5D3',
                    yellow: '#F7D200',
                    yellowSoft: '#FFF1BE',
                    marine: '#212E3E'
                };

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
                            borderColor: edpSemantic.red,
                            label: {
                                borderColor: edpSemantic.red,
                                style: {
                                    color: '#fff',
                                    background: edpSemantic.red
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
                        edpSemantic.blue,
                        edpSemantic.green,
                        edpSemantic.red,
                        edpSemantic.yellow,
                        edpSemantic.blueSoft,
                        edpSemantic.greenSoft,
                        edpSemantic.redSoft,
                        edpSemantic.yellowSoft,
                        edpSemantic.marine
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
                    dataLabels: {
                        enabled: showDataLabels
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
