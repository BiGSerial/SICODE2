<div>
    <x-show-loading />
    {{-- @dump($totalViabilityStats) --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Situação Viabilidade</h3>
            <button class="btn btn-sm btn-secondary ml-auto" wire:click="upd" wire:loading.attr="disabled">
                <i class="ri-refresh-line" wire:loading.remove></i>
                <span wire:loading wire:target="upd" class="spinner-border spinner-border-sm" role="status"
                    aria-hidden="true"></span>
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="startDate">Data Inicio:</label>
                    <input type="date" id="startDate" class="form-control" wire:model="startDate">
                </div>
                <div class="col-md-4">
                    <label for="endDate">Data Fim:</label>
                    <input type="date" id="endDate" class="form-control" wire:model="endDate">
                </div>
                <div class="col-md-4">
                    <label for="contractor">Empreitera:</label>
                    <select id="contractor" class="form-control" wire:model="company_id">
                        <option value="">Todas</option>
                        @if ($companies)
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div id="chart"></div>
        </div>
    </div>

    {{-- <script src="https://cdn.jsdelivr.net/npm/scichart@3/index.min.js" crossorigin="anonymous"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
    <script>
        let chart = null;

        document.addEventListener('DOMContentLoaded', function() {
            let labels = @json($totalViabilityStats['labels']);
            let datas = @json($totalViabilityStats['data']);

            var options = {
                series: datas,
                chart: {
                    type: 'pie',
                    height: 300,

                },
                labels: labels,
                colors: [
                    '#28FF52', '#212E3E', '#225E66', '#7C9599',
                    '#7EFF97', '#646D78', '#5b797e', '#648E94', '#A3B5B8',
                    '#6D32FF', '#0CD3F8', '#263CC8', '#A784FF', '#6DE5FB',
                ],
                dropShadow: {
                    enabled: true,
                    top: 0,
                    left: 0,
                    blur: 3,
                    opacity: 0.5
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center'
                }
            };

            chart = new ApexCharts(document.querySelector("#chart"), options);
            chart.render();
        });

        window.addEventListener('resize', function() {
            const chartElement = document.querySelector("#chart");
            chartElement.style.minHeight = '250px';
            chartElement.style.minWidth = '250px';
        });

        document.addEventListener('updateGraphXX3', function(e) {
            const newLabels = e.detail.labels; // Novos labels do evento
            const newData = e.detail.data; // Novos dados do evento

            // Atualiza as labels e os dados do gráfico
            chart.updateOptions({
                labels: newLabels
            });
            chart.updateSeries(newData);
        });
    </script>
</div>
