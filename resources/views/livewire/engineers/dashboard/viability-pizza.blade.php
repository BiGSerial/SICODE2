<div>
    <x-show-loading />
    {{-- @dump($totalViabilityStats) --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Situação Viabilidade</h3>
            <button class="btn btn-sm btn-secondary ml-auto" wire:click="$refresh" wire:loading.attr="disabled">
                <i class="ri-refresh-line" wire:loading.remove></i>
                <span wire:loading wire:target="$refresh" class="spinner-border spinner-border-sm" role="status"
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

            <canvas id="viabilityChart" style="max-width: 400px; max-heigh: 400px;" wire:ignore></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script>
        let viabilityChart; // Declare the chart outside so it's accessible globally

        document.addEventListener('DOMContentLoaded', function() {
            createGraph();
        });

        function createGraph() {

            let labels = @json($totalViabilityStats['labels']);
            let datas = @json($totalViabilityStats['data']);
            const data = {
                labels: labels,
                datasets: [{
                    label: 'Viability Stats',
                    data: datas,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FFCD56', '#C9CBCF', '#36A2EB', '#FF6384'
                    ],
                }]
            };

            const ctx = document.getElementById('viabilityChart').getContext('2d');
            viabilityChart = new Chart(ctx, { // Store the chart object in a global variable
                type: 'pie',
                data: data,
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

                    }
                },

            });
        }




        document.addEventListener('updateGraphXX3', function(e) {

            console.log(e.detail.data);

            // alert(data);
            // Update the chart's labels and data
            viabilityChart.data.labels = e.detail.labels;
            viabilityChart.data.datasets[0].data = e.detail.data;

            // Update the chart with the new data
            viabilityChart.update();
        });
    </script>
</div>
