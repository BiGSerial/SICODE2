<div>
    <x-show-loading />
    {{-- @dump($totalViabilityStats) --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Rejeição Motivos</h3>
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

            <div id="chart2"></div>
        </div>
    </div>

    {{-- <script src="https://cdn.jsdelivr.net/npm/scichart@3/index.min.js" crossorigin="anonymous"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
    <script>
        let chart2 = null;

        document.addEventListener('DOMContentLoaded', function() {
            let labels = @json($totalRejectstasts['labels']);
            let datas = @json($totalRejectstasts['data']);

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

            chart2 = new ApexCharts(document.querySelector("#chart2"), options);
            chart2.render();
        });

        document.addEventListener('updateGraphXX4', function(e) {
            const newLabels = e.detail.labels; // Novos labels do evento
            const newData = e.detail.data; // Novos dados do evento

            // Atualiza as labels e os dados do gráfico
            chart2.updateOptions({
                labels: newLabels
            });
            chart2.updateSeries(newData);
        });
    </script>
</div>
