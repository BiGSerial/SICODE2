@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item" aria-current="page">{{ $service->service }}</li>
                <li class="breadcrumb-item active" aria-current="page">Acompanhamento</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.pagamento.menu')
@endsection

@section('content')
    {{-- @livewire(, ['service' => $service->uuid]) --}}
    @livewire('services.payment.accompany.main', ['service' => $service->uuid])
@endsection

@push('script')
    <script>
        // Função para copiar texto para a área de transferência
        function copyToClipboard(elementId) {
            // Seleciona o conteúdo do elemento
            var element = document.getElementById(elementId);
            element.select();

            // Copia o conteúdo para a área de transferência
            document.execCommand('copy');
        }

        // Adiciona um ouvinte de eventos ao documento usando a delegação de eventos
        document.addEventListener('click', function(event) {
            // Verifica se o elemento clicado possui a classe .copyButton dentro do modal
            if (event.target.classList.contains('copyButton')) {
                // Obtém o ID do elemento
                var textAreaId = event.target.getAttribute('data-id');

                // Chama a função para copiar o texto
                copyToClipboard(textAreaId);

                // Exibe uma mensagem ou executa outra ação se necessário
                Livewire.emit('getCopy', 'Texto Copiado para Memória.');
            }
        });
    </script>
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
@endpush
