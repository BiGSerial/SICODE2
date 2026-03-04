@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('partner.main.viability') }}">Partner</a></li>
                <li class="breadcrumb-item active" aria-current="page">Buscar Notas/OV</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @livewire('reports.search', key('report-search-partner'))
@endsection

@push('script')
    <script>
        window.addEventListener('alertar', function(e) {
            const confirmation = Swal.mixin({
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
                    Livewire.emit(e.detail.action, e.detail.chave);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire(e.detail.cancel_titulo, e.detail.cancel_msg, 'success');
                }
            });
        });
    </script>
@endpush
