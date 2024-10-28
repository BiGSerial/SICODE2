<div>
    <!-- Modal Transferencia de Produção -->
    <div wire:ignore.self class="modal fade" id="transfer_modal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Transferência Produção</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($transfer_view)
                        <h4 class="text-center">TRANSFERENCIA DE TITULARIDADE DE PRODUÇÃO
                            {{ $production ? $production->Note->note : '' }}</h4>
                        <p
                            class="text-justify p-2 border border-1 border-secondary edp-bg-sprucegreen-100 edp-text-verde-dark my-2">
                            A transferência de produção consiste em transferir a titularidade desta produção. É
                            importante observar que isso impactará na métrica
                            da instituição que por ventura, dependa do quantitativo desta produção (Em caso de transição
                            entre empresas). Isso inclui todo tempo desprendido para a execução desta atividade
                            sendo creditada por completo, ao novo titular.
                        </p>

                        <div class="mb-1">
                            <label for="exampleFormControlInput1" class="form-label">Buscar Usuário:</label>
                            <input type="text" class="form-control" wire:model.bounce.500ms="search">
                        </div>
                        <select class="form-select mb-3" aria-label="Default select example"
                            wire:model.defer="user_transfer_id">

                            <option selected>Selecione um Usuário</option>


                            @if ($user_list->count())
                                @foreach ($user_list as $user)
                                    @if (isset($user->Employee->Contract))
                                        @php
                                            $name = explode(' ', $user->name);
                                            $name = $name[0] . ' ' . end($name);
                                        @endphp
                                        <option wire:key='{{ $user->id }}' value="{{ $user->id }}">
                                            {{ $name }} -
                                            {{ explode(' ', $user->Employee->Contract->company->name)[0] }}</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>

                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Motivo: <span
                                    class="text-danger fw-bold">*</span></label>
                            <textarea type="text" class="form-control" wire:model.defer="user_transfer_info" placeholder="Informe o motivo"
                                rows="5"></textarea>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="transfer_prod">Solicitar
                        Transferência</button>
                </div>
            </div>
        </div>
    </div>
</div>
