@extends('layouts.company')

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @include('partner.admin._styles')

    <div class="partner-admin-shell">
        <div class="partner-admin-header">
            <div>
                <div class="partner-admin-eyebrow">{{ $managedCompany?->name ?? 'Empresa parceira' }}</div>
                <h1 class="partner-admin-title">Auditoria administrativa</h1>
                <p class="partner-admin-subtitle">Eventos administrativos</p>
                <div class="partner-admin-hero-meta">
                    <div class="partner-admin-hero-chip">
                        <i class="ri-history-line"></i>
                        <strong>{{ $events->total() }}</strong>
                        <span>eventos</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="partner-admin-panel">
            <div class="partner-admin-panel-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle partner-admin-table">
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
                                <td><code class="partner-admin-code">{{ json_encode($event->payload, JSON_UNESCAPED_UNICODE) }}</code></td>
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
    </div>
@endsection
