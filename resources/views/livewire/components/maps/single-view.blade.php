<div>
    <div wire:ignore.self class="modal fade" id="transfer_modal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                @if ($production)
                    <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Mapa Obra {{ $production->Note->note }}</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
