@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
                <li class="breadcrumb-item">Parceiro</li>
                <li class="breadcrumb-item">Administração</li>
                <li class="breadcrumb-item active" aria-current="page">Usuário</li>
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
                <div class="partner-admin-eyebrow">{{ $managedCompany?->name ?? 'Empresa parceira' }}</div>
                <h1 class="partner-admin-title">{{ $user->exists ? $user->name : 'Novo usuário' }}</h1>
                <p class="partner-admin-subtitle">{{ $user->exists ? 'Editar usuário' : 'Cadastro de usuário' }}</p>
                @if ($user->exists)
                    <div class="partner-admin-hero-meta">
                        <div class="partner-admin-hero-chip">
                            <i class="ri-mail-line"></i>
                            <strong>{{ $user->email }}</strong>
                            <span>email</span>
                        </div>
                        <div class="partner-admin-hero-chip">
                            <i class="ri-shield-user-line"></i>
                            <strong>{{ $user->admin ? 'Sim' : 'Não' }}</strong>
                            <span>admin</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="partner-admin-panel">
            <div class="partner-admin-panel-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ $user->exists ? route('partner.admin.users.update', $user) : route('partner.admin.users.store') }}">
                @csrf
                @if ($user->exists)
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr>

                <div class="partner-admin-section">
                    <h6 class="partner-admin-panel-title mb-3">Filiais visíveis</h6>
                    <div class="row g-2">
                    @forelse ($branches as $branch)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="branches[]" value="{{ $branch->id }}" id="branch-{{ $branch->id }}"
                                    @checked(collect(old('branches', $selectedBranches->all()))->contains($branch->id))>
                                <label class="form-check-label" for="branch-{{ $branch->id }}">
                                    {{ trim(($branch->Company?->display_name ?: 'Empresa') . ' - ' . ($branch->city ?: 'Sem cidade') . ' - ' . ($branch->street ?: $branch->complement)) }}
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted">Nenhuma filial cadastrada para esta empresa.</div>
                    @endforelse
                    </div>
                </div>

                @if ($user->exists && $canManageUserPermissions)
                    <hr>

                    <h6 class="partner-admin-panel-title mb-3">Permissões individuais</h6>
                    @php
                        $oldUserPermissions = old('user_permissions');
                    @endphp
                    @foreach ($userPermissionCatalog as $groupKey => $group)
                        <div class="partner-admin-permission-group mb-3">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="user_permissions[{{ $groupKey }}]" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="user-permission-{{ $groupKey }}"
                                    name="user_permissions[{{ $groupKey }}]" value="1"
                                    @checked(filter_var(is_array($oldUserPermissions) ? ($oldUserPermissions[$groupKey] ?? false) : ($userPermissionValues[$groupKey] ?? false), FILTER_VALIDATE_BOOLEAN))>
                                <label class="form-check-label fw-bold" for="user-permission-{{ $groupKey }}">
                                    {{ $group['label'] }}
                                </label>
                            </div>

                            <div class="row">
                                @foreach ($group['items'] as $permissionKey => $label)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="hidden" name="user_permissions[{{ $permissionKey }}]" value="0">
                                            <input class="form-check-input" type="checkbox" id="user-permission-{{ $permissionKey }}"
                                                name="user_permissions[{{ $permissionKey }}]" value="1"
                                                @checked(filter_var(is_array($oldUserPermissions) ? ($oldUserPermissions[$permissionKey] ?? false) : ($userPermissionValues[$permissionKey] ?? false), FILTER_VALIDATE_BOOLEAN))>
                                            <label class="form-check-label" for="user-permission-{{ $permissionKey }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('partner.admin.users') }}" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-3-line"></i> Salvar
                    </button>
                </div>
            </form>

            @if ($user->exists && \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.disable') && $user->id !== auth()->id())
                <form method="POST" action="{{ route('partner.admin.users.disable', $user) }}" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="ri-user-forbid-line"></i> Desativar usuário
                    </button>
                </form>
            @endif
            </div>
        </div>
    </div>
@endsection
