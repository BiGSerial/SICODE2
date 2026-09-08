<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($showAddstatus)
        <div class="filters-modal__intro">
            <h2 class="filters-modal__service-name">{{ $service->service }}</h2>
            <p class="filters-modal__hint">
                Filtros controlam quais notas entram (ou são <strong>excluídas</strong>) deste serviço, comparando uma coluna
                da nota com um valor. Você pode combinar duas condições com <strong>E</strong>.
            </p>
        </div>

        <div class="filters-modal__control-card mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Coluna de busca</label>
                    <select class="form-select" wire:model.defer="column_search">
                        <option value="">Selecione o campo</option>
                        @forelse ($columns_l ?? [] as $column)
                            <option value="{{ $column }}">{{ $this->prettyColumn($column) }}</option>
                        @empty
                            <option value="" disabled>Nenhuma coluna disponível</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Condição</label>
                    <select class="form-select" wire:model.defer="condition">
                        <option value="">Selecione</option>
                        @foreach (\App\Http\Livewire\Config\Services\Addstatus::CONDITIONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valor</label>
                    <input type="text" class="form-control" wire:model.defer="value" placeholder="Ex.: MMGD ou 2, 4, 7">
                </div>
            </div>
            <div class="form-text">
                Em "Está em" / "Não está em", digite os valores separados por vírgula (ex.: 2, 4, 7).
            </div>

            <div class="filters-modal__exclusion form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" role="switch" id="exclusion" value="true"
                    wire:model.defer="exclusion">
                <label class="form-check-label" for="exclusion">Excluir quando este filtro corresponder</label>
                <div class="form-text">
                    Desligado: notas que combinam com este filtro <strong>entram</strong> no serviço.
                    Ligado: notas que combinam são <strong>excluídas</strong> do serviço.
                </div>
            </div>

            @if ($view_and)
                <div class="filters-modal__and-card mt-3">
                    <div class="filters-modal__and-badge">E</div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Coluna de busca</label>
                            <select class="form-select" wire:model.defer="column_search2">
                                <option value="">Selecione o campo</option>
                                @forelse ($columns_l ?? [] as $column)
                                    <option value="{{ $column }}">{{ $this->prettyColumn($column) }}</option>
                                @empty
                                    <option value="" disabled>Nenhuma coluna disponível</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Condição</label>
                            <select class="form-select" wire:model.defer="condition2">
                                <option value="">Selecione</option>
                                @foreach (\App\Http\Livewire\Config\Services\Addstatus::CONDITIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor</label>
                            <input type="text" class="form-control" wire:model.defer="value2" placeholder="Ex.: MMGD ou 2, 4, 7">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="exclusion2" value="true"
                            wire:model.defer="exclusion2">
                        <label class="form-check-label" for="exclusion2">Excluir quando esta condição também corresponder</label>
                    </div>
                </div>
            @endif

            <div class="filters-modal__actions mt-3">
                <button type="button" wire:click.prevent="and"
                    class="btn btn-sm {{ $view_and ? 'btn-outline-danger' : 'btn-outline-secondary' }}">
                    @if ($view_and)
                        <i class="ri-close-line"></i> Remover condição E
                    @else
                        <i class="ri-add-line"></i> Adicionar condição E
                    @endif
                </button>
                <button type="button" wire:click.prevent="add" class="btn btn-sm btn-primary ms-auto"
                    wire:loading.attr="disabled" wire:target="add">
                    <span wire:loading.remove wire:target="add"><i class="ri-filter-3-line"></i> Adicionar filtro</span>
                    <span wire:loading wire:target="add">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Adicionando...
                    </span>
                </button>
            </div>
        </div>

        <div class="filters-modal__summary">
            <div class="filters-modal__count">
                Filtros ativos
                <span class="filters-modal__count-badge">{{ $status_list->count() }}</span>
            </div>
        </div>

        @if (!$status_list->count())
            <div class="filters-modal__empty">
                <i class="ri-filter-3-line"></i>
                Nenhum filtro cadastrado para este serviço ainda.
            </div>
        @else
            <div class="filters-modal__table-wrap">
                <table class="table table-sm filters-modal__table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Coluna</th>
                            <th scope="col">Condição</th>
                            <th scope="col">Valor</th>
                            <th scope="col" class="text-center">Tipo</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($status_list as $sts)
                            <tr wire:key="filter-{{ $sts->id }}">
                                <td>
                                    <div>{{ $this->prettyColumn($sts->column_search) }}</div>
                                    @if ($sts->column_search2)
                                        <div class="filters-modal__and-line">
                                            <span class="filters-modal__and-tag">E</span>
                                            {{ $this->prettyColumn($sts->column_search2) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $this->conditionLabel($sts->condition) }}</div>
                                    @if ($sts->condition2)
                                        <div class="filters-modal__and-line">{{ $this->conditionLabel($sts->condition2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $this->displayValue($sts->value, $sts->condition) }}</div>
                                    @if ($sts->value2)
                                        <div class="filters-modal__and-line">{{ $this->displayValue($sts->value2, $sts->condition2) }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $sts->exclusion ? 'text-bg-danger' : 'text-bg-success' }}">
                                        {{ $sts->exclusion ? 'EXCLUI' : 'INCLUI' }}
                                    </span>
                                    @if ($sts->column_search2)
                                        <div class="mt-1">
                                            <span class="badge {{ $sts->exclusion2 ? 'text-bg-danger' : 'text-bg-success' }}">
                                                {{ $sts->exclusion2 ? 'EXCLUI' : 'INCLUI' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        title="Remover filtro" wire:click.prevent="to_remove({{ $sts->id }})">
                                        <i class="ri-delete-bin-2-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
