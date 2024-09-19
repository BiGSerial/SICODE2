@php
    use Carbon\Carbon;

@endphp
<div wire:poll.30s>
    @if ($logUpdates)
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Log de Atualização</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-9">
                        <select class="form-select form-select-sm" aria-label="Small select example"
                            wire:model.defer="singleTask">
                            <option value="" selected>Todos</option>
                            @foreach ($tasks as $task)
                                <option value="{{ $task }}">{{ $task }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <button class="btn btn-sm btn-primary" wire:click.prevent="selectTask">Selecionar</button>
                    </div>
                </div>
            </div>

            @if (!$logs->isEmpty())
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">Tarefa</th>
                            <th class="text-center">Criados</th>
                            <th class="text-center">Atualizados</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Inicio</th>
                            <th class="text-center">Fim</th>
                            <th class="text-center">Tempo</th>
                            <th class="text-center">Executado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-center">{{ $log['tarefa'] }}</td>
                                <td class="text-center">{{ $log['created'] }}</td>
                                <td class="text-center">{{ $log['updated'] }}</td>
                                <td class="text-center">{{ $log['total'] }}</td>
                                @php
                                    $start = Carbon::parse($log['date_inicio']);
                                    $end = Carbon::parse($log['date_fim']);
                                    $difference = '';

                                    // Verifica a diferença de tempo e exibe no formato desejado
                                    if ($start && $end) {
                                        if ($start->diffInSeconds($end) < 60) {
                                            $difference = $start->diffInSeconds($end) . ' seg';
                                        } elseif ($start->diffInMinutes($end) < 60) {
                                            $difference = $start->diffInMinutes($end) . ' min';
                                        } elseif ($start->diffInHours($end) < 24) {
                                            $difference = $start->diffInHours($end) . ' horas';
                                        } else {
                                            $difference = $start->diffInDays($end) . ' dias';
                                        }
                                    } else {
                                        $difference = 'Tempo não disponível';
                                    }
                                @endphp
                                <td class="text-center">{{ $start ? $start->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                <td class="text-center">{{ $end ? $end->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                <td class="text-center">{{ $difference }}</td>
                                <td class="text-center">{{ $end ? $end->diffForHumans() : 'N/A' }}</td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>

                <!-- Links de paginação -->
                <div class="ms-2">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
