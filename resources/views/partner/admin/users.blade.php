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
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Usuários da empresa</h5>
            <div class="d-flex gap-2">
                @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.template_export'))
                    <a href="{{ route('partner.admin.users.import_template') }}" class="btn btn-sm btn-outline-primary">Exportar modelo</a>
                @endif
                @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.create'))
                    <a href="{{ route('partner.admin.users.create') }}" class="btn btn-sm btn-primary">Novo usuário</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.bulk_import'))
                <form method="POST" action="{{ route('partner.admin.users.import.preview') }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">Importar usuários</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary w-100">Pré-visualizar importação</button>
                    </div>
                </form>
            @endif

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Admin</th>
                            <th>Ativo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->admin ? 'Sim' : 'Não' }}</td>
                                <td>{{ $user->deleted_at ? 'Não' : 'Sim' }}</td>
                                <td class="text-end">
                                    @if (\App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'admin_users.update'))
                                        <a href="{{ route('partner.admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum usuário encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
@endsection
