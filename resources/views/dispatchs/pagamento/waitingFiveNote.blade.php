@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Dispatch</li>
                <li class="breadcrumb-item" aria-current="page">Aguardando D5</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $service->service }}</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    {{-- @include('services.analises_pre.menu') --}}
    {{-- @include('dispatchs.menu') --}}
    @include('dispatchs.pagamento.menu')
@endsection

@section('content')
    {{-- @livewire('dispatchs.payment.stack', ['service' => $service->uuid]) --}}
    @livewire('dispatchs.payment.waiting-five-notes', ['service' => $service->uuid])
@endsection

@push('script')
    <script>
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

                    Livewire.emit(e.detail.action, e.detail.chave)

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
