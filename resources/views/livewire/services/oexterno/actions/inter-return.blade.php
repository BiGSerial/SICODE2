@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <div wire:ignore.self class="modal fade" id="modalInterReturn" tabindex="-1" aria-labelledby="modalEntityProtocolLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">Retorno Interno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($external)
                        <div class="row g-3 mb-3">
                            <!-- Categoria -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select id="category"
                                        class="form-select @error('categorySelected') is-invalid @enderror"
                                        wire:model="categorySelected">
                                        <option value="">Selecione...</option>
                                        @foreach ($categories_s as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="category">Categoria</label>
                                    @error('categorySelected')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Subcategoria -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select id="subcategory"
                                        class="form-select @error('subcategorySelected') is-invalid @enderror"
                                        wire:model="subcategorySelected">
                                        @if ($subcategories_s && $subcategories_s->count())
                                            <option value="">Selecione...</option>
                                            @foreach ($subcategories_s as $subcategory)
                                                <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                                            @endforeach
                                        @else
                                            <option value="">Escolha uma Categoria Primeiro...</option>
                                        @endif
                                    </select>
                                    <label for="subcategory">Sub-categoria</label>
                                    @error('subcategorySelected')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Observações -->
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea id="observations" class="form-control @error('observations') is-invalid @enderror"
                                        placeholder="Observações..." wire:model.defer="observations" style="height: 100px;">{{ old('observations') }}</textarea>
                                    <label for="observations">Descrição</label>
                                    @error('observations')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Serviço para Retornar -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select id="service"
                                        class="form-select @error('serviceSelected') is-invalid @enderror"
                                        wire:model="serviceSelected">
                                        <option value="">Selecione...</option>
                                        @foreach ($services ?? [] as $service)
                                            <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                        @endforeach
                                    </select>
                                    <label for="service">Serviço para Retornar</label>
                                    @error('serviceSelected')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            @if ($serviceSelected)
                                @if ($production)
                                    <div class="card mt-3 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-3 text-muted">Informações do Serviço – Último
                                                Usuário</h6>
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <small class="text-muted mb-1">Serviço</small>
                                                    <div class="fw-semibold">{{ $production->service?->service }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <select id="userSelected"
                                                            class="form-select @error('userSelected') is-invalid @enderror"
                                                            wire:model.defer="userSelected"
                                                            wire:loading.class="opacity-50" wire:loading.attr="disabled"
                                                            wire:target="serviceSelected">
                                                            <option value="">Selecione...</option>
                                                            @foreach ($userList ?? [] as $user)
                                                                <option value="{{ $user->id }}">
                                                                    {{ $user->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div wire:loading wire:target="serviceSelected"
                                                            class="spinner-border spinner-border-sm text-primary position-absolute end-0 me-3 mt-2"
                                                            role="status">
                                                            <span class="visually-hidden">Carregando...</span>
                                                        </div>
                                                        <label for="userSelected">Usuário</label>
                                                        @error('userSelected')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted mb-1">Última Interação</small>
                                                    <div class="fw-semibold">
                                                        {{ $production->completed_at->format('d/m/Y – H:i:s') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6 my-3">
                                        <div class="form-floating">
                                            <select id="userSelected"
                                                class="form-select @error('userSelected') is-invalid @enderror"
                                                wire:model.defer="userSelected" wire:loading.class="opacity-50"
                                                wire:loading.attr="disabled" wire:target="serviceSelected">
                                                <option value="">Selecione...</option>
                                                @foreach ($userList ?? [] as $user)
                                                    <option value="{{ $user->id }}">
                                                        {{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                            <label for="userSelected">Usuário</label>
                                            @error('userSelected')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        @livewire('files.manager.create-serv-files', ['note' => $external->note, 'service' => $theService], key('retusn_inter-' . $external->id))

                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" wire:click="closeAll">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:click="sendInterReturn">Enviar</button>
                </div>
            </div>
        </div>
    </div>
</div>
