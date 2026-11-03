@php
    $canUpdate  = $this->can('admin_users.update');
    $canDisable = $this->can('admin_users.disable');
    $isActive   = $tab === 'active';
    $roleFilter = $this->filterValues('role');
    $presFilter = $this->filterValues('presence');
    $confirmAction = $confirming['action'] ?? null;
@endphp

{{-- USUÁRIOS DA EMPRESA TERCEIRA (lista de controle). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Escopo e regras: PartnerUserManager. --}}
<div class="closure-dash my-panel ops-list ops-control" x-data="opsSelection()">

    @if (session('status'))
        <div class="alert alert-success mb-3" role="status">{{ session('status') }}</div>
    @endif
    @error('file')
        <div class="alert alert-danger mb-3" role="alert">Não foi possível ler a planilha: {{ $message }}</div>
    @enderror

    <x-ops.hero class="ops-hero--control" eyebrow="Administração da parceira" :title="$managed?->name ?? 'Empresa parceira'"
        subtitle="Cadastre quem acessa o sistema pela sua empresa, redefina senhas e retire o acesso de quem saiu.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            @if ($this->can('admin_users.template_export'))
                <a class="btn btn-light btn-sm btn-dash" href="{{ route('partner.admin.users.import_template') }}"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Modelo de planilha</a>
            @endif
            @if ($this->can('admin_users.bulk_import'))
                <button type="button" class="btn btn-light btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#partnerUsersImport"><i class="ri-upload-2-line" aria-hidden="true"></i> Importar planilha</button>
            @endif
            @if ($this->can('admin_users.create'))
                <a class="btn btn-primary btn-sm btn-dash" href="{{ route('partner.admin.users.create') }}"><i class="ri-user-add-line" aria-hidden="true"></i> Novo usuário</a>
            @endif
        </div>
    </x-ops.hero>

    <section class="ops-stats" aria-label="Resumo dos usuários">
        <x-ops.stat tone="teal" label="Usuários ativos" :value="number_format($counts['active'], 0, ',', '.')" icon="ri-team-line" :active="$isActive && !$hasCriteria"
            wire:click.prevent="clearAllCriteria" title="Mostrar todos os usuários ativos" />
        <x-ops.stat tone="green" label="Online agora" :value="number_format($counts['online'], 0, ',', '.')" icon="ri-wifi-line" :active="$isActive && $presFilter === ['online']"
            wire:click.prevent="quickFilter('presence', 'online')" title="Filtrar quem está conectado" />
        <x-ops.stat tone="amber" label="Nunca acessaram" :value="number_format($counts['never'], 0, ',', '.')" icon="ri-user-unfollow-line" :active="$isActive && $presFilter === ['never']"
            wire:click.prevent="quickFilter('presence', 'never')" title="Filtrar quem ainda não entrou no sistema" />
        <x-ops.stat tone="blue" label="Administradores" :value="number_format($counts['admins'], 0, ',', '.')" icon="ri-shield-user-line" :active="$isActive && $roleFilter === ['admin']"
            wire:click.prevent="quickFilter('role', 'admin')" title="Filtrar administradores da empresa" />
        <x-ops.stat tone="red" label="Desativados" :value="number_format($counts['disabled'], 0, ',', '.')" icon="ri-user-forbid-line" :active="!$isActive"
            wire:click.prevent="setTab('disabled')" title="Ver quem está sem acesso" />
    </section>

    <section class="ops-toolbar" aria-label="Filtros dos usuários">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar por nome ou e-mail…" label="Buscar usuário" />

            <x-ops.segmented name="usersTab" label="Situação do acesso" wire:model="tab" :options="['active' => 'Com acesso', 'disabled' => 'Sem acesso']" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="Usuários">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">{{ $isActive ? 'Usuários com acesso' : 'Usuários sem acesso' }}</h2>
                <div class="mp-panel-sub">{{ number_format($users->total(), 0, ',', '.') }} {{ $users->total() === 1 ? 'usuário' : 'usuários' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="6" />

        <div wire:loading.delay.class="d-none">
            @if ($users->isEmpty())
                <x-closure.empty-state icon="ri-team-line"
                    :title="$hasCriteria ? 'Nenhum usuário com esses filtros' : ($isActive ? 'Nenhum usuário cadastrado ainda' : 'Ninguém está sem acesso')"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais usuários.' : ($isActive ? 'Cadastre o primeiro usuário ou importe uma planilha com vários de uma vez.' : 'Quando você desativar o acesso de alguém, ele aparece aqui e pode ser reativado.')" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 44px">
                            <col>
                            <col style="width: 22%">
                            <col style="width: 120px">
                            <col style="width: 150px">
                            <col style="width: 150px">
                            <col style="width: 130px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th><span class="visually-hidden">Selecionar</span></th>
                                <th>Usuário</th>
                                <th>Empresa</th>
                                <th>Perfil</th>
                                <th>Conexão</th>
                                <th>Último acesso</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $isMe     = (string) $user->id === $meId;
                                    $online   = $user->last_seen_at && $user->last_seen_at >= $onlineSince;
                                    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                                    $branches = $user->partnerBranchAddresses;
                                @endphp
                                <tr wire:key="user-{{ $user->id }}">
                                    <td><input class="form-check-input" type="checkbox" data-ops-row value="{{ $user->id }}" wire:model.defer="selected" aria-label="Selecionar {{ $user->name }}" @change="recount()"></td>
                                    <td>
                                        <div class="ops-person">
                                            <span class="ops-avatar" aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
                                            <div class="min-w-0">
                                                <div class="ops-person-name">{{ $user->name }} @if ($isMe)<x-ops.badge sm tone="blue" label="Você" />@endif</div>
                                                <div class="ops-person-sub" title="{{ $user->email }}">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $user->Company?->name ?? '—' }}</div>
                                        @if ($branches->isNotEmpty())
                                            <div class="ops-person-sub" title="{{ $branches->map(fn ($b) => trim($b->city . ' ' . $b->street))->implode(' · ') }}">{{ $branches->count() }} {{ $branches->count() === 1 ? 'endereço de filial' : 'endereços de filial' }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->admin)
                                            <x-ops.badge tone="blue" icon="ri-shield-user-line" label="Administrador" hint="Gerencia os usuários e as permissões da empresa" />
                                        @else
                                            <x-ops.badge tone="muted" label="Usuário" />
                                        @endif
                                    </td>
                                    <td>
                                        @if (!$isActive)
                                            <x-ops.badge tone="red" icon="ri-user-forbid-line" label="Sem acesso" :hint="'Desativado em ' . optional($user->deleted_at)->format('d/m/Y H:i')" />
                                        @elseif ($online)
                                            <x-ops.badge tone="green" icon="ri-wifi-line" label="Online" hint="Ativo nos últimos 5 minutos" />
                                        @elseif (!$user->last_login_at)
                                            <x-ops.badge tone="amber" icon="ri-time-line" label="Nunca acessou" hint="Ainda não entrou no sistema" />
                                        @elseif ($user->first_pass)
                                            <x-ops.badge tone="amber" icon="ri-key-2-line" label="Trocar senha" hint="Entrou, mas ainda não trocou a senha temporária" />
                                        @else
                                            <x-ops.badge tone="muted" label="Offline" />
                                        @endif
                                    </td>
                                    <td>
                                        <x-ops.date :date="$user->last_seen_at ?? $user->last_login_at" :time="true" />
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            @if ($isActive && $this->can('admin_users.update'))
                                                <a class="btn btn-outline-primary btn-sm ops-action" href="{{ route('partner.admin.users.edit', $user) }}"><i class="ri-edit-2-line" aria-hidden="true"></i> Editar</a>
                                            @elseif (!$isActive && $canDisable)
                                                <button type="button" class="btn btn-outline-success btn-sm ops-action" wire:click="ask('restore', '{{ $user->id }}')"><i class="ri-user-follow-line" aria-hidden="true"></i> Reativar</button>
                                            @endif

                                            @if ($isActive && ($canUpdate || $canDisable))
                                                <div class="dropdown ops-row-actions">
                                                    <button class="btn btn-outline-secondary btn-sm ops-row-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mais ações para {{ $user->name }}"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if ($canUpdate)
                                                            <li><button type="button" class="dropdown-item" wire:click="ask('reset_password', '{{ $user->id }}')"><i class="ri-key-2-line text-primary align-middle"></i> Redefinir senha</button></li>
                                                            @if ($user->admin && !$isMe)
                                                                <li><button type="button" class="dropdown-item" wire:click="ask('remove_admin', '{{ $user->id }}')"><i class="ri-shield-user-line align-middle"></i> Remover administrador</button></li>
                                                            @elseif (!$user->admin)
                                                                <li><button type="button" class="dropdown-item" wire:click="ask('make_admin', '{{ $user->id }}')"><i class="ri-shield-user-line text-primary align-middle"></i> Tornar administrador</button></li>
                                                            @endif
                                                            @if ($companies->count() > 1)
                                                                <li><button type="button" class="dropdown-item" wire:click="ask('move_company', '{{ $user->id }}')"><i class="ri-building-line align-middle"></i> Transferir de empresa</button></li>
                                                            @endif
                                                        @endif
                                                        @if ($canDisable && !$isMe)
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><button type="button" class="dropdown-item text-danger" wire:click="ask('disable', '{{ $user->id }}')"><i class="ri-user-forbid-line align-middle"></i> Desativar acesso</button></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$users" :per-page="$perPage" :sizes="[25, 50, 100]" />
            @endif
        </div>

        <x-ops.selection-bar>
            @if ($isActive)
                @if ($canUpdate)
                    <button type="button" class="btn btn-light btn-sm" wire:click="askSelected('reset_password')"><i class="ri-key-2-line" aria-hidden="true"></i> Redefinir senhas</button>
                    <button type="button" class="btn btn-light btn-sm" wire:click="askSelected('make_admin')"><i class="ri-shield-user-line" aria-hidden="true"></i> Tornar admin</button>
                    <button type="button" class="btn btn-light btn-sm" wire:click="askSelected('remove_admin')">Remover admin</button>
                    @if ($companies->count() > 1)
                        <button type="button" class="btn btn-light btn-sm" wire:click="askSelected('move_company')"><i class="ri-building-line" aria-hidden="true"></i> Transferir</button>
                    @endif
                @endif
                @if ($canDisable)
                    <button type="button" class="btn btn-danger btn-sm" wire:click="askSelected('disable')"><i class="ri-user-forbid-line" aria-hidden="true"></i> Desativar</button>
                @endif
            @elseif ($canDisable)
                <button type="button" class="btn btn-light btn-sm" wire:click="askSelected('restore')"><i class="ri-user-follow-line" aria-hidden="true"></i> Reativar acesso</button>
            @endif
        </x-ops.selection-bar>
    </section>

    {{-- Confirmação: explica o que muda antes de qualquer alteração de acesso. --}}
    @if ($confirming)
        <div class="modal fade show d-block ops-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="partnerUsersConfirmTitle">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content closure-dash ops-list">
                    <div class="modal-header">
                        <h5 class="modal-title" id="partnerUsersConfirmTitle">{{ $actions[$confirmAction]['label'] }}</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cancelConfirmation" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            @switch($confirmAction)
                                @case('reset_password')
                                    A senha volta para a <strong>senha temporária padrão</strong> e, no próximo acesso, a pessoa precisará criar uma nova. Avise-a depois de confirmar.
                                    @break
                                @case('make_admin')
                                    Administradores gerenciam os usuários e as permissões da empresa. Conceda só a quem precisa.
                                    @break
                                @case('remove_admin')
                                    A pessoa continua acessando o sistema, mas deixa de gerenciar usuários da empresa.
                                    @break
                                @case('move_company')
                                    O usuário passa a pertencer à empresa escolhida abaixo e enxerga os dados dela.
                                    @break
                                @case('disable')
                                    A pessoa <strong>perde o acesso imediatamente</strong>. Nada é apagado: o histórico é mantido e você pode reativar depois.
                                    @break
                                @case('restore')
                                    A pessoa volta a entrar com a senha que já tinha. Se não lembrar, use “Redefinir senha”.
                                    @break
                            @endswitch
                        </p>

                        @if ($confirmAction === 'move_company')
                            <label class="form-label fw-semibold" for="partnerUsersTarget">Empresa de destino</label>
                            <select id="partnerUsersTarget" class="form-select mb-3" wire:model="targetCompany">
                                <option value="">Selecione…</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}{{ $company->parent_id ? ' (filial)' : '' }}</option>
                                @endforeach
                            </select>
                        @endif

                        <div class="fw-semibold small mb-1">{{ $confirmUsers->count() === 1 ? 'Usuário' : $confirmUsers->count() . ' usuários' }}</div>
                        <ul class="list-unstyled mb-0">
                            @foreach ($confirmUsers->take(8) as $target)
                                <li class="ops-person-sub">{{ $target->name }} · {{ $target->email }}</li>
                            @endforeach
                            @if ($confirmUsers->count() > 8)
                                <li class="ops-person-sub">e mais {{ $confirmUsers->count() - 8 }}…</li>
                            @endif
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-dash" wire:click="cancelConfirmation">Cancelar</button>
                        <button type="button" class="btn {{ in_array($confirmAction, ['disable'], true) ? 'btn-danger' : 'btn-primary' }} btn-dash" wire:click="confirm" wire:loading.attr="disabled"
                            @disabled($confirmAction === 'move_company' && $targetCompany === '')>
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($this->can('admin_users.bulk_import'))
        <div wire:ignore.self class="modal fade ops-modal" id="partnerUsersImport" tabindex="-1" aria-labelledby="partnerUsersImportTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content closure-dash ops-list" method="POST" action="{{ route('partner.admin.users.import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="partnerUsersImportTitle">Importar usuários por planilha</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Baixe o modelo, preencha uma linha por pessoa e envie aqui. Você confere tudo numa pré-visualização antes de criar os acessos.</p>
                        <label class="form-label fw-semibold" for="partnerUsersFile">Planilha preenchida</label>
                        <input id="partnerUsersFile" class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-dash"><i class="ri-file-search-line" aria-hidden="true"></i> Pré-visualizar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
