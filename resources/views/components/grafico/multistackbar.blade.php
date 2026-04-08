<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '300px' }};">
    </div>

    @push('script')
        {{-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const showDataLabels = @json((bool) ($showDataLabels ?? false));
                var options = {
                    series: @json($datasets),
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
                    colors: ['#263CC8', '#225E66', '#E32C2C', '#F7D200', '#A8B1E9', '#91AFB3', '#EDD5D3', '#FFF1BE'],
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

            Livewire.on('updateGraph2{{ Str::studly($chartId) }}', event => {
                const newLabels = event.labels;
                const newDatasets = event.datasets;

                console.log(newLabels, newDatasets);


                window.chart{{ Str::studly($chartId) }}.updateOptions({
                    xaxis: {
                        categories: newLabels
                    }
                });

                window.chart{{ Str::studly($chartId) }}.updateSeries(newDatasets);
            });
        </script>
    @endpush
</div>
