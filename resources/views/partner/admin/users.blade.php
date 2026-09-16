@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
                <li class="breadcrumb-item">Parceiro</li>
                <li class="breadcrumb-item">Administração</li>
                <li class="breadcrumb-item active" aria-current="page">Usuários</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @include('partner.admin._styles')

    <div class="partner-admin-shell">
        <div class="partner-admin-header">
            <div>
                <div class="partner-admin-eyebrow">Administração da parceira</div>
                <h1 class="partner-admin-title">{{ $managedCompany?->name ?? 'Empresa parceira' }}</h1>
                <p class="partner-admin-subtitle">Usuários</p>
                <div class="partner-admin-hero-meta">
                    <div class="partner-admin-hero-chip">
                        <i class="ri-user-settings-line"></i>
                        <strong>{{ $userCount }}</strong>
                        <span>usuários</span>
                    </div>
                    <div class="partner-admin-hero-chip">
                        <i class="ri-user-follow-line"></i>
                        <strong>{{ $activeCount }}</strong>
                        <span>ativos</span>
                    </div>
                    <div class="partner-admin-hero-chip">
                        <i class="ri-node-tree"></i>
                        <strong>{{ $branchCount }}</strong>
                        <span>filiais</span>
                    </div>
                    <div class="partner-admin-hero-chip">
                        <i class="ri-wifi-line"></i>
                        <strong>{{ $onlineCount }}</strong>
                        <span>online</span>
                    </div>
                </div>
            </div>
            <div class="partner-admin-actions">
                @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.template_export'))
                    <a href="{{ route('partner.admin.users.import_template') }}" class="btn btn-outline-primary">
                        <i class="ri-download-2-line"></i> Exportar modelo
                    </a>
                @endif
                @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.create'))
                    <a href="{{ route('partner.admin.users.create') }}" class="btn btn-primary">
                        <i class="ri-user-add-line"></i> Novo usuário
                    </a>
                @endif
            </div>
        </div>

        <div class="partner-admin-panel">
            <div class="partner-admin-panel-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.bulk_import'))
                <form method="POST" action="{{ route('partner.admin.users.import.preview') }}" enctype="multipart/form-data" class="partner-admin-toolbar row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">Importar usuários</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="ri-file-search-line"></i> Pré-visualizar importação
                        </button>
                    </div>
                </form>
            @endif

            <form method="GET" action="{{ route('partner.admin.users') }}" class="partner-admin-toolbar row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pesquisar usuário</label>
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nome ou e-mail">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Empresa</label>
                    <select name="company" class="form-select">
                        <option value="">Todas as empresas permitidas</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company->id }}" @selected($companyFilter === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Conexão</label>
                    <select name="presence" class="form-select">
                        <option value="all" @selected($presence === 'all')>Todos</option>
                        <option value="online" @selected($presence === 'online')>Online</option>
                        <option value="offline" @selected($presence === 'offline')>Offline</option>
                    </select>
                </div>
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary"><i class="ri-search-line"></i> Filtrar</button>
                </div>
            </form>

            @if ($users->count() && $status === 'active' && \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.update'))
                <form method="POST" action="{{ route('partner.admin.users.bulk_update') }}" class="partner-admin-toolbar row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Ação em massa</label>
                        <select name="action" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="make_admin">Tornar administradores</option>
                            <option value="remove_admin">Remover administrador</option>
                            <option value="reset_password">Redefinir senhas</option>
                            <option value="move_company">Transferir empresa/filial</option>
                            @if ($status === 'active')
                                <option value="disable">Desativar usuários</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Empresa de destino</label>
                        <select name="company_id" class="form-select">
                            <option value="">Somente para transferir</option>
                            @foreach ($companyOptions as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end justify-content-end">
                        <button class="btn btn-warning" onclick="return confirm('Confirma a alteração dos usuários selecionados?')">
                            <i class="ri-group-line"></i> Aplicar aos selecionados
                        </button>
                    </div>

            <div class="partner-admin-tabs">
                <a href="{{ route('partner.admin.users', array_filter(['status' => 'active', 'search' => $search, 'company' => $companyFilter, 'presence' => $presence])) }}" class="{{ $status === 'active' ? 'is-active' : '' }}">
                    <i class="ri-user-follow-line"></i>
                    Ativos
                    <span>{{ $activeCount }}</span>
                </a>
                <a href="{{ route('partner.admin.users', array_filter(['status' => 'disabled', 'search' => $search, 'company' => $companyFilter, 'presence' => $presence])) }}" class="{{ $status === 'disabled' ? 'is-active' : '' }}">
                    <i class="ri-user-unfollow-line"></i>
                    Desativados
                    <span>{{ $disabledCount }}</span>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle partner-admin-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="document.querySelectorAll('.partner-user-check').forEach((item) => item.checked = this.checked)"></th>
                            <th>Nome</th>
                            <th>Empresa</th>
                            <th>Email</th>
                            <th>Filiais</th>
                            <th>Conexão</th>
                            <th>Último acesso</th>
                            <th>Admin</th>
                            <th>Ativo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $branchLabels = $user->partnerBranchAddresses
                                    ->map(function ($branch) {
                                        $companyName = $branch->Company?->display_name ?: $branch->Company?->name;

                                        return trim(($companyName ?: 'Filial') . ($branch->city ? ' - ' . $branch->city : ''));
                                    })
                                    ->filter()
                                    ->unique()
                                    ->values();
                            @endphp
                            <tr>
                                <td><input class="partner-user-check" type="checkbox" name="user_ids[]" value="{{ $user->id }}"></td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->Company?->name ?? 'Sem empresa' }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if ($branchLabels->isEmpty())
                                        <span class="text-muted">Sem filial definida</span>
                                    @else
                                        <span class="partner-admin-branch-summary">
                                            {{ $branchLabels->take(2)->implode(', ') }}
                                            @if ($branchLabels->count() > 2)
                                                <small>+{{ $branchLabels->count() - 2 }}</small>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                @php
                                    $isOnline = $user->last_seen_at?->gte(now()->subMinutes(5));
                                @endphp
                                <td>
                                    <span class="partner-admin-status {{ $isOnline ? 'is-online' : 'is-offline' }}">
                                        <span class="partner-admin-status-dot"></span>
                                        {{ $isOnline ? 'Online' : 'Offline' }}
                                    </span>
                                </td>
                                <td>{{ ($user->last_seen_at ?: $user->last_login_at)?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                                <td>
                                    <span class="partner-admin-status {{ $user->admin ? 'is-on' : 'is-off' }}">
                                        {{ $user->admin ? 'Sim' : 'Não' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="partner-admin-status {{ $user->deleted_at ? 'is-off' : 'is-on' }}">
                                        {{ $user->deleted_at ? 'Não' : 'Sim' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if ($status === 'active' && \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.update'))
                                        <a href="{{ route('partner.admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Nenhum usuário encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                        </table>
                    </div>
                </form>
            @endif

            {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
