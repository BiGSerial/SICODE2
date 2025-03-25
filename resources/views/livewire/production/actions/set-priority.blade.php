<div>
    <div wire:ignore.self class="modal fade" id="set_priority" tabindex="-1" aria-labelledby="addMassNotesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="addMassNotesModalLabel">DEFINIR PRIORIDADE -
                        {{ $production ? $production->service->service : '' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($production)
                        <div class="card">
                            <table class="table table-sm table-condensed table-striped-columns">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">Note:</td>
                                        <td><span class="small">{{ $production->note->note }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Municipio:</td>
                                        <td><span class="small">{{ $production->note->lexp }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Rubrica:</td>
                                        <td><span class="small">{{ $production->note->rubrica }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Descrição:</td>
                                        <td><span class="small">{{ $production->note->material }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Gruop 4:</td>
                                        <td><span class="small">{{ $production->note->group4 }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-3">
                            <label for="priority_reason">Motivo da Prioridade: <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" wire:model.defer="priority_reason" rows="5"
                                placeholder="Informe o motivo da prioridade..."></textarea>
                            @error('priority_reason')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" data-bs-dismiss="modal"
                        wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:loading.attr="disabled" wire:click="executeSetPriority">
                        Atribuir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
