@php
    use Carbon\Carbon;
@endphp

<div class="user-admin-page">
    <x-show-loading />
    <x-showselected :count="$selected" />

    <style>
        .user-admin-page {
            --ua-bg: #f5f7fb;
            --ua-surface: #ffffff;
            --ua-ink: #1f2937;
            --ua-muted: #6b7280;
            --ua-accent: #0f766e;
            --ua-border: #e5e7eb;
            background: radial-gradient(circle at 15% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--ua-bg);
            padding: 1.25rem 0;
        }

        .user-admin-page .hero {
            background: linear-gradient(130deg, #0f172a 0%, #0f766e 80%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.35rem 1.5rem;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .22);
        }

        .user-admin-page .hero h3 {
            font-weight: 700;
            margin: 0;
            letter-spacing: .02em;
        }

        .user-admin-page .hero .meta {
            color: rgba(248, 250, 252, .82);
            font-size: .9rem;
        }

        .user-admin-page .panel,
        .user-admin-page .table-card {
            background: var(--ua-surface);
            border: 1px solid var(--ua-border);
            border-radius: .95rem;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .user-admin-page .panel {
            padding: 1rem 1.1rem;
            height: 100%;
        }

        .user-admin-page .panel h6 {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ua-muted);
            font-weight: 700;
            margin-bottom: .85rem;
        }

        .user-admin-page .table-card .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
            padding: .85rem .75rem;
            vertical-align: middle;
        }

        .user-admin-page .users-table {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1120px;
        }

        .user-admin-page .users-table tbody td {
            background: #ffffff;
            border-bottom: 1px solid #e8edf3;
            padding: 1rem .8rem;
            vertical-align: middle;
        }

        .user-admin-page .users-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .user-admin-page .users-table tbody tr:hover td {
            background: #f7fafc;
        }

        .user-admin-page .users-table tbody tr.user-removed td {
            background: #fff5f5;
        }

        .user-admin-page .users-table tbody tr.user-removed:hover td {
            background: #feecec;
        }

        .user-admin-page .users-table thead {
            background: #1e293b;
            color: #ffffff;
        }

        .user-admin-page .users-table thead th {
            background: transparent;
            border: 0;
            color: rgba(255, 255, 255, .88);
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .user-admin-page .avatar {
            width: 48px;
            height: 48px;
            min-width: 48px;
            min-height: 48px;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid #dbe4ee;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .12);
        }

        .user-admin-page .user-name {
            color: var(--ua-ink);
            font-size: .98rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .user-admin-page .min-w-0 {
            min-width: 0;
        }

        .user-admin-page .user-id {
            display: block;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-admin-page .role-chip {
            font-size: .68rem;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: .12rem .45rem;
            background: #f9fafb;
            color: #334155;
        }

        .user-admin-page .user-meta {
            color: var(--ua-muted);
            font-size: .76rem;
            line-height: 1.45;
        }

        .user-admin-page .activity-chip {
            background: #eef2ff;
            border: 1px solid #dbe4ff;
            border-radius: .45rem;
            color: #334155;
            display: inline-block;
            font-size: .72rem;
            margin: 0 .2rem .25rem 0;
            padding: .18rem .45rem;
            white-space: nowrap;
        }

        .user-admin-page .company-name,
        .user-admin-page .contact-email {
            color: var(--ua-ink);
            font-weight: 600;
        }

        .user-admin-page .contact-email {
            display: block;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-admin-page .company-cell {
            max-width: 220px;
        }

        .user-admin-page .activities-cell {
            max-width: 300px;
        }

        .user-admin-page .action-buttons {
            display: flex;
            gap: .35rem;
            justify-content: center;
        }

        .user-admin-page .action-buttons .btn {
            align-items: center;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            padding: 0;
            width: 34px;
        }

        .user-admin-page .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            display: inline-block;
            margin-right: .35rem;
        }

        .user-admin-page .status-card {
            align-items: center;
            display: inline-flex;
            flex-direction: column;
            gap: .25rem;
            min-width: 92px;
        }

        .user-admin-page .status-card .badge {
            min-width: 78px;
            padding: .42rem .6rem;
        }

        .user-admin-page .summary-item {
            background: #ffffff;
            border: 1px solid var(--ua-border);
            border-radius: .7rem;
            padding: .6rem .8rem;
            min-width: 130px;
        }

        .user-admin-page .table-toolbar {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid var(--ua-border);
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: space-between;
            padding: .9rem 1rem;
        }

        .user-admin-page .table-toolbar-icon {
            align-items: center;
            background: #e7f6f3;
            border-radius: .65rem;
            color: var(--ua-accent);
            display: inline-flex;
            font-size: 1.25rem;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        @media (max-width: 991px) {
            .user-admin-page .hero {
                padding: 1.1rem;
            }

            .user-admin-page .summary-item {
                flex: 1 1 140px;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="hero d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-3">
            <div>
                <h3>Gestão de Usuários</h3>
                <div class="meta">Administração de acessos, perfis, empresas e atividades</div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                <button type="button" class="btn btn-light" wire:click.prevent="editInMass" @disabled(count($selected) <= 0)>
                    <i class="ri-user-settings-line align-middle"></i> Alterar em massa
                </button>
                <button type="button" class="btn btn-light" wire:click.prevent="$emitTo('admin.user.actions.usuario', 'newUser')">
                    <i class="ri-user-add-line align-middle"></i> Novo usuário
                </button>
                <button type="button" class="btn btn-warning" wire:click.prevent="export_excel" wire:loading.attr="disabled"
                    wire:target="export_excel">
                    <i class="ri-file-excel-2-line align-middle" wire:loading.remove wire:target="export_excel"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                        wire:target="export_excel"></span>
                    Exportar
                </button>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="summary-item">
                <small class="text-muted d-block">Usuários na lista</small>
                <strong>{{ $totalUsers }}</strong>
            </div>
            <div class="summary-item">
                <small class="text-muted d-block">Online</small>
                <strong>{{ $onlineUsers }}</strong>
            </div>
            <div class="summary-item">
                <small class="text-muted d-block">Página atual</small>
                <strong>30 registros</strong>
            </div>
            <div class="summary-item">
                <small class="text-muted d-block">Selecionados</small>
                <strong>{{ count($selected) }}</strong>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-5">
                <div class="panel">
                    <h6>Pesquisa</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="searchBy" wire:model="searchBy">
                                    <option value="all">Nome, email, ID</option>
                                    <option value="email">Email</option>
                                    <option value="registration">Matrícula</option>
                                    <option value="id">ID</option>
                                </select>
                                <label for="searchBy">Pesquisar por</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-search-line"></i></span>
                                <input type="text" class="form-control" placeholder="Buscar usuário"
                                    wire:model.debounce.600ms="search">
                                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                    <i class="ri-checkbox-multiple-blank-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 col-xl-3">
                <div class="panel">
                    <h6>Escopo</h6>
                    <div class="form-floating mb-2">
                        <select class="form-select" id="selectedCompany" wire:model="selectedCompany">
                            <option value="">Todas as empresas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        <label for="selectedCompany">Empresa</label>
                    </div>
                    <div class="form-floating">
                        <select class="form-select" id="roleFilter" wire:model="roleFilter">
                            <option value="">Todos os perfis</option>
                            <option value="superadm">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="management">Gerente</option>
                            <option value="engineer">Engenheiro</option>
                            <option value="responsible">Responsável</option>
                            <option value="operator">Operador</option>
                            <option value="user">Usuário</option>
                            <option value="onlyparner">Empreiteira</option>
                            <option value="analyst">Analista</option>
                        </select>
                        <label for="roleFilter">Perfil</label>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 col-xl-4">
                <div class="panel">
                    <h6>Status e Lixeira</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">Conexão</small>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="statusFilter" id="statusAll" value="all" wire:model="statusFilter">
                            <label class="btn btn-outline-secondary" for="statusAll">Todos</label>

                            <input type="radio" class="btn-check" name="statusFilter" id="statusOnline" value="online" wire:model="statusFilter">
                            <label class="btn btn-outline-success" for="statusOnline">Online</label>

                            <input type="radio" class="btn-check" name="statusFilter" id="statusOffline" value="offline" wire:model="statusFilter">
                            <label class="btn btn-outline-dark" for="statusOffline">Offline</label>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block mb-1">Exibição</small>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="deletedFilter" id="deletedActive" value="active" wire:model="deletedFilter">
                            <label class="btn btn-outline-primary" for="deletedActive">Ativos</label>

                            <input type="radio" class="btn-check" name="deletedFilter" id="deletedAll" value="all" wire:model="deletedFilter">
                            <label class="btn btn-outline-secondary" for="deletedAll">Todos</label>

                            <input type="radio" class="btn-check" name="deletedFilter" id="deletedOnly" value="deleted" wire:model="deletedFilter">
                            <label class="btn btn-outline-danger" for="deletedOnly">Só removidos</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            @if ($users_l->count())
                <div class="table-toolbar">
                    <div class="d-flex align-items-center gap-3">
                        <span class="table-toolbar-icon"><i class="ri-team-line"></i></span>
                        <div>
                            <h5 class="mb-0 fw-semibold">Usuários cadastrados</h5>
                            <small class="text-muted">Selecione usuários para executar ações em massa.</small>
                        </div>
                    </div>
                    <span class="badge text-bg-light border text-dark">{{ $users_l->total() }} registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table users-table table-hover align-middle mb-0">
                        <thead class="text-center">
                            <tr>
                                <th style="width: 32px;">
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                        wire:click="setSelectAll()" @checked($this->checkAllSelect($users_l))>
                                </th>
                                <th class="text-start">Usuário</th>
                                <th class="text-start">Contato</th>
                                <th class="text-start">Empresa / Contrato</th>
                                <th class="text-start">Atividades</th>
                                <th>Status</th>
                                <th style="width: 170px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users_l as $theUser)
                                @php
                                    $active = isset($theUser->Watchdog->watchdog) && $theUser->Watchdog->watchdog;
                                    $roles = [];
                                    if ($theUser->superadm) {
                                        $roles[] = 'Super';
                                    }
                                    if ($theUser->admin) {
                                        $roles[] = 'Admin';
                                    }
                                    if ($theUser->management) {
                                        $roles[] = 'Gerente';
                                    }
                                    if ($theUser->engineer) {
                                        $roles[] = 'Engenheiro';
                                    }
                                    if ($theUser->responsible) {
                                        $roles[] = 'Responsável';
                                    }
                                    if ($theUser->operator) {
                                        $roles[] = 'Operador';
                                    }
                                    if ($theUser->user) {
                                        $roles[] = 'Usuário';
                                    }
                                    if ($theUser->onlyparner) {
                                        $roles[] = 'Empreiteira';
                                    }
                                    if ($theUser->analyst) {
                                        $roles[] = 'Analista';
                                    }
                                @endphp
                                <tr class="text-center @if ($theUser->trashed()) user-removed @endif"
                                    wire:key="user-row-{{ $theUser->id }}">
                                    <td>
                                        <input class="form-check-input border border-primary" type="checkbox"
                                            value="{{ $theUser->id }}" wire:model.defer="selected">
                                    </td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-start gap-3">
                                            <img src="{{ $theUser->avatar_url }}" alt="Avatar {{ $theUser->name }}"
                                                class="avatar"
                                                onerror="this.onerror=null;this.src='{{ asset('img/user.png') }}';">
                                            <div class="min-w-0">
                                                <div class="user-name">{{ $theUser->name }}</div>
                                                <small class="user-id text-muted" title="{{ $theUser->id }}">
                                                    ID {{ $theUser->id }}
                                                </small>
                                                @if ($theUser->Registration)
                                                    <small class="user-meta d-block">
                                                        Matrícula {{ $theUser->Registration }}
                                                    </small>
                                                @endif
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    @forelse ($roles as $role)
                                                        <span class="role-chip">{{ $role }}</span>
                                                    @empty
                                                        <span class="role-chip">Sem perfil</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="contact-email" title="{{ $theUser->email }}">
                                            <i class="ri-mail-line text-muted me-1"></i>{{ $theUser->email }}
                                        </div>
                                        @if ($theUser->can_dispatch)
                                            <span class="badge text-bg-success-subtle text-success-emphasis mt-1">
                                                <i class="ri-send-plane-line me-1"></i>Pode despachar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-start company-cell">
                                        <div class="company-name">{{ isset($theUser->Employee->Contract->Company->name) ? mb_strtoupper($theUser->Employee->Contract->Company->name) : '-' }}</div>
                                        <small class="user-meta">
                                            <i class="ri-file-text-line me-1"></i>Contrato:
                                            {{ $theUser->Employee->Contract->number ?? '-' }}
                                        </small>
                                    </td>
                                    <td class="text-start activities-cell">
                                        @if ($theUser->ToServices->count())
                                            <div>
                                                @foreach ($theUser->ToServices->take(3) as $service)
                                                    <span class="activity-chip">
                                                        {{ $service->Service->service ?? '-' }}
                                                        {{ $service->service ? 'S' : '-' }}/{{ $service->dispatch ? 'D' : '-' }}
                                                    </span>
                                                @endforeach
                                                @if ($theUser->ToServices->count() > 3)
                                                    <span class="activity-chip fw-semibold">
                                                        +{{ $theUser->ToServices->count() - 3 }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">Sem atividades</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="status-card">
                                        @if ($theUser->trashed())
                                            <span class="badge text-bg-danger">REMOVIDO</span>
                                        @elseif($active)
                                            <span class="badge text-bg-success"><span class="status-dot bg-white"></span>ONLINE</span>
                                        @else
                                            <span class="badge text-bg-secondary">OFFLINE</span>
                                            <div class="small text-muted mt-1">
                                                {{ isset($theUser->Watchdog->updated_at) ? Carbon::parse($theUser->Watchdog->updated_at)->diffForHumans(Carbon::now()) : 'Nunca acessou' }}
                                            </div>
                                        @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            @if (!$theUser->trashed())
                                                @if (Auth()->User()->superadm && $theUser->id !== $master->id)
                                                    <a href="{{ route('impersonate', $theUser->id) }}"
                                                        class="btn btn-sm btn-outline-info"
                                                        title="Visualizar como usuário">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                @endif

                                                @if ($theUser->id !== $master->id || Auth()->User()->id == $theUser->id)
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                        title="Editar usuário"
                                                        wire:click.prevent="$emitTo('admin.user.actions.usuario', 'openUser', {{ $theUser }})">
                                                        <i class="ri-edit-line"></i>
                                                    </button>
                                                @endif

                                                @if ($theUser->id !== Auth()->User()->id && $theUser->id !== $master->id)
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        title="Remover usuário"
                                                        wire:click.prevent="$emitTo('admin.user.delete','delete_user', '{{ $theUser->id }}')">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                @endif
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    title="Restaurar usuário"
                                                    wire:click.prevent="$emitTo('admin.user.delete','undelete_user', '{{ $theUser->id }}')">
                                                    <i class="ri-arrow-go-back-line"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center">
                    <h5 class="mb-1">Nenhum usuário encontrado</h5>
                    <p class="text-muted mb-0">Ajuste os filtros para ampliar a busca.</p>
                </div>
            @endif

            <div class="row p-3 align-items-center">
                <div class="col-md-6">
                    {{ $users_l->links() }}
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">
                        Exibindo {{ $users_l->firstItem() ?? 0 }} até {{ $users_l->lastItem() ?? 0 }} de {{ $users_l->total() }} registros.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title mb-0">Busca múltipla de usuários</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Informe uma linha por ID, e-mail ou matrícula.</p>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="8"
                        wire:model.defer="preText"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="multiSearch">Aplicar</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('admin.user.actions.usuario', key('users'))
    @livewire('admin.user.actions.usuario-mass', key('the_users-mass'))
    @livewire('admin.user.delete', key('delete-user'))
</div>
