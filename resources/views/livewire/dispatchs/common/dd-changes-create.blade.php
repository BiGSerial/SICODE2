<div>
    @php
        use App\Helpers\FileIcon;
    @endphp
    <div>
        <x-show-loading />
        <div wire:ignore.self class="modal fade" id="openDdChangesCreateModal" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content  edp-bg-stategrey-50">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h5 class="modal-title" id="exampleModalLabel">Associação de DD em Massa para
                            {{ $service?->service }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control m-0" rows="15" placeholder="Ex: <NOTE/OV> <DD>" wire:model.defer="dd_text"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" wire:click="assignDD"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50">
                            <span wire:loading.remove wire:target="assignDD">Associar em Massa</span>
                            <span wire:loading wire:target="assignDD">
                                <span class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true"></span>
                                Processando...
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>


    </div>

</div>
