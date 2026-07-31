@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item" aria-current="page">{{ $service->service }}</li>
                <li class="breadcrumb-item active" aria-current="page">Obras Liberadas</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.oexterno.menu')
@endsection

@section('content')
    @livewire('services.oexterno.released-works', ['service' => $service->uuid])
@endsection

@push('script')
    <script>
        function escapeReleasedWorkText(value) {
            return String(value).replace(/[&<>"']/g, function(match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[match];
            });
        }

        window.addEventListener('confirm-external-organ-rejection', function(e) {
            const note = escapeReleasedWorkText(e.detail.note || '---');

            Swal.fire({
                title: 'Recusar Órgão Externo?',
                html: 'A nota <strong>' + note + '</strong> será removida da fila de OE e liberada para aprovação de projeto.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, recusar OE',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('rejectExternalOrganRequirement', e.detail.releaseId);
                }
            });
        });
    </script>
@endpush
