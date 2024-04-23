<div x-data="{ show1: false, show2: false, text: '' }">
    <x-show-loading />

    <div class="card edp-bg-sprucegreen-100">
        <div class="card-body">
            <select name="select-action" id="action" class="form-select" wire:model="action">
                <option value="">Selecione Ação</option>
                <option value="1">Viabilizar</option>
                <option value="2">Contratar</option>
            </select>
        </div>
    </div>

    @if ($action)
        <p class="fw-bold fs-6 my-0 py-0">Comentário: <span class="text-danger fw-bold">*</span></p>
        <p class="mb-2 mb-0 py-0">
            <textarea class="form-control border border-secondary" cols="30" rows="6" wire:model.defer="comment"></textarea>
        </p>
        <button class="btn btn-sm btn-primary"
            wire:click="go_action">{{ $action == 1 ? 'VIABILIZAR' : 'CONTRATAR' }}</button>
    @endif

    <div wire:ignore.self class="modal fade" id="confirm" tabindex="-1">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content edp-bg-gray">
                @if ($confirm_text)
                    <div class="modal-header edp-bg-seoweedgreen-100 text-white">
                        <h5 class="modal-title text-center fw-bold">{{ $confirm_text['action'] }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-white text-center">
                        <i class="bx bx-question-mark fw-bold " style="font-size: 80px;"></i>
                        <p class="fs-4 fw-bold text-center py-3">{{ $confirm_text['message'] }}</p>
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
