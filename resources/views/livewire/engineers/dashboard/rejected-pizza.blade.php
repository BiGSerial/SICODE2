<div wire:key='rejected_pizza_stats'>
    <x-show-loading />
    {{-- @dump($totalViabilityStats) --}}
    <div class="card" wire:ignore.self>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Situação Viabilidade <span class="fs-6 fw-bold">(De
                    {{ date('d/m/Y', strToTime($startDate)) }} à
                    {{ date('d/m/Y', strToTime($endDate)) }})</span></h3>
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

            <canvas id="rejectedChart" class="text-center" wire:ignore style="width: 100px; height: 100px"></canvas>
        </div>
    </div>

    {{-- <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script> --}}
    <script>
        let rejectedChart;

        document.addEventListener('livewire:load', function() {
            createGraph();
        });



        function createGraph() {

            // if (rejectedChart) {
            //     rejectedChart.destroy();
            // }

            let labels1 = @json($totalRejectstasts['labels']);
            let datas1 = @json($totalRejectstasts['data']);
            const data1 = {
                labels: labels1,
                datasets: [{
                    label: 'Rejected Stats',
                    data: datas1,
                    backgroundColor: [
                        '#28FF52', '#225E66', '#7C9599',
                        '#7EFF97', '#646D78', '#5b797e', '#648E94', '#A3B5B8',
                        '#6D32FF', '#263CC8', '#A784FF', '#7D8ADE'
                    ],
                }]
            };

            const ctx1 = document.getElementById('rejectedChart').getContext('2d');

            generateGraph('rejectedChart', ctx1, data1);
        }

        //     const ctx1 = document.getElementById('rejectedChart').getContext('2d');
        //     rejectedChart = new Chart(ctx1, {
        //         type: 'pie',
        //         data: data1,
        //         options: {
        //             responsive: true,
        //             plugins: {
        //                 legend: {
        //                     position: 'top',
        //                 },
        //                 tooltip: {
        //                     callbacks: {
        //                         label: function(tooltipItem) {
        //                             return tooltipItem.label + ': ' + tooltipItem.raw;
        //                         }
        //                     }
        //                 },
        //                 datalabels: {
        //                     formatter: (value, ctx1) => {
        //                         let sum = ctx1.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
        //                         let percentage = ((value / sum) * 100).toFixed(2) + "%";
        //                         return percentage;
        //                     },
        //                     color: '#fff',
        //                     font: {
        //                         weight: 'bold',
        //                         size: 14
        //                     },
        //                     anchor: 'end',
        //                     align: 'start'
        //                 }
        //             }
        //         },

        //     });
        // }

        document.addEventListener('updateGraphXX4', function(e) {
            console.log(e.detail.data);

            // Atualizar as labels e dados do gráfico
            // rejectedChart.data.labels = e.detail.labels;
            // rejectedChart.data.datasets[0].data = e.detail.data;

            // Atualizar o gráfico com os novos dados
            // rejectedChart.update();

            updateGraph('rejectedChart', e.detail.data, e.detail.labels);
        });
    </script>


</div>
