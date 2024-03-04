@php
    use Carbon\Carbon;
@endphp
<div>
    <div wire:ignore.self class="modal fade" id="priorityModal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog">
            @if ($productions)
                <div class="modal-content edp-bg-gray">
                    <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Prioridade
                            {{ $productions && $productions->count() > 1 ? 'PRIORIDADE EM MASSA ' . $productions->count() . ' NOTAS/OVS' : $productions[0]->load('Note')->Note->note }}
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h4 class="text-center">Definir Prioridade de Produção.</h4>
                        <p
                            class="text-justify p-2 border border-1 border-secondary edp-bg-sprucegreen-100 edp-text-verde-dark my-2">
                            Descreva de forma clara e objetiva a motivação para priorização da NOTA/OV. Essa informação
                            estará disponível somente para essa produção
                            enquanto esta se mantiver ativa, exceto quando a priorização for global. Todas as
                            priorizações ficarão registradas para as NOTA/OV e suas Produções.
                        </p>
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Motivo: <span
                                    class="text-danger fw-bold">*</span></label>
                            <textarea type="text" class="form-control" wire:model.defer="priority" placeholder="Informe o motivo" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" wire:click="givePriority"
                            wire:loading.attr="disabled">
                            <span wire:loading class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true"></span>
                            <span wire:loading.remove>
                                Priorizar
                            </span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="infoPrioridade" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Prioridade
                        {{ $infoPriority ? $infoPriority->load('Note')->Note->note : '' }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 class="text-center">Informação da Prioridade</h4>
                    <div class="card">
                        <div class="card-body">
                            <p class="text-justify my-2">
                                {{ $this->infoPriority ? $this->infoPriority->prioridade : '' }}
                            </p>
                        </div>
                        <div class="card-footer">
                            <span>Usuário: </span> <span
                                class="fw-bold">{{ $this->infoPriority ? $this->infoPriority->load('User')->User->name : '---' }}
                                -
                                {{ $this->infoPriority ? Carbon::parse($this->infoPriority->created_at)->format('d/m/Y H:i:s') : '--:--' }}</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>

                </div>
            </div>
        </div>
    </div>

</div>
