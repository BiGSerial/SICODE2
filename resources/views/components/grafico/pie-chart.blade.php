<div>
    <div id="{{ $chartId }}" style="width:100%; max-width:{{ $width ?? '100%' }}; height:{{ $height ?? '100%' }};">
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let labels = @json($labels);
                let datas = @json($dataset);

                var options = {
                    series: datas,
                    chart: {
                        type: 'pie',
                        height: '{{ $height ?? '100%' }}',
                        width: '{{ $width ?? '100%' }}'
                    },
                    labels: labels,
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
                    }
                };

                window.chart{{ Str::studly($chartId) }} = new ApexCharts(document.querySelector("#{{ $chartId }}"), options);
                window.chart{{ Str::studly($chartId) }}.render();
            });

            document.addEventListener('updateGraph{{ Str::studly($chartId) }}', function(e) {
                const newLabels = e.detail.labels;
                const newData = e.detail.data;

                window.chart{{ Str::studly($chartId) }}.updateOptions({
                    labels: newLabels
                });
                window.chart{{ Str::studly($chartId) }}.updateSeries(newData);
            });
        </script>
    @endpush
</div>
