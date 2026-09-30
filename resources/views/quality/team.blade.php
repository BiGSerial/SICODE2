@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">Equipe N1 / N2</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">Equipe N1 / N2</h1>
            <div class="dash-subtitle">Defina quem é N1 e N2 de cada empresa. O N1 gerencia as obras da empresa (despacha aos usuários); o N2 aprova e encerra. Cada um só enxerga as empresas em que está cadastrado.</div>
        </div>

        @include('quality._feedback')

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="table-card p-3 mb-3">
                    <div class="chart-title mb-2">Empresa</div>
                    <form method="get">
                        <select name="company_id" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
                            <option value="">Todas as empresas</option>
                            @foreach ($companies as $company)<option value="{{ $company->id }}" @selected($companyId === $company->id)>{{ $company->name }}</option>@endforeach
                        </select>
                    </form>
                </div>

                @if ($companyId)
                    <form method="post" action="{{ route('quality.team.store') }}" class="table-card p-3">
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $companyId }}">
                        <div class="chart-title mb-2">Cadastrar na empresa</div>
                        <label class="form-label small mb-1">Usuário da empresa</label>
                        <select name="user_id" class="form-select form-select-sm mb-2" required>
                            <option value="">Selecione</option>
                            @foreach ($candidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }}</option>@endforeach
                        </select>
                        <label class="form-label small mb-1">Papel</label>
                        <select name="role" class="form-select form-select-sm mb-3" required>
                            <option value="N1">N1 — despacha aos usuários e analisa</option>
                            <option value="N2">N2 — aprova, devolve ao N1 e encerra</option>
                        </select>
                        <button class="btn btn-primary btn-sm btn-dash"><i class="ri-user-add-line"></i> Cadastrar</button>
                    </form>
                @else
                    <div class="alert alert-info small">Escolha uma empresa para cadastrar N1 ou N2.</div>
                @endif
            </div>

            <div class="col-xl-8">
                @php
                    $initials = fn (?string $name) => $name ? collect(preg_split('/\s+/', trim($name)))->filter(fn ($w) => mb_strlen($w) > 2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') : '?';
                    $byCompany = $members->groupBy('company_id');
                @endphp
                @forelse ($byCompany as $companyMembers)
                    <div class="table-card mb-3">
                        <div class="p-3 pb-2 d-flex justify-content-between align-items-center">
                            <div><div class="chart-title"><i class="ri-building-line"></i> {{ $companyMembers->first()->Company?->name }}</div><div class="chart-subtitle">{{ $companyMembers->where('role', 'N1')->where('active', true)->count() }} N1 · {{ $companyMembers->where('role', 'N2')->where('active', true)->count() }} N2 ativos</div></div>
                        </div>
                        <div class="row g-0 border-top">
                            @foreach (['N1' => ['Despacham aos usuários e analisam', 'primary'], 'N2' => ['Aprovam, devolvem e encerram', 'dark']] as $role => [$roleHint, $tone])
                                <div class="col-md-6 p-3 {{ $role === 'N1' ? 'border-end' : '' }}">
                                    <div class="d-flex align-items-center gap-2 mb-2"><span class="badge text-bg-{{ $tone }}">{{ $role }}</span><span class="small text-muted">{{ $roleHint }}</span></div>
                                    @forelse ($companyMembers->where('role', $role) as $member)
                                        <div class="usr-act {{ $member->active ? '' : 'opacity-50' }}">
                                            <span class="ob-avatar" style="flex-basis: 28px; height: 28px; font-size: .68rem;">{{ $initials($member->User?->name) }}</span>
                                            <span class="fw-bold">{{ $member->User?->name }}</span>
                                            @unless ($member->active)<span class="chip">inativo</span>@endunless
                                            <span class="ms-auto d-flex gap-1">
                                                <form method="post" action="{{ route('quality.team.toggle', $member) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $member->active ? 'warning' : 'success' }}" title="{{ $member->active ? 'Desativar' : 'Reativar' }}"><i class="ri-{{ $member->active ? 'pause-circle-line' : 'play-circle-line' }}"></i></button></form>
                                                <form method="post" action="{{ route('quality.team.destroy', $member) }}" onsubmit="return confirm('Remover este vínculo?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Remover"><i class="ri-delete-bin-line"></i></button></form>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="small text-muted fst-italic">Nenhum {{ $role }} cadastrado.</div>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="table-card"><div class="ob-empty"><i class="ri-team-line"></i><div class="fw-bold">Nenhum N1 ou N2 cadastrado.</div><div class="small">Escolha uma empresa ao lado e cadastre o primeiro.</div></div></div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
