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

    <script src="https://cdn.jsdelivr.net/npm/scichart@3/index.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let labels = @json($totalViabilityStats['labels']);
            let datas = @json($totalViabilityStats['data']);

            const data = [{
                type: 'pie',
                labels: labels,
                values: datas,
                textinfo: 'label+percent',
                insidetextorientation: 'radial'
            }];

            const layout = {
                title: 'Viability Stats',
                height: 400,
                width: 400
            };

            Plotly.newPlot('viabilityChart', data, layout);
        });

        document.addEventListener('updateGraphXX3', function(e) {
            console.log(e.detail.data);

            const update = {
                labels: [e.detail.labels],
                values: [e.detail.data]
            };

            Plotly.restyle('viabilityChart', update);
        });
    </script>
</div>
