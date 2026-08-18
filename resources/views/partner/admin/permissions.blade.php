@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
                <li class="breadcrumb-item">Parceiro</li>
                <li class="breadcrumb-item">Administração</li>
                <li class="breadcrumb-item active" aria-current="page">Permissões</li>
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
            <h5 class="mb-0">Permissões da empresa</h5>
            @if (!$configured)
                <span class="badge bg-info">Acesso legado completo</span>
            @endif
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('partner.admin.permissions.update') }}">
                @csrf
                @method('PUT')

                @foreach ($catalog as $groupKey => $group)
                    <div class="border rounded p-3 mb-3">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="permissions[{{ $groupKey }}]" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="permission-{{ $groupKey }}"
                                name="permissions[{{ $groupKey }}]" value="1"
                                @checked(!$configured || ($permissions->get($groupKey)?->enabled ?? false))>
                            <label class="form-check-label fw-bold" for="permission-{{ $groupKey }}">
                                {{ $group['label'] }}
                            </label>
                        </div>

                        <div class="row">
                            @foreach ($group['items'] as $permissionKey => $label)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="hidden" name="permissions[{{ $permissionKey }}]" value="0">
                                        <input class="form-check-input" type="checkbox" id="permission-{{ $permissionKey }}"
                                            name="permissions[{{ $permissionKey }}]" value="1"
                                            @checked(!$configured || ($permissions->get($permissionKey)?->enabled ?? true))>
                                        <label class="form-check-label" for="permission-{{ $permissionKey }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">Salvar permissões</button>
            </form>
        </div>
    </div>
@endsection
