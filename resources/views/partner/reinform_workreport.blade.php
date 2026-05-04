@extends('layouts.company')

@section('title', 'Reenviar Informe')

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @livewire('partner.forms.reworkreports', ['token' => $token], key('reworkreports-' . $token))
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
