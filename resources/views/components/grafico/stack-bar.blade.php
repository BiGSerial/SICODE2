<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '300px' }};">
    </div>
    <div class="card" style="display: none;" id="msg-{{ $chartId }}">
        <div class="card-body">
            <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
        </div>
    </div>

    @push('script')
        {{-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const showDataLabels = @json((bool) ($showDataLabels ?? false));
                var options = {
                    series: [{
                        name: '{{ $dataset1Label }}',
                        data: @json($dataset1Data)
                    }, {
                        name: '{{ $dataset2Label }}',
                        data: @json($dataset2Data)
                    }],
                    chart: {
                        type: 'bar',
                        stacked: true, // Habilita o empilhamento das colunas
                        height: {{ $height ?? '300' }},
                        // Removemos a propriedade width para que o gráfico preencha toda a largura do elemento contêiner.
                        toolbar: {
                            show: false // Oculta a barra de ferramentas
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        },
                    },
                    dataLabels: {
                        enabled: showDataLabels
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: @json($labels),
                    },
                    yaxis: {
                        title: {
                            text: '{{ $yAxisTitle ?? '' }}',
                        }
                    },
                    fill: {
                        opacity: 1
                    },
                    colors: ['#263CC8', '#225E66', '#E32C2C', '#F7D200', '#A8B1E9', '#91AFB3'],
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val;
                            }
                        }
                    },
                    title: {
                        text: '{{ $title ?? '' }}',
                        align: 'center'
                    }
                };

                window.chart{{ Str::studly($chartId) }} = new ApexCharts(document.querySelector("#{{ $chartId }}"),
                    options);
                window.chart{{ Str::studly($chartId) }}.render();
            });

            Livewire.on('updateGraph1{{ Str::studly($chartId) }}', event => {
                const newLabels = event.labels;
                const newData1 = event.dataset1Data; // Novos dados para o dataset 1
                const newData2 = event.dataset2Data; // Novos dados para o dataset 2

                console.log(newLabels, newData1, newData2);


                window.chart{{ Str::studly($chartId) }}.updateOptions({
                    xaxis: {
                        categories: newLabels
                    }
                });

                window.chart{{ Str::studly($chartId) }}.updateSeries([{
                    name: '{{ $dataset1Label }}',
                    data: newData1
                }, {
                    name: '{{ $dataset2Label }}',
                    data: newData2
                }]);
            });
        </script>
    @endpush
</div>
