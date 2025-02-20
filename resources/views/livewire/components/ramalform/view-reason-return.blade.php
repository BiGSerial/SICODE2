@php

    use Carbon\Carbon;
@endphp

<div>

    <div wire:ignore.self class="modal fade" id="ramalRejectedViewCategory" tabindex="-1"
        aria-labelledby="workRejectedViewCategoryLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="workRejectedViewCategoryLabel">Motivo Retorno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($workReport && $workReport->ReturnRamal->count())
                        @foreach ($workReport->ReturnRamal as $index => $returnWork)
                            <div class="card mb-3 shadow-sm border-1">
                                <div
                                    class="card-header bg-light text-secondary fw-bold d-flex align-items-center justify-content-between">
                                    Retorno #{{ $index + 1 }}
                                    <span class="badge bg-primary text-white">{{ $returnWork->category }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">Descrição:</h6>
                                        <p class="card-text fst-italic">
                                            {{ $returnWork->text_obs ?: 'Nenhuma descrição fornecida.' }}
                                        </p>
                                    </div>

                                    <div class="mb-2">
                                        <h6 class="text-muted mb-1">Responsável:</h6>
                                        <p class="card-text">
                                            {{ $returnWork->User->name }} (<a
                                                href="mailto:{{ $returnWork->User->email }}">{{ $returnWork->User->email }}</a>)
                                        </p>
                                    </div>

                                    <div class="mb-0">
                                        <h6 class="text-muted mb-1">Data:</h6>
                                        <p class="card-text fw-bold">
                                            {{ Carbon::parse($returnWork->created_at)->format('d/m/Y H:i:s') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">Nenhum retorno encontrado.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>





</div>
