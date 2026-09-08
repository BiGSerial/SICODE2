<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div style="min-width: 260px;">
            <label for="search" class="form-label">Buscar serviço</label>
            <input wire:model.bounce.2s="search" type="text" class="form-control"
                id="search" placeholder="Nome do serviço...">
        </div>

        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal"
            data-bs-target="#create_modal">
            <i class="ri-add-line fs-5"></i>
            <span>Novo serviço</span>
        </button>
    </div>

    @if (!$services->count())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ri-customer-service-2-line fs-1 d-block mb-2"></i>
                Nenhum serviço encontrado.
            </div>
        </div>
    @else
        @foreach ($services as $service)
            <div class="card mb-3 service-card" wire:key="service-{{ $service->id }}">
                <div class="card-header bg-white">
                    <div class="row align-items-center g-2">
                        <div class="col">
                            @if (isset($editName[$service->id]) && $editName[$service->id])
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Nome</label>
                                        <input type="text" class="form-control"
                                            wire:model.defer="service_name"
                                            wire:key="item-name-{{ $service->id }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Diretório</label>
                                        <select class="form-select" wire:model.defer="folder_s"
                                            wire:key="item-folder-{{ $service->id }}">
                                            <option value="">Selecione um diretório</option>
                                            @forelse ($folders ?? [] as $folder)
                                                <option value="{{ $folder }}">{{ mb_strtoupper($folder) }}</option>
                                            @empty
                                                <option value="" disabled>Nenhum diretório disponível</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <x-icon-picker wire-model="icon_s" :selected="$icon_s"
                                            :id="'edit-' . $service->id" label="" />
                                    </div>
                                    <div class="col-md-2 d-flex gap-1">
                                        <button class="btn btn-primary flex-fill" type="button"
                                            wire:click.prevent="update_name" wire:key="item-save-{{ $service->id }}">
                                            <i class="ri-check-line"></i> Salvar
                                        </button>
                                    </div>
                                </div>
                            @else
                                <h5 class="mb-0 d-flex align-items-center flex-wrap gap-2">
                                    <i class="{{ $service->icon ?: 'ri-customer-service-2-line' }} text-primary"></i>
                                    <span>{{ $service->service }}</span>

                                    @if ($service->Status && $service->Status->where('exclusion', false)->unique('value')->isNotEmpty())
                                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $status)
                                            <span class="badge text-bg-light border fw-normal">{{ $status->value }}</span>
                                        @endforeach
                                    @endif

                                    @if ($service->project)
                                        <span class="badge text-bg-primary">Projeto</span>
                                    @endif
                                    @if ($service->construction)
                                        <span class="badge text-bg-warning">Construção</span>
                                    @endif

                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                        title="Editar serviço"
                                        wire:click.prevent="edit_name_service({{ $service->id }})">
                                        <i class="ri-pencil-fill"></i>
                                    </button>
                                </h5>
                            @endif
                        </div>

                        <div class="col-auto d-flex flex-wrap justify-content-end gap-2">
                            <button
                                class="btn btn-sm {{ $service->canReturn ? 'btn-success' : 'btn-outline-secondary' }}"
                                title="{{ $service->canReturn ? 'Retorno habilitado' : 'Retorno desabilitado' }}"
                                wire:click.prevent="update_return({{ $service->id }})">
                                <i class="ri-arrow-go-back-fill align-middle"></i>
                                Retorno
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" wire:click.prevent="addStatus({{ $service->id }})">
                                <i class="ri-filter-3-line align-middle"></i>
                                Filtros
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" wire:click.prevent="addRule({{ $service->id }})">
                                <i class="ri-file-list-3-line align-middle"></i>
                                Regras
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (!$service->contracts->count())
                        <div class="text-center text-muted py-3">
                            <i class="ri-file-list-3-line fs-3 d-block mb-1"></i>
                            Nenhuma regra cadastrada para este serviço.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Empresa</th>
                                        <th scope="col">Contrato</th>
                                        <th scope="col" class="text-center">Despacho?</th>
                                        <th scope="col" class="text-center">Por poste?</th>
                                        <th scope="col" class="text-center">Quantidade</th>
                                        <th scope="col" class="text-center">Dias</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($service->contracts as $contract)
                                        <tr>
                                            <td class="fw-bold">{{ mb_strtoupper(optional($contract->company)->name ?? 'EMPRESA NÃO INFORMADA') }}</td>
                                            <td>{{ $contract->number ?? '—' }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $contract->pivot->dispatch ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                    {{ $contract->pivot->dispatch ? 'SIM' : 'NÃO' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $contract->pivot->posts ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                    {{ $contract->pivot->posts ? 'SIM' : 'NÃO' }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ $contract->pivot->qtd ?? 0 }}</td>
                                            <td class="text-center">{{ $contract->pivot->days ?? 0 }}</td>
                                            <td>@livewire('config.services.removerules', ['service' => $service->id, 'contract' => $contract->id, 'action_id' => hash('ripemd160', $service->id . now() . $contract->id)], key(hash('ripemd160', 'removeRules' . $service->id . now() . $contract->id)))</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    {{-- MODAIS --}}

    @livewire('config.services.delete')

    <div wire:ignore.self class="modal fade" id="create_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-customer-service-2-fill fs-4 align-middle"></i> CRIAR SERVIÇO</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('config.services.create', key(hash('ripemd160', 'config' . now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_create_service')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="add_rules_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-customer-service-2-fill fs-4 align-middle"></i> CRIAR REGRAS</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" wire:key="addRule">
                    @livewire('config.services.addrules', key(hash('ripemd160', 'addRules' . now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_add_rules')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade filters-modal" id="add_status_modal" tabindex="-1"
        aria-labelledby="filtersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="filters-modal__header d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h1 class="filters-modal__title" id="filtersModalLabel">
                            <i class="ri-filter-3-line align-middle"></i> Filtros do serviço
                        </h1>
                        <div class="filters-modal__subtitle">Defina quais notas entram ou são excluídas automaticamente.</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>
                <div class="filters-modal__body" wire:key="addStatus">
                    @livewire('config.services.addstatus', key('addStatus' . hash('ripemd160', 'addStatus' . now())))
                </div>
                <div class="filters-modal__footer">
                    <button type="button" class="btn btn-primary" wire:click.prevent="$emit('refresh_service_list')"
                        data-bs-dismiss="modal">
                        <i class="ri-check-line"></i> Concluído
                    </button>
                </div>
            </div>
        </div>
    </div>


</div>

@once
    @push('css')
        <style>
            .filters-modal .modal-dialog {
                max-width: min(760px, calc(100vw - 1.5rem));
            }

            .filters-modal .modal-content {
                border: 0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
            }

            .filters-modal__header {
                background: #123f43;
                color: #f8fafc;
                padding: 1rem 1.25rem;
            }

            .filters-modal__title {
                font-size: 1rem;
                font-weight: 700;
                margin: 0;
            }

            .filters-modal__subtitle {
                color: rgba(248, 250, 252, 0.74);
                font-size: 0.78rem;
                margin-top: 0.15rem;
            }

            .filters-modal__body {
                background: #f8fafc;
                padding: 1rem 1.25rem;
                max-height: min(65vh, 620px);
                overflow-y: auto;
            }

            .filters-modal__footer {
                align-items: center;
                background: #ffffff;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
                padding: 0.85rem 1.25rem;
            }

            .filters-modal__footer .btn {
                border-radius: 6px;
                font-weight: 700;
                min-height: 38px;
                min-width: 112px;
            }

            @media (max-width: 767.98px) {
                .filters-modal__footer {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .filters-modal__footer .btn {
                    width: 100%;
                }
            }

            .filters-modal__intro {
                margin-bottom: 0.85rem;
            }

            .filters-modal__service-name {
                font-size: 0.95rem;
                font-weight: 800;
                margin: 0 0 0.2rem;
            }

            .filters-modal__hint {
                color: #64748b;
                font-size: 0.8rem;
                margin: 0;
            }

            .filters-modal .form-label {
                color: #334155;
                font-size: 0.75rem;
                font-weight: 700;
                margin-bottom: 0.3rem;
                text-transform: uppercase;
            }

            .filters-modal .form-control,
            .filters-modal .form-select {
                border-color: #cbd5e1;
                border-radius: 6px;
                min-height: 38px;
            }

            .filters-modal__control-card {
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                padding: 0.85rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            }

            .filters-modal__exclusion .form-text {
                font-size: 0.74rem;
            }

            .filters-modal__and-card {
                background: #f8fafc;
                border: 1px dashed #94a3b8;
                border-radius: 8px;
                padding: 0.75rem 0.85rem 0.85rem;
                position: relative;
            }

            .filters-modal__and-badge {
                align-items: center;
                background: #123f43;
                border-radius: 999px;
                color: #fff;
                display: inline-flex;
                font-size: 0.72rem;
                font-weight: 800;
                height: 22px;
                justify-content: center;
                left: 0.85rem;
                position: absolute;
                top: -11px;
                width: 22px;
            }

            .filters-modal__actions {
                align-items: center;
                display: flex;
                gap: 0.5rem;
            }

            .filters-modal__summary {
                align-items: center;
                display: flex;
                justify-content: space-between;
                margin: 0.9rem 0 0.5rem;
            }

            .filters-modal__count {
                color: #0f172a;
                font-weight: 800;
                font-size: 0.85rem;
                letter-spacing: 0.01em;
            }

            .filters-modal__count-badge {
                align-items: center;
                background: #0f766e;
                border-radius: 999px;
                color: #ffffff;
                display: inline-flex;
                font-size: 0.78rem;
                font-weight: 800;
                justify-content: center;
                margin-left: 0.4rem;
                min-width: 28px;
                padding: 0.15rem 0.5rem;
            }

            .filters-modal__empty {
                background: #ffffff;
                border: 1px dashed #cbd5e1;
                border-radius: 8px;
                color: #64748b;
                padding: 1.25rem;
                text-align: center;
            }

            .filters-modal__empty i {
                display: block;
                font-size: 1.4rem;
                margin-bottom: 0.35rem;
            }

            .filters-modal__table-wrap {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                max-height: min(40vh, 360px);
                overflow: auto;
            }

            .filters-modal__table {
                margin: 0;
            }

            .filters-modal__table thead th {
                background: #eef2f7;
                border-bottom: 1px solid #dbe3ef;
                color: #475569;
                font-size: 0.72rem;
                position: sticky;
                text-transform: uppercase;
                top: 0;
                z-index: 1;
            }

            .filters-modal__table td {
                color: #1f2937;
                font-size: 0.86rem;
                vertical-align: middle;
            }

            .filters-modal__and-line {
                color: #c1442e;
                font-size: 0.72rem;
                font-weight: 700;
                margin-top: 0.15rem;
            }

            .filters-modal__and-tag {
                background: #123f43;
                border-radius: 3px;
                color: #fff;
                font-size: 0.6rem;
                padding: 0 0.3rem;
                margin-right: 0.2rem;
            }
        </style>
    @endpush
@endonce

@push('script')
    <script>
        window.addEventListener('alertar', function(e) {

            const Confirmation = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            Swal.fire({
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    Livewire.emit(e.detail.action, e.detail.action_id)

                } else if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire(
                        e.detail.cancel_titulo,
                        e.detail.cancel_msg,
                        'success'
                    )
                }
            })
        });
    </script>
@endpush
