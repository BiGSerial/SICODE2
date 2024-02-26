<div>
    <div wire:ignore.self class="modal fade" id="action_viability" tabindex="-1" aria-labelledby="action_viability"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">

            @if ($viability)
                <div class="modal-content edp-bg-stategrey-50">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        DESPACHO VIABILIDADE
                    </div>
                    <div class="modal-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                    </div>
                </div>
            @endif

        </div>

    </div>
</div>
