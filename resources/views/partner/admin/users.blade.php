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

            <div class="partner-admin-tabs">
                <a href="{{ route('partner.admin.users') }}" class="{{ $status === 'active' ? 'is-active' : '' }}">
                    <i class="ri-user-follow-line"></i>
                    Ativos
                    <span>{{ $activeCount }}</span>
                </a>
                <a href="{{ route('partner.admin.users', ['status' => 'disabled']) }}" class="{{ $status === 'disabled' ? 'is-active' : '' }}">
                    <i class="ri-user-unfollow-line"></i>
                    Desativados
                    <span>{{ $disabledCount }}</span>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle partner-admin-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Filiais</th>
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
                                <td>{{ $user->name }}</td>
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
                                <td colspan="7" class="text-center text-muted">Nenhum usuário encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
