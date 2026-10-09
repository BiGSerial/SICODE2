@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>
    @push('script')
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true,
            });
        </script>
    @endpush
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Não foi possível concluir a ação.</strong>
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
