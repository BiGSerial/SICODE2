<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '300px' }};">
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let labels = @json($labels);
                let datasets = @json($data);
                const showDataLabels = @json((bool) ($showDataLabels ?? false));

                renderMixedChart('{{ $chartId }}', labels, datasets, '{{ $title ?? 'Gráfico' }}', showDataLabels);
            });

            document.addEventListener('updateMixedChart{{ Str::studly($chartId) }}', function(e) {
                const newLabels = e.detail.labels;
                const newDatasets = e.detail.data;
                const showDataLabels = @json((bool) ($showDataLabels ?? false));
                renderMixedChart('{{ $chartId }}', newLabels, newDatasets, '{{ $title ?? 'Gráfico' }}', showDataLabels);
            });

            function renderMixedChart(chartId, labels, datasets, title, showDataLabels = false) {
                const edpSemantic = {
                    blue: '#263CC8',
                    blueSoft: '#A8B1E9',
                    green: '#225E66',
                    greenSoft: '#91AFB3',
                    red: '#E32C2C',
                    yellow: '#F7D200',
                    marine: '#212E3E',
                    slate: '#7C9599'
                };
                const edpPalette = [
                    edpSemantic.blue,
                    edpSemantic.green,
                    edpSemantic.red,
                    edpSemantic.yellow,
                    edpSemantic.blueSoft,
                    edpSemantic.greenSoft,
                    edpSemantic.marine,
                    edpSemantic.slate
                ];
                let series = [];

                // Calcule a média de cada conjunto de dados
                let annotations = [];
                datasets.forEach((dataset, index) => {
                    if (dataset.data && dataset.data.length > 0) {
                        let sum = dataset.data.reduce((acc, val) => acc + val, 0);
                        let avg = sum / dataset.data.length;

                        // Adicionar anotação da média
                        annotations.push({
                            yaxis: [{
                                y: avg,
                                seriesIndex: index,
                                borderColor: edpSemantic.marine,
                                label: {
                                    borderColor: edpSemantic.marine,
                                    style: {
                                        color: '#fff',
                                        background: edpSemantic.marine
                                    },
                                    text: `Média (${dataset.name})`
                                }
                            }]
                        });
                    }
                    series.push({
                        name: dataset.name,
                        type: dataset.type,
                        data: dataset.data,
                        yaxis: dataset.type === 'line' ? 2 : 1 // Assign line series to Y axis 2
                    });
                });

                var options = {
                    series: series,
                    chart: {
                        height: '{{ $height ?? '300' }}',
                        type: 'line', //Tipo padrão
                        stacked: false,
                        toolbar: { // Adiciona a opção de toolbar
                            show: true,
                        }
                    },
                    stroke: {
                        curve: 'smooth', // Adiciona a linha de conexão
                        width: [0, 2]
                    },
                    title: {
                        text: title,
                        align: 'left'
                    },
                    dataLabels: {
                        enabled: showDataLabels,
                        enabledOnSeries: showDataLabels ? [1] : []
                    },
                    xaxis: {
                        categories: labels,
                    },
                    yaxis: [{
                            axisTicks: {
                                show: true,
                            },
                            axisBorder: {
                                show: true,
                                color: edpSemantic.marine
                            },
                            labels: {
                                style: {
                                    colors: edpSemantic.marine,
                                    fontSize: '12px'
                                }
                            },
                            title: {
                                text: "Eixo Y1",
                                style: {
                                    color: edpSemantic.marine,
                                    fontSize: '14px',
                                }
                            },
                        },
                        {
                            opposite: true,
                            axisTicks: {
                                show: true,
                            },
                            axisBorder: {
                                show: true,
                                color: edpSemantic.slate
                            },
                            labels: {
                                style: {
                                    colors: edpSemantic.slate,
                                    fontSize: '12px'
                                }
                            },
                            title: {
                                text: "Eixo Y2",
                                style: {
                                    color: edpSemantic.slate,
                                    fontSize: '14px'
                                }
                            }
                        },
                    ],
                    tooltip: {
                        fixed: {
                            enabled: true,
                            position: 'topLeft', // topRight, topLeft, bottomRight, bottomLeft
                            offsetY: 30,
                            offsetX: 60
                        },
                    },
                    legend: {
                        horizontalAlign: 'left',
                        offsetX: 40
                    },
                    colors: edpPalette,
                    annotations: {
                        yaxis: annotations.flat() // Use annotations aqui
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 10,
                            columnWidth: '50%',
                        }
                    },
                };

                let chart = window['chart' + capitalizeFirstLetter(chartId)];
                if (chart) {
                    chart.destroy();
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
