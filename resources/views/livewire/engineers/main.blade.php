<div>
    <div class="row">
        <div class="col-4">@livewire('engineers.dashboard.viability-pizza', key('viability-pizza'))</div>
        <div class="col-8">@livewire('engineers.dashboard.viability-prod-list', key('viability-list'))</div>

    </div>
    <div class="row">
        <div class="col-4">@livewire('engineers.dashboard.rejected-pizza', key('rejected-pizza'))</div>
        <div class="col-8"></div>
    </div>

    {{-- <script>
        let graph = [];

        function generateGraph(grafico, chartId, datas) {
            if (graph[grafico]) {
                graph[grafico].destroy();
            }

            graph[grafico] = new Chart(chartId, {
                type: 'pie',
                data: datas,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw;
                                }
                            }
                        },
                        datalabels: {
                            formatter: (value, chartId) => {
                                let sum = chartId.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                let percentage = ((value / sum) * 100).toFixed(2) + "%";
                                return percentage;
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            anchor: 'end',
                            align: 'start'
                        }
                    }
                },

            });
        }

        function updateGraph(grafico, data, label) {
            graph[grafico].data.labels = e.detail.labels;
            graph[grafico].data.datasets[0].data = data;
            graph[grafico].update();
        }
    </script> --}}
</div>
