@extends('layouts.padrao')

@section('breadcrumb')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">Serviços</li>
            <li class="breadcrumb-item active" aria-current="page">{{ $service->service }}</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.menu')
@endsection

@section('content')
    @livewire('services.analises.main', ['service' => $service->uuid])
@endsection

@push('script')
    <script>
        window.addEventListener('focus', function() {
            // console.log('A janela recebeu o foco!');
            livewire.emitTo('services.analises.main', 'refresh_service');

            stopInterval

        });

        window.addEventListener('blur', function() {
            // console.log('A janela recebeu o foco!');
            livewire.emitTo('services.analises.main', 'refresh_service');

            startInterval;

        });

        function startInterval() {
            if (!intervalId) {
                intervalId = setInterval(executarComando, 60000); // Executa a cada 1 minuto
            }
        }

        function stopInterval() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }
        }


        function executeCommand() {
            
            livewire.emitTo('services.analises.main', 'refresh_service');
        }


        window.addEventListener('alertar', function(e) {

            const Confirmation = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            Swal.fire({
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    Livewire.emit(e.detail.action)

                } else if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire(
                        e.detail.cancel_titulo,
                        e.detail.cancel_msg,
                        'success'
                    )
                }
            })
        });
    </script>
@endpush
