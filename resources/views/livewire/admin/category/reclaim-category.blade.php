<div>
    <div class="card">
        <h4 class="card-header mb-4 edp-bg-sprucegreen-70 text-edp-verde">Categoria Retorno Interno</h4>
        <div class="card-body">


            <div class="row"> <!-- Adicionado container row -->
                <!-- Seção Categoria -->
                <div class="col-md-6">
                    <h5 class="card-header mb-4 edp-bg-sprucegreen-50 text-edp-verde">Categorias</h5>
                    <!-- Adicionado col-md-6 -->
                    <div class="mb-4">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Nova categoria"
                                wire:model.defer="category">
                            <button class="btn btn-primary" wire:click.prevent="AddCategory">Adicionar</button>
                        </div>

                        <div class="overflow-auto" style="height: 12rem;">
                            @if ($categories->isNotEmpty())
                                <table class="table table-hover">
                                    <tbody>
                                        @foreach ($categories as $category)
                                            <tr wire:key="category-{{ $category->id }}"
                                                wire:click="$set('category_id', {{ $category->id }})"
                                                style="cursor: pointer;"
                                                class="{{ $category_id == $category->id ? 'text-bg-primary' : '' }}">
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <input type="radio" name="category"
                                                                class="form-check-input me-2" wire:model='category_id'
                                                                value="{{ $category->id }}">
                                                            <span>{{ $category->name }}</span>
                                                        </div>
                                                        <i class="bi bi-trash text-danger"
                                                            wire:click.stop="RemoveCategory({{ $category->id }})"
                                                            style="font-size: 1.1rem; cursor: pointer;"></i>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-info" role="alert">
                                    Nenhuma categoria criada.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Seção Subcategoria -->
                <div class="col-md-6"> <!-- Adicionado col-md-6 -->
                    <h5 class="card-header mb-4 edp-bg-sprucegreen-50 text-edp-verde">Sub Categorias</h5>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Nova subcategoria"
                            @disabled(!$category_id) wire:model.defer="subcategory">
                        <button class="btn btn-primary" @disabled(!$category_id)
                            wire:click.prevent="AddSubCategory">Adicionar</button>
                    </div>

                    <div class="overflow-auto" style="height: 12rem;">
                        @if ($subcategories->isNotEmpty())
                            <table class="table table-hover">
                                <tbody>

                                    @foreach ($subcategories as $subcategory)
                                        <tr wire:key="subcategory-{{ $subcategory->id }}">
                                            <td class="d-flex justify-content-between align-items-center">
                                                <span>{{ $subcategory->name }}</span>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch"
                                                            wire:click="setNeedfile({{ $subcategory->id }})"
                                                            @checked($subcategory->needFile)>
                                                        <small class="text-muted">Arquivo Obrigatório</small>
                                                    </div>
                                                    <i class="bi bi-trash text-danger"
                                                        wire:click="RemoveSubCategory({{ $subcategory->id }})"
                                                        style="font-size: 1.1rem; cursor: pointer;"></i>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info" role="alert">
                                Nenhuma subcategoria para categoria selecionada.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
