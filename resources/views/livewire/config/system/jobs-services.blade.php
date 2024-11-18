<div class="container mt-5">
    <div class="log-viewer">
        {{-- <h2 class="mb-4">Laravel Logs</h2> --}}
        {{-- <div class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">
            @foreach ($logs as $log)
                <pre>{{ $log }}</pre>
            @endforeach
        </div> --}}

        <h2 class="mt-5 mb-4">Jobs Pendentes e em Execução</h2>
        @if ($jobs->count() > 0)
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Queue</th>
                        <th>Nome do Job</th>
                        <th>Data e Hora de Criação</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody wire:poll.5s>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>{{ $job->queue }}</td>
                            <td>{{ $job->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($job->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <button class="btn btn-primary btn-sm"
                                    wire:click="restartJob({{ $job->id }})">Reiniciar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="card card-body">
                <h4 class="text-center">SEM JOBS PENDENTES</h4>
            </div>

        @endif


        <h2 class="mt-5 mb-4">Jobs com Falha</h2>
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Queue</th>
                    <th>Mensagem de Erro</th>
                    <th>Data e Hora do Erro</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody wire:poll.5s>
                @foreach ($failedJobs as $failedJob)
                    <tr>
                        <td>{{ $failedJob->id }}</td>
                        <td>{{ $failedJob->queue }}</td>
                        <td>{{ $failedJob->exception }}</td>
                        <td>{{ \Carbon\Carbon::parse($failedJob->failed_at)->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <button class="btn btn-primary btn-sm"
                                wire:click="restartJob({{ $failedJob->id }})">Reiniciar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


        @if (session()->has('message'))
            <div class="alert alert-success mt-3">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger mt-3">
                {{ session('error') }}
            </div>
        @endif
    </div>
</div>
