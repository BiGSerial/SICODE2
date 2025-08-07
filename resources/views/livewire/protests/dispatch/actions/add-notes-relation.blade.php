<div>
    <div wire:ignore.self class="modal fade" id="addNotesRelationModal" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde border-0">
                    <h5 class="modal-title fw-bold" id="modalEntityProtocolLabel">
                        <i class="ri-link me-2"></i>ASSOCIAR NOTA/OV PARA
                        <span class="badge bg-light text-dark ms-2">{{ $protest?->nota }}</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 edp-bg-gray">
                    @if ($protest)
                        <!-- Search Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="ri-search-line text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 shadow-sm" id="searchInput"
                                        wire:model="search" placeholder="Digite para buscar notas...">
                                </div>
                            </div>
                        </div>

                        <!-- Available Notes Section -->
                        <div class="card border-0 shadow mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h6 class="my-1 mb-0 text-muted">
                                    <i class="ri-file-list-3-line me-2"></i>NOTAS DISPONÍVEIS
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div wire:loading class="text-center p-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <span class="text-muted">Carregando...</span>
                                </div>
                                <div wire:loading.remove>
                                    @if ($notes->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col" class="fw-semibold">
                                                            <i class="ri-file-text-line me-1"></i>Nota
                                                        </th>
                                                        <th scope="col" class="fw-semibold">
                                                            <i class="ri-price-tag-3-line me-1"></i>Rubrica
                                                        </th>
                                                        <th scope="col" class="fw-semibold">
                                                            <i class="ri-map-pin-line me-1"></i>Município
                                                        </th>
                                                        <th scope="col" class="fw-semibold">
                                                            <i class="ri-user-line me-1"></i>Cliente
                                                        </th>
                                                        <th scope="col" class="text-center fw-semibold">Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($notes as $n)
                                                        <tr class="align-middle">
                                                            <td class="fw-medium">{{ $n->note }}</td>
                                                            <td>{{ $n->rubrica }}</td>
                                                            <td>{{ $n->lexp }}</td>
                                                            <td>{{ $n->client }}</td>
                                                            <td class="text-center">
                                                                <button
                                                                    class="btn btn-outline-success btn-sm rounded-pill"
                                                                    wire:click="addNoteToProtest({{ $n->id }})"
                                                                    title="Adicionar nota">
                                                                    <i class="ri-add-line"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="ri-search-line fs-1 text-muted mb-3 d-block"></i>
                                            <h5 class="text-muted">Nenhuma Nota/OV Encontrada</h5>
                                            <p class="text-muted mb-0">Tente ajustar os termos da busca</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Associated Notes Section -->
                        <div class="card border-0 shadow">
                            <div class="card-header bg-primary text-white">
                                <h6 class="my-1 mb-0">
                                    <i class="ri-links-line me-2"></i>NOTAS ASSOCIADAS
                                    @if ($protest->Notes->isNotEmpty())
                                        <span
                                            class="badge bg-light text-primary ms-2">{{ $protest->Notes->count() }}</span>
                                    @endif
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                @if ($protest->Notes->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th scope="col" class="fw-semibold">
                                                        <i class="ri-file-text-line me-1"></i>Nota
                                                    </th>
                                                    <th scope="col" class="fw-semibold">
                                                        <i class="ri-price-tag-3-line me-1"></i>Rubrica
                                                    </th>
                                                    <th scope="col" class="fw-semibold">
                                                        <i class="ri-map-pin-line me-1"></i>Município
                                                    </th>
                                                    <th scope="col" class="fw-semibold">
                                                        <i class="ri-user-line me-1"></i>Cliente
                                                    </th>
                                                    <th scope="col" class="text-center fw-semibold">Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($protest->Notes as $nNote)
                                                    <tr class="align-middle">
                                                        <td class="fw-medium">{{ $nNote->note }}</td>
                                                        <td>{{ $nNote->rubrica }}</td>
                                                        <td>{{ $nNote->lexp }}</td>
                                                        <td>{{ $nNote->client }}</td>
                                                        <td class="text-center">
                                                            <button class="btn btn-outline-danger btn-sm rounded-pill"
                                                                wire:click.prevent="removeNoteFromProtest({{ $nNote->id }})"
                                                                title="Remover nota">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="ri-file-forbid-line fs-1 text-muted mb-3 d-block"></i>
                                        <h5 class="text-muted">Nenhuma Nota/OV Associada</h5>
                                        <p class="text-muted mb-0">Adicione notas da lista acima</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-100 border-top-0">
                    <button type="button" class="btn btn-outline-primary rounded-pill px-4" wire:click="closeAll">
                        <i class="ri-close-line me-1"></i>Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
