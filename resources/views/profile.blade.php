@extends('layouts.padrao_ext')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active">Profile</li>
                <li class="breadcrumb-item active">{{ $user->name }}</li>
            </ol>
        </ol>
    </nav>
@endsection


@section('menu')
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">

        </ul>

    </aside>
@endsection

@section('content')
    @livewire('home.profile', ['user' => $user])
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

    <script>
        window.addEventListener('copyToBoard', function(e) {
            copyToClipboard();
        });



        function copyToClipboard() {
            const textToCopy = document.getElementById('clipboard-data').innerText;
            const textarea = document.createElement('textarea');
            textarea.textContent = textToCopy;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);


        }
    </script>
@endpush
