@php
    use Carbon\Carbon;
@endphp

<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="container-fluid">
        <div class="card edp-bg-gray">
            <div
                class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark d-flex justify-content-between align-items-center">
                <h4 class="card-title my-0 py-0">USUÁRIOS</h4>
                <button type="button" class="btn btn-primary"><i class="ri-user-add-line align-middle"></i> Novo
                    Usuário</button>
            </div>
            <div class="card-body my-0">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" wire:model="selectedCompany">
                            <option value="">Selecione uma Empresa</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Buscar usuário..." wire:model="search">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm my-0">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 30px">
                                <input class="form-check-input" type="checkbox" wire:model="selectall">
                            </th>
                            <th scope="col" style="width: 50px">Avatar</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Email</th>
                            <th scope="col">Empresa</th>
                            <th scope="col">Status</th>
                            <th scope="col" style="width: 150px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users_l as $theUser)
                            <tr class="align-middle text-center" wire:key='theusers-{{ $theUser->id }}'>
                                <td>
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $theUser->id }}" wire:model.defer="selected">
                                </td>
                                <td>
                                    <img src="https://api.dicebear.com/8.x/pixel-art/svg?seed={{ $theUser->email }}"
                                        alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px;">
                                </td>
                                <td>
                                    <p class="my-0 py-0">{{ $theUser->name }}</p>
                                    @if ($theUser->superadm)
                                        <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="left"
                                            data-bs-title="ADMINISTRADOR DO SISTEMA"
                                            data-bs-content="Administra todo o sistema adicionando novos SUPERADM e Inferiores"><i
                                                class="ri-home-gear-fill text-danger fs-5"></i></span>
                                    @endif
                                    @if ($theUser->admin)
                                        <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="left"
                                            data-bs-title="ADMINISTRADOR"
                                            data-bs-content="Administra informações da sua empresa adicionando novos ADMIN e Inferiores"><i
                                                class="ri-home-gear-line text-primary fs-5"></i></span>
                                    @endif
                                    @if ($theUser->management)
                                        <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="left"
                                            data-bs-title="GERENTE"
                                            data-bs-content="Acesso a informações de Relatórios entre outros."><i
                                                class="ri-user-voice-fill text-info fs-5"></i></span>
                                    @endif
                                    @if ($theUser->engineer)
                                        <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="left"
                                            data-bs-title="ENGENHEIRO"
                                            data-bs-content="Tem informações aos serviços que foi colocado como Responsável"><i
                                                class="ri-user-2-fill text-info fs-5"></i></span>
                                    @endif
                                    @if ($theUser->operator)
                                        <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="left"
                                            data-bs-title="OPERADOR"
                                            data-bs-content="Tem Acesso a área de Controle, atribuindo serviços para outros usuários."><i
                                                class="ri-user-voice-line text-primary fs-5"></i></span>
                                    @endif
                                    @if ($theUser->user)
                                        <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="left"
                                            data-bs-title="USUÁRIO"
                                            data-bs-content="Apenas acesso básico ao sistema, e acesso aos serviços a qual foi designado a realizar."><i
                                                class="ri-user-line text-secondary fs-5"></i></span>
                                    @endif
                                </td>
                                <td>{{ $theUser->email }}</td>
                                <td>{{ isset($theUser->Employee->Contract->Company->name) ? mb_strtoupper($theUser->Employee->Contract->Company->name) : '' }}
                                </td>
                                @php
                                    $active = isset($theUser->Watchdog->watchdog) && $theUser->Watchdog->watchdog;
                                @endphp
                                <td>
                                    @if ($active)
                                        <span class="badge text-bg-success">ONLINE</span>
                                    @else
                                        <span class="badge text-bg-danger">OFFLINE</span>
                                        <p class="mt-1 mb-0"><span class="fw-bold">Visto em:</span>
                                            {{ isset($theUser->Watchdog->updated_at) ? Carbon::parse($theUser->Watchdog->updated_at)->diffForHumans(Carbon::now()) : 'Nunca Entrou' }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <i class="ri-eye-line fs-5 text-info cursor-pointer me-2" title="Ver"
                                        style="cursor: pointer;"></i>
                                    <i wire:click.prevent="$emitTo('admin.user.actions.usuario', 'openUser', {{ $theUser }})"
                                        class="ri-edit-line fs-5 text-warning cursor-pointer me-2" title="Editar"
                                        style="cursor: pointer;"></i>
                                    <i class="ri-delete-bin-line fs-5 text-danger cursor-pointer" title="Excluir"
                                        style="cursor: pointer;"></i>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- Livewire Components --}}
    @livewire('admin.user.actions.usuario', key('users'))
</div>
