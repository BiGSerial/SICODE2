@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
@endphp
<div wire:poll>


    <div class="card">
        <h4 class="card-header">
            MONITOR TAREFAS TEMPO REAL - <span class="fs-6 fw-bold align-middle">({{ date('d/m/Y - H:i') }})</span> -
            <span class="fs-6 fw-bold align-middle">{{ $live_tasks->count() }}</span>
        </h4>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped">

                    <thead>
                        <tr>
                            <th scope="col">Usuario</th>
                            <th scope="col">Empresa</th>
                            <th scope="col">Serviço</th>
                            <th scope="col">Nota</th>
                            <th scope="col">Tempo</th>
                            <th scope="col">Status</th>

                        </tr>
                    </thead>
                    <tbody>
                        @if ($live_tasks->count())
                            @foreach ($live_tasks as $task)
                                <tr>
                                    @php
                                        if (isset($task->User->name)) {
                                            $name = explode(' ', $task->User->name);
                                            $name = $name[0] . ' ' . end($name);
                                        } else {
                                            $name = 'DESCONHECIDO';
                                        }
                                    @endphp
                                    <td class="fw-bold">{{ $name }}</td>
                                    <td>{{ explode(' ', $task->Company->name)[0] }}</td>
                                    <td>{{ $task->Service->service }}</td>
                                    <td>{{ $task->Note->note }}</td>
                                    <td>{{ Carbon::parse($task->att_at)->diffForHumans() }}</td>
                                    {{-- <td>
                                        <span
                                            class="badge {{ Notestatus::status($task->status)->colorbg }}">{{ Notestatus::status($task->status)->status }}</span>
                                    </td> --}}
                                    <td>
                                        @livewire('components.status.statusview', ['status' => $task->status, 'idstatus' => $task->id, 'note_id' => $task->note_id], key($task->id))
                                    </td>

                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
