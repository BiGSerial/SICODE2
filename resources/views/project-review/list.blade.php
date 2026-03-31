@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">Análise Projeto</li>
            <li class="breadcrumb-item active" aria-current="page">Lista para Analisar</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('project-review.menu')
@endsection

@section('content')
    @livewire('project-review.queue', ['mode' => 'pending'])
@endsection

@push('script')
    <script>
        const tryOpenProjectReviewFromUrl = function() {
            const params = new URLSearchParams(window.location.search);
            const productionId = parseInt(params.get('production') || '', 10);

            if (Number.isInteger(productionId) && productionId > 0) {
                let attempts = 0;
                const maxAttempts = 8;

                const openModal = function() {
                    attempts += 1;
                    Livewire.emitTo('project-review.queue', 'openReviewFromNotification', productionId);
                    Livewire.emit('openReviewFromNotification', productionId);

                    const modalEl = document.getElementById('projectReviewModal');
                    const isOpen = modalEl && modalEl.classList.contains('show');
                    if (!isOpen && attempts < maxAttempts) {
                        setTimeout(openModal, 250);
                    }
                };

                openModal();

                params.delete('production');
                params.delete('focus');

                const nextQuery = params.toString();
                const nextUrl = `${window.location.pathname}${nextQuery ? '?' + nextQuery : ''}${window.location.hash || ''}`;
                window.history.replaceState({}, document.title, nextUrl);
            }
        };

        document.addEventListener('livewire:load', tryOpenProjectReviewFromUrl);
        document.addEventListener('livewire:init', function() {
            setTimeout(tryOpenProjectReviewFromUrl, 120);
        });
    </script>
@endpush
