@extends('layouts.company')

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @include('partner.admin._styles')

    <div class="partner-admin-shell">
        <div class="partner-admin-header">
            <div>
                <div class="partner-admin-eyebrow">{{ $managedCompany?->name ?? 'Empresa parceira' }}</div>
                <h1 class="partner-admin-title">Prévia da importação</h1>
                <p class="partner-admin-subtitle">Importação de usuários</p>
            </div>
            <div class="partner-admin-actions">
                <div class="partner-admin-hero-chip">
                    <i class="ri-checkbox-circle-line"></i>
                    <strong>{{ $preview['valid_count'] }}</strong>
                    <span>válidas</span>
                </div>
                <div class="partner-admin-hero-chip">
                    <i class="ri-error-warning-line"></i>
                    <strong>{{ $preview['error_count'] }}</strong>
                    <span>erros</span>
                </div>
            </div>
        </div>

        <div class="partner-admin-panel">
            <div class="partner-admin-panel-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle partner-admin-table">
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
                                        <span class="partner-admin-status is-on">Válida</span>
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
                <a href="{{ route('partner.admin.users') }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line"></i> Voltar
                </a>
                @if ($preview['valid_count'] > 0 && $preview['error_count'] === 0)
                    <form method="POST" action="{{ route('partner.admin.users.import.confirm') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-check-line"></i> Confirmar importação
                        </button>
                    </form>
                @endif
            </div>
            </div>
        </div>
    </div>
@endsection
