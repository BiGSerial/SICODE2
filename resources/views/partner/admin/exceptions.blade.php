@extends('layouts.company')

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Exceções por usuário</h5>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('partner.admin.exceptions.store') }}" class="row g-3 mb-4">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Usuário</label>
                    <select name="user_id" class="form-select">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Permissão</label>
                    <select name="permission_key" class="form-select">
                        @foreach ($catalog as $groupKey => $group)
                            <optgroup label="{{ $group['label'] }}">
                                <option value="{{ $groupKey }}">{{ $group['label'] }} inteiro</option>
                                @foreach ($group['items'] as $permissionKey => $label)
                                    <option value="{{ $permissionKey }}">{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Regra</label>
                    <select name="enabled" class="form-select">
                        <option value="1">Liberar</option>
                        <option value="0">Bloquear</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Motivo</label>
                    <input type="text" name="reason" class="form-control" value="{{ old('reason') }}">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Salvar exceção</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Permissão</th>
                            <th>Regra</th>
                            <th>Motivo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exceptions as $exception)
                            <tr>
                                <td>{{ $exception->user?->name }}</td>
                                <td>{{ $exception->permission_key }}</td>
                                <td>{{ $exception->enabled ? 'Liberar' : 'Bloquear' }}</td>
                                <td>{{ $exception->reason }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('partner.admin.exceptions.destroy', $exception) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma exceção cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $exceptions->links() }}
        </div>
    </div>
@endsection
