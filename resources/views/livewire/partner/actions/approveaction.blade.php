<div>
    <x-show-loading />
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label for="exampleFormControlTextarea1" class="form-label fw-bold">Inserir
                    Comentário: <span class="text-danger fw-bold">*</span></label>
                <textarea class="form-control border border-secondary" id="exampleFormControlTextarea1" rows="6" wire:model="comment"></textarea>
            </div>
            <button class="btn btn-primary" wire:click.prevent="agree">Concordar</button>
            <button class="btn btn-danger" wire:click.prevent="desagree">Discordar</button>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="confirm" tabindex="-1">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content edp-bg-gray">
                @if ($modal)
                    <div class="modal-header edp-bg-seoweedgreen-100 text-white">
                        <h5 class="modal-title text-center fw-bold">CONFIRMAÇÃO</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-white text-center">
                        <i class="bx bx-question-mark fw-bold " style="font-size: 80px;"></i>
                        <p class="fs-4 fw-bold text-center py-3">{{ $modal['info'] }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" wire:click.prevent="confirm">Confirmar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
