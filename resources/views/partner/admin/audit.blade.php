@extends('layouts.company')

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Auditoria administrativa</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ator</th>
                            <th>Alvo</th>
                            <th>Evento</th>
                            <th>Dados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $event->actor?->name }}</td>
                                <td>{{ $event->target?->name ?: '-' }}</td>
                                <td>{{ $event->event_type }}</td>
                                <td><code>{{ json_encode($event->payload, JSON_UNESCAPED_UNICODE) }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum evento registrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $events->links() }}
        </div>
    </div>
@endsection
