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
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ $user->exists ? 'Editar usuário' : 'Novo usuário' }}</h5>
        </div>
        <div class="card-body">
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

                <h6>Filiais</h6>
                <div class="row">
                    @forelse ($branches as $branch)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="branches[]" value="{{ $branch->id }}" id="branch-{{ $branch->id }}"
                                    @checked(collect(old('branches', $selectedBranches->all()))->contains($branch->id))>
                                <label class="form-check-label" for="branch-{{ $branch->id }}">
                                    {{ trim(($branch->city ?: 'Sem cidade') . ' - ' . ($branch->street ?: $branch->complement)) }}
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted">Nenhuma filial cadastrada para esta empresa.</div>
                    @endforelse
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('partner.admin.users') }}" class="btn btn-outline-secondary">Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>

            @if ($user->exists && \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.disable') && $user->id !== auth()->id())
                <form method="POST" action="{{ route('partner.admin.users.disable', $user) }}" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Desativar usuário</button>
                </form>
            @endif
        </div>
    </div>
@endsection
