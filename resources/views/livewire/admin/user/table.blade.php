@php
    use Carbon\Carbon;
@endphp

<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title my-0 py-0">USUÁRIOS</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped table-sm">
                            <thead>
                                <tr class='align-middle text-center'>
                                    <th style="width: 30px">
                                        <input class="form-check-input" type="checkbox" wire:model="selectall">
                                    </th>
                                    <th scope="col" class="" style="width: 40px">Avatar</th>
                                    <th scope="col" class="">Nome</th>
                                    <th scope="col" class="">Email</th>
                                    <th scope="col" class="">Empresa</th>
                                    <th scope="col" class="">Status</th>
                                    <th scope="col" class="">DtCriação</th>
                                    <th scope="col" class=""></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users_l as $user)
                                    <tr class="align-middle text-center" wire:key='Key-{{ $user->id }}'>
                                        <td class="align-middle text-center">
                                            <input class="form-check-input border border-1 border-primary"
                                                type="checkbox" value="{{ $user->id }}" wire:model.defer="selected">
                                        </td>
                                        <td><img src="https://api.dicebear.com/8.x/pixel-art/svg?seed={{ $user->email }}"
                                                alt=""></td>
                                        <td class="align-middle text-center">
                                            <p class="my-0 py-0">{{ $user->name }}</p>
                                            @if ($user->superadm)
                                                <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="left"
                                                    data-bs-title="ADMINISTRADOR DO SISTEMA"
                                                    data-bs-content="Administra todo o sistema adicionando novos SUPERADM e Inferiores"><i
                                                        class="ri-home-gear-fill text-danger fs-5"></i></span>
                                            @endif
                                            @if ($user->admin)
                                                <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="left"
                                                    data-bs-title="ADMINISTRADOR"
                                                    data-bs-content="Administra informações da sua empresa adicionando novos ADMIN e Inferiores"><i
                                                        class="ri-home-gear-line text-primary fs-5"></i></span>
                                            @endif
                                            @if ($user->management)
                                                <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="left"
                                                    data-bs-title="GERENTE"
                                                    data-bs-content="Acesso a informações de Relatórios entre outros."><i
                                                        class="ri-user-voice-fill text-info fs-5"></i></span>
                                            @endif
                                            @if ($user->engineer)
                                                <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="left"
                                                    data-bs-title="ENGENHEIRO"
                                                    data-bs-content="Tem informações aos serviços que foi colocado como Responsável"><i
                                                        class="ri-user-2-fill text-info fs-5"></i></span>
                                            @endif
                                            @if ($user->operator)
                                                <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="left"
                                                    data-bs-title="OPERADOR"
                                                    data-bs-content="Tem Acesso a área de Controle, atribuindo serviços para outros usuários."><i
                                                        class="ri-user-voice-line text-primary fs-5"></i></span>
                                            @endif
                                            @if ($user->user)
                                                <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="left"
                                                    data-bs-title="USUÁRIO"
                                                    data-bs-content="Apenas acesso básico ao sistema, e acesso aos serviços a qual foi designado a realizar."><i
                                                        class="ri-user-line text-secondary fs-5"></i></span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">{{ $user->email }}</td>
                                        <td class="align-middle text-center"> <span
                                                class="fs-6 fw-bold">{{ isset($user->Employee->Contract->Company->name) ? mb_strtoupper($user->Employee->Contract->Company->name) : '' }}</span>
                                            <p class="py-0 my-0">
                                                {{ isset($user->Employee->Contract->number) ? mb_strtoupper($user->Employee->Contract->number) : '' }}
                                            </p>
                                        </td>
                                        @php
                                            $active = false;

                                            if (isset($user->Watchdog->watchdog) && $user->Watchdog->watchdog) {
                                                $active = true;
                                            }

                                        @endphp
                                        <td class="align-middle text-center">
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
                                        <td class="align-middle text-center"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="button" class="btn btn-primary align-center mb-3"><i
                                class="ri-user-add-line align-middle"></i>
                            Criar Usuário</button>
                        <button type="button" class="btn btn-primary align-center mb-3"><i
                                class="ri-user-settings-line align-middle"></i>
                            Editar Usuários</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
