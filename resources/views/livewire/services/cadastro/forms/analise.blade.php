{{-- @push('css')
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.2);
            /* opcional: fundo escurecido */
            z-index: 9999;
            /* para garantir que o overlay esteja na frente de tudo */
        }

        .loading-message {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush --}}





<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($view_form)

        <div class="container">
            <div class="card">
                <h4 class="card-header">Informações da Nota</h4>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nota/Ov:</dt>
                                <dd class="col-sm-8">{{ $note->note }}</dd>
                                <dt class="col-sm-4">Cliente:</dt>
                                <dd class="col-sm-8">{{ $note->client }}</dd>
                                <dt class="col-sm-4">Município</dt>
                                <dd class="col-sm-8">{{ $note->lexp }}</dd>
                            </dl>
                        </div>

                        <div class="col-6">
                            <dl class="row">
                                <dt class="col-sm-4">Tipo:</dt>
                                <dd class="col-sm-8">{{ $note->rubrica }}</dd>
                                <dt class="col-sm-4">Data:</dt>
                                <dd class="col-sm-8">{{ date('d/m/Y', strToTime($note->dt_status)) }}</dd>
                                <dt class="col-sm-4">Pedido:</dt>
                                <dd class="col-sm-8">{{ $note->numPedido }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h4 class="card-header">Endereço</h4>
                <div class="card-body">
                    <div class="col-6">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control border border-secondary" placeholder="Endereço"
                                aria-label="Recipient's username" aria-describedby="button-addon2"
                                wire:model.defer="search" required>
                            <button class="btn btn-outline-secondary" type="button" id="button-addon2"
                                wire:click.prevent="search">Buscar</button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($encontrado)
                <div class="card">
                    <h4 class="card-header">Resultado Busca</h4>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Endereço</th>
                                        <th scope="col">Bairro</th>
                                        <th scope="col">Município</th>
                                        <th scope="col">Codigo</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($addresses->count())
                                        @foreach ($addresses as $add)
                                            <tr>
                                                <td>{{ $add->address }}</td>
                                                <td>{{ $add->district }}</td>
                                                <td>{{ $add->city }}</td>
                                                <td>{{ $add->cod }}</td>
                                                <td><button class="btn btn-sm btn-primary"
                                                        wire:click.prevent="UseAddress({{ $add->id }})">USAR</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="text-center"><button class="btn btn-sm btn-primary"
                                wire:click.prevent="newAddress">NOVO ENDEREÇO</button></div>
                    </div>
                </div>
            @endif


            @if ($cadastrar)
                <div class="card">
                    <h4 class="card-header">Cadastrar Novo Endereço</h4>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-6">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Endereço:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="address">
                                </div>
                            </div>
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Bairro:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="district">
                                </div>
                            </div>
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Município:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="city">
                                </div>
                            </div>
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Código:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="cod">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @endif



            <div class="card">
                <h4 class="card-header">Resultado Analise</h4>
                <div class="card-body">

                    <div class="row">


                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Conclusão: <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model="conclusion">
                                <option value="0" selected>Selecione</option>
                                <option value="RUA CADASTRADA">RUA CADASTRADA</option>
                                <option value="RUA NAO CADASTRADA">RUA NAO CADASTRADA</option>
                                <option value="OV ARQUIVADA">OV ARQUIVADA</option>
                            </select>
                        </div>



                        <div class="mb-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Informações: <span
                                    class="text-danger fw-bold">*</span><span class="fw-bold"><i
                                        class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                        style="cursor: pointer;"></i></span></label>
                            <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8" wire:model.defer="info"></textarea>
                        </div>

                        {{-- @if ($card)
                            <div class="mb-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Carta: <span
                                        class="fw-bold"><i class="ri-file-copy-line copyButton"
                                            data-id="infoTextArea" style="cursor: pointer;"></i></span></label>
                                <textarea id="infoTextArea" class="form-control border border-secondary" rows="15" wire:model.defer="card"></textarea>
                            </div>
                        @endif --}}
                    </div>

                </div>
            </div>

            <div class="row g-3 justify-content-end">
                <button class="btn btn-primary col-1 me-2" wire:click.prevent="save_info">SALVAR</button>
                <button class="btn btn-warning col-1 me-2" wire:click.prevent="to_pause">PAUSAR</button>

                @if (!$block_time || $conclusion === 'OV ARQUIVADA')
                    <button class="btn btn-success col-1 me-2"
                        wire:click.prevent="to_finish({{ $analise->production_id }})">ENCERRAR</button>
                @endif

            </div>
        @else
            <div class="loading-overlay">
                <div class="loading-message">
                    <h1>Carregando Dados...</h1>
                </div>
            </div>
    @endif
</div>
