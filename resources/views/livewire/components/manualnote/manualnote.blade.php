<div>
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#entrada_modal">
        ENTRADA MANUAL
    </button>

    <div wire:ignore.self class="modal fade" id="entrada_modal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">ENTRADA MANUAL</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <h4 class="text-center">ENTRADA MANUAL DE NOTA/OV</h4>

                    <div class="card mb-3 edp-bg-sprucegreen-100 edp-text-verde-dark">
                        <div class="card-body">
                            <p class="card-text text-justify">
                                Ao realizar a inserção manual de uma (NOTA/OV), isso significará que estamos lidando
                                com uma nota que foi criada recentemente e que requer atenção urgente.

                                É importante observar que todas as entradas manuais de notas são registradas e
                                armazenadas no histórico para referência futura.
                            </p>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="text" class="form-control" wire:model.defer="search" placeholder="Buscar Nota">
                        <button class="btn btn-outline-secondary" type="button" wire:click="getNote">Buscar</button>
                    </div>

                    @if ($search_view)
                        @if (!$note)
                            <div class="alert alert-warning" role="alert">
                                <p class="mb-0">
                                    Não foi possível encontrar (NOTA/OV) no banco de dados do sistema. Tal situação
                                    pode ocorrer com notas recém-criadas no sistema SAP que ainda não foram submetidas à
                                    nossa extração de dados.
                                </p>
                            </div>

                            <div class="card mb-3 edp-bg-sprucegreen-100 edp-text-verde-dark">
                                <div class="card-body">
                                    <p class="card-text text-justify">
                                        Para proceder, é fundamental entender que a mencionada nota será direcionada a
                                        uma lista específica, aguardando a inclusão subsequente da mesma em nosso
                                        sistema. Após sua inclusão na lista de ESPERA e a conclusão desse processo, a
                                        NOTA será creditada automaticamente à produção correspondente.
                                        <strong class="text-white">É imprescindível que se tenha cuidado ao fornecer o
                                            número da
                                            NOTA/OV, uma vez que informar incorretamente poderá resultar em uma
                                            contabilização inadequada da produção. Portanto, solicitamos especial
                                            atenção nesse
                                            aspecto.</strong>
                                    </p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="solicitante" class="form-label">Usuário Destinado Habilitado:</label>
                                <select name="" id="" class="form-select" wire:model.defer="user">
                                    <option value="">Selecione o Usuário</option>
                                    @if ($users)
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"> {{ $user->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="solicitante" class="form-label">Solicitante:</label>
                                <input type="text" id="solicitante" class="form-control"
                                    wire:model.defer="solicitante">
                            </div>
                            <div class="mb-3">
                                <label for="setor" class="form-label">Setor da Solicitante:</label>
                                <input type="text" id="setor" class="form-control" wire:model.defer="setor">
                            </div>
                            <div class="card mb-3 edp-bg-sprucegreen-100 edp-text-verde-dark">
                                <div class="card-body">
                                    <p class="card-text text-justify">
                                        Favor informar o STATUS que esta nota se encontra neste momento no SAP (Antes de
                                        qualquer intervenção da sua parte).
                                    </p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="status-new" class="form-label">Status:</label>
                                <input type="number" min="0" id="status-new" class="form-control"
                                    wire:model.defer="status">
                            </div>
                        @elseif($note)
                            <div class="card mb-3 edp-bg-sprucegreen-100 edp-text-verde-dark">
                                <div class="card-body">
                                    <p class="card-text text-justify">
                                        Identificamos a nota em um STATUS diferente, que servirá como a fonte de
                                        informação para o processo. A partir deste momento você deverá confirmar abaixo,
                                        o STATUS que esta note se encontra neste exato momento SAP (Antes de qualquer
                                        intervenção da sua parte). A (NOTA/OV) será exibida em sua lista de tarefas..
                                    </p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status-exist" class="form-label">Status:</label>
                                <input type="number" id="status-exist" class="form-control" wire:model.defer="status">
                            </div>
                        @endif
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="to_getNote"
                        @disabled($block)>Entrar
                        Manualmente</button>
                </div>
            </div>
        </div>
    </div>
</div>
