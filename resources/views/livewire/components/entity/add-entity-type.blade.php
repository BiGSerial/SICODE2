<div>
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="modalEntityType" tabindex="-1" aria-labelledby="modalEntityTypeLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable  bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityTypeLabel">TIPO DE ENTIDADE</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-3">
                        <input type="text" wire:model.defer="name"
                            class="form-control form-control-sm border border-secondary"
                            placeholder="Tipo de entidade...">
                        <button type="button" class="btn btn-primary btn-sm" wire:click="addType">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>

                    @if ($editEntityType)

                        <div class="card shadow-sm">
                            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                <h5 class="mb-0">Editar Entidade</h5>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Entity Type -->


                                    <!-- Name -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Nome da entidade" value="{{ old('name') }}"
                                                wire:model.defer="editEntityType.name">
                                            <label for="name">Nome</label>
                                        </div>
                                    </div>


                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button type="button" class="btn btn-secondary me-2"
                                    wire:click="$set('editEntityType', null)">Cancelar</button>
                                <button type="submit" class="btn btn-primary"
                                    wire:click="saveEntityType">Salvar</button>
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div
                                class="card-header bg-light text-secondary fw-bold d-flex align-items-center justify-content-between edp-bg-sprucegreen-70 text-edp-verde">
                                <span>Lista de Tipos de Entidade</span>

                            </div>
                            <div class="card-body">
                                <input type="text" wire:model.live="search"
                                    class="form-control form-control-sm border border-secondary mb-2"
                                    placeholder="Pesquisar...">
                                @if ($lists->isNotEmpty())

                                    <div class="table-responsible">
                                        <table class="table table-sm table-condensed table-striped mt-2">
                                            <thead>
                                                <tr>
                                                    <th style="width: 80%">Type</th>
                                                    <th style="width: 20%">Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($lists as $list)
                                                    <tr class="align-middle">
                                                        <td>{{ $list->name }}</td>
                                                        <td>
                                                            <button type="button" class="btn btn-primary btn-sm"
                                                                wire:click="editEntityType({{ $list->id }})">
                                                                <i class="ri-edit-2-line"></i>
                                                            </button>
                                                            @if ($list->entities->isEmpty())
                                                                <button type="button" class="btn btn-danger btn-sm"
                                                                    wire:click="deleteType({{ $list->id }})">
                                                                    <i class="ri-delete-bin-2-line"></i>
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="card my-1 py-0">
                                        <div class="card-body">
                                            <h5 class="text-center">NENHUM TIPO ENCONTRADO</h5>
                                        </div>
                                    </div>

                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
