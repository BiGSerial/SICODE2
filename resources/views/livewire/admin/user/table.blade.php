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
        }

        .user-admin-page .avatar {
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid #dbe4ee;
        }

        .user-admin-page .role-chip {
            font-size: .72rem;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: .15rem .5rem;
            background: #f9fafb;
            color: #334155;
        }

        .user-admin-page .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            display: inline-block;
            margin-right: .35rem;
        }

        .user-admin-page .summary-item {
            background: #ffffff;
            border: 1px solid var(--ua-border);
            border-radius: .7rem;
            padding: .6rem .8rem;
            min-width: 130px;
        }

        @media (max-width: 991px) {
            .user-admin-page .hero {
                padding: 1.1rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="hero d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-3">
            <div>
                <h3>Gestao de Usuarios</h3>
                <div class="meta">Lista otimizada com filtros de status, perfis e busca avançada</div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                <button type="button" class="btn btn-light" wire:click.prevent="editInMass" @disabled(count($selected) <= 0)>
                    <i class="ri-user-settings-line align-middle"></i> Alterar em massa
                </button>
                <button type="button" class="btn btn-light" wire:click.prevent="$emitTo('admin.user.actions.usuario', 'newUser')">
                    <i class="ri-user-add-line align-middle"></i> Novo usuario
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
                <small class="text-muted d-block">Usuarios na lista</small>
                <strong>{{ $totalUsers }}</strong>
            </div>
            <div class="summary-item">
                <small class="text-muted d-block">Online</small>
                <strong>{{ $onlineUsers }}</strong>
            </div>
            <div class="summary-item">
                <small class="text-muted d-block">Pagina fixa</small>
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
                                    <option value="registration">Matricula</option>
                                    <option value="id">ID</option>
                                </select>
                                <label for="searchBy">Campo</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-search-line"></i></span>
                                <input type="text" class="form-control" placeholder="Buscar usuario" wire:model.debounce.600ms="search">
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
                            <option value="responsible">Responsavel</option>
                            <option value="operator">Operador</option>
                            <option value="user">Usuario</option>
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
                        <small class="text-muted d-block mb-1">Conexao</small>
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
                        <small class="text-muted d-block mb-1">Exibicao</small>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="deletedFilter" id="deletedActive" value="active" wire:model="deletedFilter">
                            <label class="btn btn-outline-primary" for="deletedActive">Ativos</label>

                            <input type="radio" class="btn-check" name="deletedFilter" id="deletedAll" value="all" wire:model="deletedFilter">
                            <label class="btn btn-outline-secondary" for="deletedAll">Todos</label>

                            <input type="radio" class="btn-check" name="deletedFilter" id="deletedOnly" value="deleted" wire:model="deletedFilter">
                            <label class="btn btn-outline-danger" for="deletedOnly">So deletados</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            @if ($users_l->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 32px;">
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                        wire:click="setSelectAll()" @checked($this->checkAllSelect($users_l))>
                                </th>
                                <th style="width: 54px;">Avatar</th>
                                <th class="text-start">Usuario</th>
                                <th class="text-start">Contato</th>
                                <th class="text-start">Empresa / Contrato</th>
                                <th class="text-start">Atividades</th>
                                <th>Status</th>
                                <th style="width: 170px;">Acoes</th>
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
                                        $roles[] = 'Responsavel';
                                    }
                                    if ($theUser->operator) {
                                        $roles[] = 'Operador';
                                    }
                                    if ($theUser->user) {
                                        $roles[] = 'Usuario';
                                    }
                                    if ($theUser->onlyparner) {
                                        $roles[] = 'Empreiteira';
                                    }
                                    if ($theUser->analyst) {
                                        $roles[] = 'Analista';
                                    }
                                @endphp
                                <tr class="text-center @if ($theUser->trashed()) table-danger @endif" wire:key="user-row-{{ $theUser->id }}">
                                    <td>
                                        <input class="form-check-input border border-primary" type="checkbox"
                                            value="{{ $theUser->id }}" wire:model.defer="selected">
                                    </td>
                                    <td>
                                        <img src="{{ $theUser->avatar_url }}" alt="Avatar {{ $theUser->name }}" class="avatar"
                                            onerror="this.onerror=null;this.src='{{ asset('img/user.png') }}';">
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-semibold">{{ $theUser->name }}</div>
                                        <small class="text-muted">ID {{ $theUser->id }}
                                            @if ($theUser->Registration)
                                                • Matricula {{ $theUser->Registration }}
                                            @endif
                                        </small>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @foreach ($roles as $role)
                                                <span class="role-chip">{{ $role }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div>{{ $theUser->email }}</div>
                                        @if ($theUser->can_dispatch)
                                            <small class="text-success fw-semibold">Pode despachar</small>
                                        @endif
                                    </td>
                                    <td class="text-start">
                                        <div>{{ isset($theUser->Employee->Contract->Company->name) ? mb_strtoupper($theUser->Employee->Contract->Company->name) : '-' }}</div>
                                        <small class="text-muted">Contrato: {{ $theUser->Employee->Contract->number ?? '-' }}</small>
                                    </td>
                                    <td class="text-start">
                                        @if ($theUser->ToServices->count())
                                            <div class="small text-muted">
                                                @foreach ($theUser->ToServices->take(2) as $service)
                                                    <div>{{ $service->Service->service ?? '-' }}
                                                        ({{ $service->service ? 'S' : '-' }}/{{ $service->dispatch ? 'D' : '-' }})</div>
                                                @endforeach
                                                @if ($theUser->ToServices->count() > 2)
                                                    <div class="fw-semibold">+{{ $theUser->ToServices->count() - 2 }} atividades</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">Sem atividades</span>
                                        @endif
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        @if (!$theUser->trashed())
                                            @if (Auth()->User()->superadm && $theUser->id !== $master->id)
                                                <a href="{{ route('impersonate', $theUser->id) }}" class="text-decoration-none me-2">
                                                    <i class="ri-eye-line fs-5 text-info" title="Ver"></i>
                                                </a>
                                            @endif

                                            @if ($theUser->id !== $master->id || Auth()->User()->id == $theUser->id)
                                                <i wire:click.prevent="$emitTo('admin.user.actions.usuario', 'openUser', {{ $theUser }})"
                                                    class="ri-edit-line fs-5 text-warning me-2" title="Editar" style="cursor: pointer;"></i>
                                            @endif

                                            @if ($theUser->id !== Auth()->User()->id && $theUser->id !== $master->id)
                                                <i class="ri-delete-bin-line fs-5 text-danger" title="Excluir" style="cursor: pointer;"
                                                    wire:click.prevent="$emitTo('admin.user.delete','delete_user', '{{ $theUser->id }}')"></i>
                                            @endif
                                        @else
                                            <i class="ri-arrow-go-back-line fs-5 text-primary" title="Restaurar" style="cursor: pointer;"
                                                wire:click.prevent="$emitTo('admin.user.delete','undelete_user', '{{ $theUser->id }}')"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center">
                    <h5 class="mb-1">Nenhum usuario encontrado</h5>
                    <p class="text-muted mb-0">Ajuste os filtros para ampliar a busca.</p>
                </div>
            @endif

            <div class="row p-3 align-items-center">
                <div class="col-md-6">
                    {{ $users_l->links() }}
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">
                        Exibindo {{ $users_l->firstItem() ?? 0 }} ate {{ $users_l->lastItem() ?? 0 }} de {{ $users_l->total() }} registros.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title mb-0">Busca multi-usuarios</h5>
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
