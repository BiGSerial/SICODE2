@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="returnRamalform" tabindex="-1" aria-labelledby="returnWorkformLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            @if ($production)
                <div class="modal-content">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h1 class="modal-title fs-5" id="returnWorkformLabel">
                            RETORNAR INFORME: <strong> {{ $production->Note->note }}</strong>
                        </h1>
                    </div>
                    <div class="modal-body edp-bg-stategrey-50">
                        <div class="card">
                            <h5 class="card-header edp-bg-sprucegreen-70 text-edp-verde">Atenção</h5>
                            <div class="card-body">
                                <p>
                                    Ao retornar este informe, A Obra ficará oculta na sua lista, porém continuará
                                    atribuído a você a atividade. Assim que houver
                                    um retorno do digitador, ela voltará a aparecer na sua lista.
                                </p>
                            </div>
                        </div>

                        <form>
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Selecione Motivo <strong
                                        class="text-danger">*</strong></label>
                                <select class="form-select" aria-label="Default select example"
                                    wire:model.defer="returnWork.category" required>
                                    <option value="" selected>Selecione</option>
                                    @foreach (SelectOptions::getReasonSmcPublication() as $category)
                                        <option value="{{ $category->value }}">{{ $category->reason }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Detalhe a instrução ao
                                    Digitador: <strong class="text-danger">*</strong></label>
                                <textarea class="form-control" placeholder="Detalhe o motivo de retorno" rows="3"
                                    wire:model.defer="returnWork.text_obs" required></textarea>
                            </div>

                    </div>
                    <div class="modal-footer edp-bg-stategrey-100">
                        <button type="button" class="btn btn-danger" wire:click.prevent="close()">CANCELAR</button>
                        <button type="submit" class="btn btn-secondary" wire:click.prevent="toSave()">DEVOLVER</button>

                    </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('returnWorkform').addEventListener('hidden.bs.modal', () => {

            Livewire.emitTo('production.return.return-work', 'close');
        });
    </script>
</div>
