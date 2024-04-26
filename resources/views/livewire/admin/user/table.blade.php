@php
    use Carbon\Carbon;
@endphp

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

                @can('superadm')
                    <div class="mb-3 col-3">
                        <label for="email" class="form-label">Empresa</label>
                        <select class="form-select form-select border border-2 border-secondary" aria-label=""
                            wire:model='company_s'>
                            @if ($companies->count())
                                <option selected>Selecione uma Empresa</option>
                                <option value="">TODAS</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ mb_strtoupper($company->name) }}</option>
                                @endforeach
                            @else
                                <option selected disabled>Nenhuma empresa com contrato</option>
                            @endif
                        </select>
                    </div>
                @endcan

                <div class="mb-3 col-1">
                    <button type="button" class="btn btn-primary align-end" data-bs-toggle="modal"
                        data-bs-target="#create_modal" style="height: 50px">
                        <i class="ri-user-add-fill fs-4"></i>
                    </button>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="main-box clearfix">
                    @if ($users_l && $users_l->count())
                        <div class="row">
                            <div class="col-6">
                                {{ $users_l->links() }}
                            </div>
                            <div class="col-6 d-flex justify-content-end align-middle">
                                <span class="align-middle"> Exibindo {{ $users_l->firstItem() }} até
                                    {{ $users_l->lastItem() }}
                                    de {{ $users_l->total() }}
                                    registros.
                                </span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table user-list">
                                <thead>
                                    <tr>
                                        <th><span>Usuário</span></th>
                                        <th class="text-center"><span>Empresa</span></th>
                                        <th><span>Criado em</span></th>
                                        <th class="text-center"><span>Status</span></th>
                                        <th><span>Email</span></th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users_l as $user)
                                        <tr wire:key='line-user-{{ $user->id }}'>
                                            <td>
                                                {{-- @php
                                                    $name = explode(' ', auth()->user()->name);
                                                    $name = end($name) . $name[0];
                                                @endphp --}}
                                                {{-- <img src="https://bootdey.com/img/Content/avatar/avatar8.png"
                                                    alt=""> --}}
                                                <img src="https://api.dicebear.com/8.x/pixel-art/svg?seed={{ $user->email }}"
                                                    alt="">

                                                <a href="#"
                                                    class="user-link  @if ($user->trashed()) text-decoration-line-through text-danger @else text-dark @endif">{{ $user->name }}</a>
                                                @if ($user->superadm)
                                                    <span class="user-subhead me-2" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="left"
                                                        data-bs-title="ADMINISTRADOR DO SISTEMA"
                                                        data-bs-content="Administra todo o sistema adicionando novos SUPERADM e Inferiores"><i
                                                            class="ri-home-gear-fill text-danger fs-5"></i></span>
                                                @endif
                                                @if ($user->admin)
                                                    <span class="user-subhead me-2" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="left" data-bs-title="ADMINISTRADOR"
                                                        data-bs-content="Administra informações da sua empresa adicionando novos ADMIN e Inferiores"><i
                                                            class="ri-home-gear-line text-primary fs-5"></i></span>
                                                @endif
                                                @if ($user->management)
                                                    <span class="user-subhead me-2" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="left" data-bs-title="GERENTE"
                                                        data-bs-content="Acesso a informações de Relatórios entre outros."><i
                                                            class="ri-user-voice-fill text-info fs-5"></i></span>
                                                @endif
                                                @if ($user->engineer)
                                                    <span class="user-subhead me-2" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="left" data-bs-title="ENGENHEIRO"
                                                        data-bs-content="Tem informações aos serviços que foi colocado como Responsável"><i
                                                            class="ri-user-2-fill text-info fs-5"></i></span>
                                                @endif
                                                @if ($user->operator)
                                                    <span class="user-subhead me-2" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="left" data-bs-title="OPERADOR"
                                                        data-bs-content="Tem Acesso a área de Controle, atribuindo serviços para outros usuários."><i
                                                            class="ri-user-voice-line text-primary fs-5"></i></span>
                                                @endif
                                                @if ($user->user)
                                                    <span class="user-subhead me-2" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="left" data-bs-title="USUÁRIO"
                                                        data-bs-content="Apenas acesso básico ao sistema, e acesso aos serviços a qual foi designado a realizar."><i
                                                            class="ri-user-line text-secondary fs-5"></i></span>
                                                @endif

                                            </td>
                                            <td class="text-center py-auto">
                                                <div class="align-middle">
                                                    <span
                                                        class="fs-6 fw-bold">{{ isset($user->Employee->Contract->Company->name) ? mb_strtoupper($user->Employee->Contract->Company->name) : '' }}</span>
                                                    <p class="user-subhead">
                                                        {{ isset($user->Employee->Contract->number) ? mb_strtoupper($user->Employee->Contract->number) : '' }}
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                {{ date('d/m/Y', strToTime($user->created_at)) }}
                                            </td>
                                            @php
                                                $active = false;

                                                if (isset($user->Watchdog->watchdog) && $user->Watchdog->watchdog) {
                                                    $active = true;
                                                }

                                            @endphp
                                            <td class="text-center">

                                                @if ($active)
                                                    <span class="badge fs-6 text-bg-success">
                                                        ONLINE
                                                    </span>
                                                @else
                                                    <span class="align-middle my-auto">
                                                        <span class="badge fs-6 text-bg-danger">
                                                            OFFLINE
                                                        </span>
                                                        <p class="mt-1 py-0"><span class="fw-bold">Visto em:</span>
                                                            {{ isset($user->Watchdog->updated_at) ? Carbon::parse($user->Watchdog->updated_at)->diffForHumans(Carbon::now()) : 'Nunca Entrou' }}
                                                        </p>
                                                    </span>
                                                @endif


                                            </td>
                                            <td>
                                                <a href="#" class="text-dark">{{ $user->email }}</a>
                                            </td>
                                            <td style="width: 20%;">


                                                <a href="#" class="table-link"
                                                    wire:click.prevent="update_user('{{ $user->id }}')">
                                                    <span class="fa-stack">
                                                        <i class="ri-pencil-fill btn btn-primary btn-sm"></i>
                                                    </span>
                                                </a>
                                                @if ($user->id !== Auth()->USer()->id)
                                                    @if (!$user->trashed())
                                                        <a href="#" class="table-link">
                                                            <span class="fa-stack"
                                                                wire:click.prevent="$emit('delete_user', '{{ $user->id }}')">
                                                                <i
                                                                    class="ri-delete-bin-2-fill btn btn-danger btn-sm"></i>
                                                            </span>
                                                        </a>
                                                    @else
                                                        <a href="#" class="table-link">
                                                            <span class="fa-stack"
                                                                wire:click.prevent="$emit('undelete_user', '{{ $user->id }}')">
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
                        <div class="row">
                            <div class="col-6">
                                {{ $users_l->links() }}
                            </div>
                            <div class="col-6 d-flex justify-content-end align-middle">
                                <span class="align-middle"> Exibindo {{ $users_l->firstItem() }} até
                                    {{ $users_l->lastItem() }}
                                    de {{ $users_l->total() }}
                                    registros.</span>
                            </div>
                        </div>
                    @else
                        <h4 class="text-center">SEM USUÁRIOS PARA EXIBIÇÃO</h4>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- MODAIS --}}

    @livewire('admin.user.delete')

    <div wire:ignore.self class="modal fade" id="create_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-user-add-fill fs-4 align-middle"></i> CRIAR UASUÁRIO</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('admin.user.create', key(hash('ripemd160', now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_create_user')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="update_modal" tabindex="-1" aria-labelledby="update"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-user-add-fill fs-4 align-middle"></i> ATUALIZAR USUÁRIO</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($show_update)
                        @livewire('admin.user.update', ['user_id' => $user_id], key(hash('ripemd160', now())))
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_update_user')">Salvar</button>
                </div>
            </div>
        </div>
    </div>


    {{-- FIM MODAIS --}}

</div>
