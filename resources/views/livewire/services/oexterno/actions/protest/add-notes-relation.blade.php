<div>
    <div wire:ignore.self class="modal fade" id="addNotesRelationModal" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">ASSOCIAR NOTA/OV PARA
                        <strong>{{ $protest?->nota }}</strong>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($protest)
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control border-secondary" id="searchInput"
                                wire:model="search" placeholder="Buscar">
                            <label for="searchInput">Buscar</label>
                        </div>
                        <div class="card">
                            <div wire:loading class="text-center p-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                            <div wire:loading.remove class="text-center p-3">
                                @if ($note)
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr>
                                                <th scope="col">Note</th>
                                                <th scope="col">Rubrica</th>
                                                <th scope="col">Município</th>
                                                <th scope="col">Cliente</th>
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($note as $n)
                                                <tr>
                                                    <td>{{ $n->note }}</td>
                                                    <td>{{ $n->rubrica }}</td>
                                                    <td>{{ $n->lexp }}</td>
                                                    <td>{{ $n->client }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary"
                                                            wire:click="addNote({{ $n->id }})">
                                                            Adicionar
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <h4 class="align-middle text-center">Nenhuma Nota/OV Encontrada ou Buscada</h4>
                                @endif
                            </div>

                        </div>

                        @if ($protest->Notes->isNotEmpty())
                            <div class="card">
                                <h5 class="card-header">NOTAS ASSOCIADAS</h5>
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th scope="col">Note</th>
                                            <th scope="col">Rubrica</th>
                                            <th scope="col">Município</th>
                                            <th scope="col">Cliente</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($protest->Notes as $nNote)
                                            <tr>
                                                <td>{{ $nNote->note }}</td>
                                                <td>{{ $nNote->rubrica }}</td>
                                                <td>{{ $nNote->lexp }}</td>
                                                <td>{{ $nNote->client }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary"
                                                        wire:click="addNote({{ $nNote->id }})">
                                                        Remover
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="card">
                                <h5 class="card-header">NOTAS ASSOCIADAS</h5>
                                <div class="card-body text-center">
                                    <h4 class="align-middle text-center">Nenhuma Nota/OV Associada</h4>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" wire:click="closeAll">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:click="saveProtocol">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
