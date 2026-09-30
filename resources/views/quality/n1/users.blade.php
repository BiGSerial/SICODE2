@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item"><a href="{{ route('quality.n1') }}">N1 · Minhas obras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Meus usuários</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">Meus usuários</h1>
            <div class="dash-subtitle">Quem executa as suas obras, a carga de cada um e há quanto tempo estão com ele. Só o N1 pode trocar o usuário de uma atividade: marque as atividades nos cartões, escolha o novo usuário e reatribua.</div>
        </div>

        @include('quality._feedback')
        <a href="{{ route('quality.n1') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="ri-arrow-left-line"></i> Voltar às minhas obras</a>

        @php $initials = fn (?string $name) => $name ? collect(preg_split('/\s+/', trim($name)))->filter(fn ($w) => mb_strlen($w) > 2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') : '?'; @endphp
        @forelse ($byCompany as $block)
            @php
                $company  = $block['company'];
                $users    = $block['users'];
                $maxOpen  = max(5, $users->max(fn ($row) => $row['open']->count()) ?? 0);
                $openRows = $users->flatMap(fn ($row) => $row['open']);
            @endphp
            <form method="post" action="{{ route('quality.n1.reassign') }}" class="mb-4">
                @csrf
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                    <div>
                        <h2 class="h5 fw-bold mb-0" style="color: var(--dash-ink)"><i class="ri-building-line"></i> {{ $company->name }}</h2>
                        <div class="small text-muted">{{ $users->count() }} usuário(s) habilitado(s) · {{ $openRows->count() }} atividade(s) em andamento</div>
                    </div>
                    @if ($openRows->isNotEmpty())
                        <div class="d-flex flex-wrap align-items-end gap-2">
                            <select name="designer_id" class="form-select form-select-sm" style="min-width: 260px;" required>
                                <option value="">Reatribuir as marcadas para…</option>
                                @foreach (['PROJECT', 'BUDGET'] as $phaseKey)
                                    @php $options = $designersByPhase[$company->id][$phaseKey] ?? collect(); @endphp
                                    @continue($options->isEmpty())
                                    <optgroup label="{{ $activityNames[$phaseKey] ?? $phaseKey }} · {{ $phaseKey === 'PROJECT' ? '1º ciclo' : '2º ciclo' }}">@foreach ($options as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</optgroup>
                                @endforeach
                            </select>
                            <input name="reason" class="form-control form-control-sm" style="max-width: 220px;" maxlength="500" placeholder="Motivo (opcional)">
                            <button class="btn btn-sm btn-warning btn-dash" onclick="return confirm('Trocar o usuário das atividades marcadas? O usuário anterior deixa de vê-las.')"><i class="ri-exchange-line"></i> Reatribuir</button>
                        </div>
                    @endif
                </div>

                <div class="usr-grid p-0">
                    @forelse ($users as $row)
                        @php
                            $open    = $row['open']->sortBy('state_changed_at');
                            $oldest  = $open->first();
                            $tone    = $oldest ? $oldest->urgency() : 'ok';
                            $percent = min(100, round($open->count() / $maxOpen * 100));
                        @endphp
                        <div class="usr-card">
                            <div class="usr-head">
                                <div class="ob-avatar">{{ $initials($row['user']->name) }}</div>
                                <div class="flex-grow-1 min-w-0"><div class="fw-bold">{{ $row['user']->name }}</div><div class="ob-role">{{ $open->count() ? $open->count() . ' em andamento' : 'livre' }}</div></div>
                                @if ($open->isNotEmpty())<a href="{{ route('quality.n1', ['tab' => 'com-usuarios', 'designer_id' => $row['user']->id]) }}" class="btn btn-sm btn-outline-primary">Ver</a>@endif
                            </div>
                            <div class="load-bar is-{{ $tone }} mt-3" title="Carga atual"><span style="width: {{ max($percent, $open->count() ? 6 : 0) }}%"></span></div>
                            <div class="usr-stats">
                                <div class="usr-stat"><b>{{ $open->count() }}</b><span>Abertas</span></div>
                                <div class="usr-stat"><b>{{ $row['rounds'] }}</b><span>Rodadas feitas</span></div>
                                <div class="usr-stat"><b>{{ $row['avg_hours'] !== null ? $row['avg_hours'] . ' h' : '—' }}</b><span>Tempo médio</span></div>
                            </div>
                            @if ($open->isNotEmpty())
                                <div class="mt-2">
                                    @foreach ($open->take(5) as $process)
                                        <label class="usr-act">
                                            <input type="checkbox" class="form-check-input mt-0" name="process_ids[]" value="{{ $process->id }}">
                                            <a href="{{ route('quality.process', $process) }}" class="fw-bold text-decoration-none">{{ $process->Note?->note }}</a>
                                            <span class="chip chip-cycle">{{ $process->phase->short() }}</span>
                                            <span class="ms-auto">@include('quality._age', ['since' => $process->state_changed_at])</span>
                                        </label>
                                    @endforeach
                                    @if ($open->count() > 5)<div class="small text-muted mt-1">+ {{ $open->count() - 5 }} outra(s) · use "Ver"</div>@endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="ob-empty" style="grid-column: 1 / -1;"><i class="ri-team-line"></i>Nenhum usuário desta empresa está habilitado nas atividades da Qualidade.</div>
                    @endforelse
                </div>
            </form>
        @empty
            <div class="alert alert-info">Você ainda não está cadastrado como N1 de nenhuma empresa. Fale com a Gestão.</div>
        @endforelse
    </div>
@endsection

