@php
    use Carbon\Carbon;
@endphp

<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="container-fluid">

        <div class="card edp-bg-gray">
            <div
                class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark d-flex justify-content-between align-items-center">
                <h4 class="card-title my-0 py-0 align-start">USUÁRIOS</h4>
                <div>
                    <button type="button" class="btn btn-primary" wire:click.prevent="editInMass"
                        @disabled(count($selected) <= 0)><i class="ri-user-add-line align-middle"></i> Alterar Usuário em
                        Massa</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emitTo('admin.user.actions.usuario', 'newUser')"><i
                            class="ri-user-add-line align-middle"></i> Novo
                        Usuário</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="export_excel"
                        wire:loading.attr="disabled" wire:target="export_excel"><i
                            class="ri-file-excel-2-line align-middle" wire:loading.remove></i>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                            wire:loading></span>

                    </button>
                </div>
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
                    <div class="col-md-4 ">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar usuário..."
                                wire:model="search">
                            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                                    class="ri-checkbox-multiple-blank-line"></i></button>
                        </div>

                    </div>
                </div>
            </div>
            @if ($users_l->count())
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm my-0">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 30px">
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                        wire:click="setSelectAll()" @checked($this->checkAllSelect($users_l))>
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
                                <tr class="align-middle text-center @if ($theUser->trashed()) table-danger text-light text-decoration-line-through @endif"
                                    wire:key='theusers-{{ $theUser->id }}'>
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
                                        @if ($theUser->responsible)
                                            <span class="user-subhead me-2" tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="left"
                                                data-bs-title="RESPONSÁVEL"
                                                data-bs-content="Tem informações aos serviços que foi colocado como Responsável"><i
                                                    class="ri-user-2-fill text-primary fs-5"></i></span>
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
                                        @if ($theUser->trashed())
                                            <span class="badge text-bg-danger">REMOVIDO</span>
                                        @else
                                            @if ($active)
                                                <span class="badge text-bg-success">ONLINE</span>
                                            @else
                                                <span class="badge text-bg-secondary">OFFLINE</span>
                                                <p class="mt-1 mb-0"><span class="fw-bold">Visto em:</span>
                                                    {{ isset($theUser->Watchdog->updated_at) ? Carbon::parse($theUser->Watchdog->updated_at)->diffForHumans(Carbon::now()) : 'Nunca Entrou' }}
                                                </p>
                                            @endif
                                        @endif
                                    </td>
                                    <td>



                                        @if (!$theUser->trashed())
                                            @if (Auth()->User()->superadm && $theUser->id !== $master->id)
                                                <a href="{{ route('impersonate', $theUser->id) }}" class="table-link">

                                                    <i class="ri-eye-line fs-5 text-info cursor-pointer me-2"
                                                        title="Ver" style="cursor: pointer;"></i>
                                                </a>
                                            @endif

                                            @if ($theUser->id !== $master->id || Auth()->User()->id == $theUser->id)
                                                <i wire:click.prevent="$emitTo('admin.user.actions.usuario', 'openUser', {{ $theUser }})"
                                                    class="ri-edit-line fs-5 text-warning cursor-pointer me-2"
                                                    title="Editar" style="cursor: pointer;"></i>
                                            @endif

                                            @if ($theUser->id !== Auth()->User()->id && $theUser->id !== $master->id)
                                                <i class="ri-delete-bin-line fs-5 text-danger cursor-pointer"
                                                    title="Excluir" style="cursor: pointer;"
                                                    wire:click.prevent="$emitTo('admin.user.delete','delete_user', '{{ $theUser->id }}')"></i>
                                            @endif
                                        @else
                                            <i class="ri-arrow-go-back-line fs-5 text-danger cursor-pointer"
                                                title="Excluir" style="cursor: pointer;"
                                                wire:click.prevent="$emitTo('admin.user.delete','undelete_user', '{{ $theUser->id }}')"></i>
                                        @endif


                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <h4 class="text-center">USUARIO NAO ENCONTRADO</h4>
                    </div>
                </div>
            @endif
            <div class="row p-3">
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
        </div>

    </div>

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    BUSCAR MULTI-USUÁRIOS
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="preText"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="multiSearch">OK</button>
                </div>
            </div>

        </div>

    </div>

    <!-- Exibir os dados do clipboard com formatação para Excel -->
    <textarea id="clipboard-data" style="display: none;">

    </textarea>

    {{-- Livewire Components --}}
    @livewire('admin.user.actions.usuario', key('users'))
    @livewire('admin.user.actions.usuario-mass', key('the_users-mass'))
    @livewire('admin.user.delete', key('delete-user'))

    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notificação</strong>
            <button type="button" class="btn-close"   data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Texto copiado com sucesso!
        </div>
    </div>



    @push('script')
        <script>
            window.addEventListener('copySicodeAccess', async event => {


                // Monta o texto no formato desejado
                const acessoText = [
                    '==== DADOS USUÁRIO ====',
                    '',
                    `Nome: ${event.detail.name}`,
                    `Empresa: ${event.detail.company}`,
                    '',
                    '=== ACESSO AO SICODE ===',
                    `email: ${event.detail.email}`,
                    'Senha: 123456',
                    'Servidor: http://edpbr1204/es/',
                    '====================',
                    'Gerado em: ' + new Date().toLocaleString(),
                ].join('\n');

                console.log(acessoText);

                try {
                    await navigator.clipboard.writeText(acessoText);
                    Livewire.emit('getCopy', 'Acesso ao SICODE copiado para a área de transferência');
                } catch (err) {
                    console.error('Falha ao copiar no Clipboard API', err);
                }

                // Cria um textarea temporário para copiar o texto
                // const textArea2 = document.createElement('textarea');
                // textArea2.value = acessoText;
                // document.body.appendChild(textArea2);
                // textArea2.select();
                // document.execCommand('copy');

                // document.body.removeChild(textArea2);


                // Opcional: notifica via Livewire que copiou
                Livewire.emit('getCopy', 'Acesso ao SICODE copiado para a área de transferência');
            });
        </script>
    @endpush



</div>
