@extends('layouts.company')

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Prévia da importação</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <span class="badge bg-success">{{ $preview['valid_count'] }} válidas</span>
                <span class="badge bg-danger">{{ $preview['error_count'] }} com erro</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Linha</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Filial</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['items'] as $item)
                            <tr>
                                <td>{{ $item['line'] }}</td>
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item['email'] }}</td>
                                <td>{{ $item['branchName'] }}</td>
                                <td>
                                    @if ($item['valid'])
                                        <span class="text-success">Válida</span>
                                    @else
                                        <span class="text-danger">{{ implode(' ', $item['errors']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('partner.admin.users') }}" class="btn btn-outline-secondary">Voltar</a>
                @if ($preview['valid_count'] > 0 && $preview['error_count'] === 0)
                    <form method="POST" action="{{ route('partner.admin.users.import.confirm') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit" class="btn btn-primary">Confirmar importação</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
