@extends('layouts.login_new')



@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">
@endpush

@section('content')
    @livewire('password.change')
@endsection

@push('script')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
    <script>
        window.addEventListener('swal', function(e) {
            Swal.fire(e.detail);
        });

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
