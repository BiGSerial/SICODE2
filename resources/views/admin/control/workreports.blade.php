@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Administracao</li>
                <li class="breadcrumb-item">Controle de Dados</li>
                <li class="breadcrumb-item active" aria-current="page">Controle WorkReports</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('admin.control.menu')
@endsection

@section('content')
    @livewire('admin.control.work-report-list')
@endsection

@push('script')
    <script>
        window.addEventListener('alertar', function(e) {
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
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire(e.detail.cancel_titulo, e.detail.cancel_msg, 'success')
                }
            })
        });
    </script>
@endpush
