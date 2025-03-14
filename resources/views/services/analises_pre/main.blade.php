@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $service->service }}</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    {{-- @include('services.analises_pre.menu') --}}
    @include('services.menu')
@endsection

@section('content')
    @livewire('services.analises_pre.main', ['service' => $service->uuid])
@endsection

@push('script')
    <script>
        let intervalId = null; // Declare intervalId outside the event listeners to be accessible to both.

        window.addEventListener('focus', function() {
            // console.log('A janela recebeu o foco!');
            livewire.emitTo('services.analises_pre.main', 'refresh_service');

            stopInterval(); // Call stopInterval directly, not assign it.
        });

        window.addEventListener('blur', function() {
            // console.log('A janela perdeu o foco!');
            livewire.emitTo('services.analises_pre.main', 'refresh_service');

            startInterval(); // Call startInterval directly, not assign it.

        });

        function startInterval() {
            if (!intervalId) {
                intervalId = setInterval(executeCommand, 60000); // Executa a cada 1 minuto
                console.log('Interval started'); // Add a log message
            }
        }

        function stopInterval() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
                console.log('Interval stopped'); // Add a log message
            }
        }

        function executeCommand() {
            const now = () => new Date().toLocaleTimeString(); // Correctly define now()
            console.log('Executado:' + now());
            livewire.emitTo('services.analises_pre.main', 'refresh_service');
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
