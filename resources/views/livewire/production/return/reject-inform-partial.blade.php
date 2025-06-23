@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="rejectInformPartial" tabindex="-1" aria-labelledby="returnWorkformLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            @if ($production)
                <div class="modal-content">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h1 class="modal-title fs-5" id="returnWorkformLabel">
                            REJEITAR INFORME PARCIAL: <strong> {{ $production->Note->note }}</strong>
                        </h1>
                    </div>
                    <div class="modal-body edp-bg-stategrey-50">
                        <div class="card text-bg-danger">
                            <h5 class="card-header edp-bg-sprucegreen-70 text-edp-verde text-center">ATENÇÃO</h5>
                            <div class="card-body">
                                <h4 class="text-center">Ao rejeitar este informe parcial, esta produção será excluída
                                    automaticamente.</h4>
                            </div>
                        </div>

                        <form>
                            {{-- <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Selecione Motivo <strong
                                        class="text-danger">*</strong></label>
                                <select class="form-select" aria-label="Default select example"
                                    wire:model.defer="returnWork.category" required>
                                    <option value="" selected>Selecione</option>
                                    @foreach (SelectOptions::getReasonSmcPublication() as $category)
                                        <option value="{{ $category->value }}">{{ $category->reason }}</option>
                                    @endforeach
                                </select>
                            </div> --}}
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="floatingTextarea" wire:model.defer="text_obs" required rows="8"
                                    style="height: 300px"></textarea>
                                <label for="floatingTextarea">Detalhe o motivo da rejeição: <strong
                                        class="text-danger">*</strong></label>
                            </div>

                    </div>
                    <div class="modal-footer edp-bg-stategrey-100">
                        <button type="button" class="btn btn-danger" wire:click.prevent="close()">CANCELAR</button>
                        <button type="submit" class="btn btn-secondary" wire:click.prevent="toSave()">REJEITAR</button>

                    </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('rejectInformPartial').addEventListener('hidden.bs.modal', () => {

            Livewire.emitTo('rejectInformPartial', 'close');
        });
    </script>
</div>
