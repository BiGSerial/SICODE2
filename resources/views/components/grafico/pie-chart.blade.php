<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '100%' }};">
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
                const edpPalette = [
                    '#263CC8', '#225E66', '#E32C2C', '#F7D200',
                    '#A8B1E9', '#91AFB3', '#EDD5D3', '#FFF1BE',
                    '#212E3E', '#143F47', '#7C9599', '#0CD3F8'
                ];

                if (datas.length === 0) {
                    document.getElementById('msg-{{ $chartId }}').style.display = 'block';
                    document.getElementById('{{ $chartId }}').style.display = 'none';

                } else {
                    document.getElementById('msg-{{ $chartId }}').style.display = 'none';
                    document.getElementById('{{ $chartId }}').style.display = 'block';
                }

                var options = {
                    series: datas,
                    chart: {
                        type: 'donut',
                        height: '450px', // Aumentar o tamanho do gráfico
                        width: '100%'
                    },
                    labels: labels,
                    colors: edpPalette,
                    dataLabels: {
                        enabled: showDataLabels
                    },
                    dropShadow: {
                        enabled: true,
                        blur: 5,
                        left: 1,
                        top: 1,
                        opacity: 0.2
                    },
                    legend: {
                        position: 'bottom', // Mover a legenda para a parte inferior
                        horizontalAlign: 'center',
                        fontSize: '12px', // Reduzir o tamanho da fonte da legenda
                        itemMargin: {
                            horizontal: 5
                        }, // Ajustar o espaçamento entre os itens
                    }
                };

                window.chart{{ Str::studly($chartId) }} = new ApexCharts(document.querySelector("#{{ $chartId }}"),
                    options);
                window.chart{{ Str::studly($chartId) }}.render();
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

                window.chart{{ Str::studly($chartId) }}.updateOptions({
                    labels: newLabels
                });
                window.chart{{ Str::studly($chartId) }}.updateSeries(newData);
            });
        </script>
    @endpush
</div>
