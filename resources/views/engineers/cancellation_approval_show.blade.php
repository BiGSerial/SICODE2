@extends('layouts.padrao_ext')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Engenharia</li>
        <li class="breadcrumb-item"><a href="{{ route('engineers.cancellations.index') }}">Aprovação de Cancelamentos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detalhe</li>
    </ol>
@endsection

@section('menu')
@endsection

@section('content')
    @livewire('engineers.cancellation-approvals.show', ['request' => $request])
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
                    Livewire.emit(e.detail.action)
                } else if (result.dismiss === Swal.DismissReason.cancel) {
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
