@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Dispatch</li>
                <li class="breadcrumb-item">Reclamações</li>
                <li class="breadcrumb-item active" aria-current="page">View</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('protest.dispatch.menu')
@endsection

@section('content')
    @livewire('protests.dispatch.view')
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

            const swalConfig = {
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            };

            const hasInputType = Object.prototype.hasOwnProperty.call(e.detail, 'inputType');
            if (hasInputType) {
                swalConfig.input = e.detail.inputType || 'select';
                swalConfig.inputOptions = e.detail.inputOptions || {};
                swalConfig.inputValue = e.detail.inputValue || '';
                swalConfig.inputPlaceholder = e.detail.inputPlaceholder || 'Nao informado';
                swalConfig.inputLabel = e.detail.inputLabel || '';

                if (e.detail.requireInput) {
                    swalConfig.inputValidator = (value) => {
                        if (!value) {
                            return 'Selecione uma opção para continuar.';
                        }
                    };
                }
            }

            Swal.fire(swalConfig).then((result) => {
                if (result.isConfirmed) {

                    if (swalConfig.input) {
                        Livewire.emit(e.detail.action, result.value)
                    } else {
                        Livewire.emit(e.detail.action)
                    }

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
