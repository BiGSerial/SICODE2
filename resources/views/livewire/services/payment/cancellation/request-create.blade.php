<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Solicitação de Cancelamento</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Número da Nota</label>
                            <input type="text" class="form-control" wire:model.defer="noteSearch" placeholder="Ex: 123456" />
                            @error('noteSearch')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" wire:click="findNote">Buscar</button>
                        </div>
                    </div>

                    @if($note)
                        <hr />
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Nota:</strong> {{ $note->note }}</p>
                                <p class="mb-1"><strong>Cliente:</strong> {{ $note->client ?? '-' }}</p>
                                <p class="mb-1"><strong>Status:</strong> {{ $note->status ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                @if($noteCanceled)
                                    <div class="alert alert-danger">Nota já cancelada. Não é possível abrir nova solicitação.</div>
                                @elseif($hasCanceledOrders)
                                    <div class="alert alert-warning">Existem ordens já canceladas. Selecione ordens específicas.</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <select class="form-select" wire:model.defer="categoryId">
                                    <option value="">Selecione...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('categoryId')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Escopo</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="scope" value="NOTE_FULL" id="scopeFull" @if($noteCanceled || $hasCanceledOrders) disabled @endif>
                                    <label class="form-check-label" for="scopeFull">Cancelar nota inteira</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="scope" value="ORDERS_PARTIAL" id="scopePartial">
                                    <label class="form-check-label" for="scopePartial">Cancelar ordens específicas</label>
                                </div>
                                @error('scope')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Ordens</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Ordem</th>
                                                <th>Status</th>
                                                <th>Cancelada</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orders as $order)
                                                <tr>
                                                    <td>
                                                        @if(!$order['canceled'])
                                                            <input class="form-check-input" type="checkbox" value="{{ $order['id'] }}" wire:model="selectedOrders" @if($scope !== 'ORDERS_PARTIAL') disabled @endif>
                                                        @endif
                                                    </td>
                                                    <td>{{ $order['ordem'] }}</td>
                                                    <td>{{ $order['status'] ?? '-' }}</td>
                                                    <td>{{ $order['canceled'] ? 'Sim' : 'Não' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @error('selectedOrders')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Descrição complementar</label>
                                <textarea class="form-control" rows="3" wire:model.defer="description"></textarea>
                                @error('description')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Evidências</label>
                                <input type="file" class="form-control" multiple wire:model="files">
                                @error('files.*')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-12 mt-2">
                                @if(count($tempFiles))
                                    <ul class="list-group">
                                        @foreach($tempFiles as $index => $file)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>{{ $file['original_name'] }} ({{ number_format($file['size'] / 1024, 1) }} KB)</span>
                                                <button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-4">
                                <button class="btn btn-success w-100" wire:click="submit" @if($noteCanceled) disabled @endif>Enviar solicitação</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">Regras rápidas</div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Selecione a categoria e o escopo antes de enviar.</li>
                        <li>Notas canceladas não permitem novas solicitações.</li>
                        <li>Ordens já canceladas não podem ser selecionadas.</li>
                        <li>Evidências podem ser obrigatórias conforme categoria.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
