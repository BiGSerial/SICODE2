@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}">Home</a>
            </li>
            <li class="breadcrumb-item">
                <a href="#">Engenharia</a>
            </li>
            <li class="breadcrumb-item">
                <a href="#">Analise Projetos</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                Dashboard
            </li>
        </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('engineers.menu', key('engineers-menu'))
@endsection

@section('content')
    @livewire('engineers.analises.analise-dashboard', key('analise-dashboard'))
@endsection


@push('script')
    <script>
        // window.addEventListener('focus', function() {
        //     // console.log('A janela recebeu o foco!');
        //     livewire.emitTo('responsible.viab-control', 'refresh_list');



        // });

        // window.addEventListener('blur', function() {
        //     // console.log('A janela recebeu o foco!');
        //     livewire.emitTo('responsible.viab-control', 'refresh_list');



        // });

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
