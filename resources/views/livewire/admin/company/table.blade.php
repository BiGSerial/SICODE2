<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />


    <div class="container">
        <div class="row">
            <div class="row justify-content-between">
                <div class="mb-3 col-3">
                    <label for="search" class="form-label">Buscar</label>
                    <input wire:model.bounce.2s="search" type="email"
                        class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                </div>

                <div class="mb-3 col-1">
                    <button type="button" class="btn btn-primary align-end" data-bs-toggle="modal"
                        data-bs-target="#create_modal" style="height: 50px">
                        <i class="ri-community-fill fs-4"></i>
                    </button>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="main-box clearfix">
                    @if ($companies_l && $companies_l->count())
                        <div class="table-responsive">
                            <table class="table user-list">
                                <thead>
                                    <tr>
                                        <th><span>Empresa</span></th>
                                        <th><span>Municipio</span></th>
                                        <th><span>Email</span></th>
                                        <th><span>Telefone</span></th>
                                        <th><span>Criado em</span></th>
                                        <th class="text-center"><span>Status</span></th>

                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($companies_l as $company)
                                        <tr>
                                            <td>
                                                @if ($company->img_rw_path)
                                                    <img src="{{ asset('storage/' . $company->img_rw_path) }}"
                                                        alt="Logo da Empresa">
                                                @else
                                                    <img src="{{ asset('img/edp-img/edp-avatar.jpg') }}" alt="">
                                                @endif
                                                <a href="#"
                                                    class="user-link  @if ($company->trashed()) text-decoration-line-through text-danger @else text-dark @endif">{{ $company->name }}</a>
                                                @if (isset($company->address->first()->street))
                                                    <span
                                                        class="user-subhead mr-2">{{ $company->address->first()->street }}</span>
                                                @endif


                                            </td>
                                            <td>
                                                @if (isset($company->address->first()->city))
                                                    <span
                                                        class="user-subhead mr-2">{{ $company->address->first()->city }}
                                                        {{ $company->address->first()->uf }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="#" class="text-dark">{{ $company->email }}</a>
                                            </td>
                                            <td>
                                                {{ $company->telephone }}
                                            </td>
                                            <td>
                                                {{ date('d/m/Y', strToTime($company->created_at)) }}

                                            </td>

                                            <td class="text-center">
                                                @if ($company->trashed())
                                                    <span
                                                        class="label label-default badge text-bg-danger">Deletado</span>
                                                @else
                                                    <span class="label label-default badge text-bg-success">Ativo</span>
                                                @endif
                                            </td>

                                            <td style="width: 20%;">

                                                <a href="#" class="table-link">
                                                    <span class="fa-stack">
                                                        <i class="ri-eye-line btn btn-info btn-sm"></i>
                                                    </span>
                                                </a>
                                                <a href="#" class="table-link"
                                                    wire:click.prevent="$emitTo('admin.company.action.update', 'openModal', '{{ $company->id }}')">
                                                    <span class="fa-stack">
                                                        <i class="ri-pencil-fill btn btn-primary btn-sm"></i>
                                                    </span>
                                                </a>
                                                @if ($company->id !== Auth()->USer()->id)
                                                    @if (!$company->trashed())
                                                        <a href="#" class="table-link">
                                                            <span class="fa-stack"
                                                                wire:click.prevent="$emit('delete_company', '{{ $company->id }}')">
                                                                <i
                                                                    class="ri-delete-bin-2-fill btn btn-danger btn-sm"></i>
                                                            </span>
                                                        </a>
                                                    @else
                                                        <a href="#" class="table-link">
                                                            <span class="fa-stack"
                                                                wire:click.prevent="$emit('undelete_company', '{{ $company->id }}')">
                                                                <i
                                                                    class="ri-arrow-go-back-line btn btn-danger btn-sm"></i>
                                                            </span>
                                                        </a>
                                                    @endif
                                                @endif


                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <h4 class="text-center my-4 fw-bold">SEM EMPRESAS CADASTRADAS PARA EXIBIÇÃO</h4>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- MODAIS --}}

    @livewire('admin.company.delete')

    <div wire:ignore.self class="modal fade" id="create_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-community-fill fs-4 align-middle"></i> CRIAR EMPRESA</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('admin.company.create', key(hash('ripemd160', now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_create_company')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- <div wire:ignore.self class="modal fade" id="update_modal" tabindex="-1" aria-labelledby="update"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-user-add-fill fs-4 align-middle"></i> ATUALIZAR EMPRESA</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($show_update)
                        @livewire('admin.company.update', ['company_id' => $company_id], key(hash('ripemd160', now())))
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_update_company')">Salvar</button>
                </div>
            </div>
        </div>
    </div> --}}


    {{-- FIM MODAIS --}}
    @livewire('admin.company.action.update', key('company_update'))
</div>
