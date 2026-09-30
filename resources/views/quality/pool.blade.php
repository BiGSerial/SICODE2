@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">Pool</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">Pool de Notas</h1>
            <div class="dash-subtitle">Notas elegíveis pelos critérios configurados. Nada é gravado até você despachar ao N1 de uma empresa; a atividade do usuário só é criada quando o N1 despacha.</div>
        </div>

        @include('quality._feedback')

        @if ($missingActivities)
            <div class="alert alert-warning">Falta definir a atividade de: <strong>{{ implode(', ', $missingActivities) }}</strong>. Sem isso não é possível despachar. <a href="{{ route('quality.settings') }}">Configurar agora</a>.</div>
        @endif

        {{-- Barra única: busca, busca em massa e filtros; critérios ficam recolhidos para a lista aparecer logo --}}
        <div class="table-card p-3 mb-3">
            <form method="get" class="mb-2">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-5"><div class="input-group input-group-sm"><span class="input-group-text"><i class="ri-search-line"></i></span><input name="note" value="{{ request('note') }}" class="form-control" placeholder="Buscar por Nota ou OV"></div></div>
                    <div class="col-auto d-flex gap-2"><button class="btn btn-sm btn-primary btn-dash">Filtrar</button><a href="{{ route('quality.pool') }}" class="btn btn-sm btn-outline-secondary">Limpar</a></div>
                </div>
                @include('quality._note-filters')
            </form>
            <div class="border-top pt-2">@include('quality._bulk-search', ['bulkScope' => 'pool', 'bulkNotes' => $bulkNotes, 'bulkReport' => $bulkReport])</div>
            <details class="mt-2">
                <summary class="small fw-bold text-primary" style="cursor: pointer;"><i class="ri-question-line"></i> Por que uma Nota aparece aqui ({{ count($fixedRules) }} regras fixas · {{ $rules->count() }} critério(s) configurado(s))</summary>
                <div class="mt-2">
                    <div class="small text-uppercase text-muted fw-bold mb-1">Regras fixas</div>
                    <ul class="small mb-2">@foreach ($fixedRules as $rule)<li>{{ $rule }}</li>@endforeach</ul>
                    <div class="small text-uppercase text-muted fw-bold mb-1">Critérios configurados</div>
                    @include('quality._rules-list', ['rules' => $rules, 'deletable' => false])
                    <a href="{{ route('quality.settings') }}" class="btn btn-sm btn-outline-secondary mt-1">Alterar critérios</a>
                </div>
            </details>
        </div>

        <form method="post" action="{{ route('quality.dispatch') }}" class="table-card" id="wk-form" style="overflow: visible;">
            @csrf
            <input type="hidden" name="filter_query" value="{{ request()->getQueryString() }}">
            <input type="hidden" name="all_filtered" value="0" id="wk-all-filtered">
            <div class="d-flex justify-content-between align-items-center px-3 pt-3">
                <div class="small text-muted"><strong>{{ $pool->total() }}</strong> Nota(s) elegível(is)</div>
                <div class="d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input" id="wk-check-page" aria-label="Marcar todas da página"><label for="wk-check-page" class="small text-muted mb-0">Marcar todas desta página</label></div>
            </div>
            <div id="wk-select-all-banner" class="alert alert-info small py-2 mx-3 mt-2 mb-0 d-none">
                Você marcou as <strong>{{ $pool->count() }}</strong> Notas desta página.
                @if ($pool->total() > $pool->count())<a href="#" id="wk-pick-all" class="fw-bold">Selecionar as {{ min($pool->total(), 2000) }} Notas de todas as páginas</a>@endif
                <span id="wk-all-picked" class="d-none fw-bold">Todas as {{ min($pool->total(), 2000) }} Notas do filtro estão selecionadas. <a href="#" id="wk-clear-all">Desfazer</a></span>
            </div>
            <div class="ob-list">
                @forelse ($pool as $note)
                    @php
                        $days = $note->days_left;
                        $tone = $days === null ? 'ok' : ($days <= 3 ? 'late' : ($days <= 10 ? 'warn' : 'ok'));
                    @endphp
                    <div class="ob is-{{ $tone }}">
                        <div class="ob-check"><input class="form-check-input wk-row" type="checkbox" name="note_ids[]" value="{{ $note->id }}" aria-label="Selecionar Nota {{ $note->note }}"></div>
                        <div class="ob-main">
                            <div class="d-flex align-items-baseline gap-2 flex-wrap"><span class="ob-note">{{ $note->note }}</span>@if ($note->nstats)<span class="chip chip-cycle">Status {{ $note->nstats }}</span>@endif</div>
                            <div class="ob-material" title="{{ $note->material }}">{{ $note->material ?: '—' }}</div>
                            <div class="ob-chips">
                                @if ($note->rubrica)<span class="chip">{{ $note->rubrica }}</span>@endif
                                @if ($note->lexp)<span class="chip chip-where"><i class="ri-map-pin-line"></i>{{ $note->lexp }}</span>@endif
                                @if ($note->group1)<span class="chip">{{ $note->group1 }}</span>@endif
                            </div>
                        </div>
                        <div class="ob-side">
                            <div class="ob-age">@if ($days !== null)<span class="badge text-bg-{{ ['ok' => 'success', 'warn' => 'warning', 'late' => 'danger'][$tone] }}">{{ $days }} dia(s)</span><small>prazo restante</small>@else<span class="text-muted">—</span><small>sem prazo</small>@endif</div>
                            <div class="ob-age"><span class="fw-bold">{{ $note->dt_status ? \Illuminate\Support\Carbon::parse($note->dt_status)->format('d/m/Y') : '—' }}</span><small>data do status</small></div>
                        </div>
                    </div>
                @empty
                    <div class="ob-empty">
                        <i class="ri-inbox-2-line"></i>
                        @if ($rules->isEmpty())<div class="fw-bold">Nenhum critério configurado.</div><div class="small">Sem critérios o Pool fica vazio de propósito. Defina em Configuração › Critérios e atividades.</div>
                        @else<div class="fw-bold">Nenhuma Nota elegível.</div><div class="small">Nenhuma Nota atende aos critérios e filtros atuais.</div>@endif
                    </div>
                @endforelse
            </div>
            <div class="p-3">{{ $pool->withQueryString()->links('quality._pagination') }}</div>

            <div class="wk-bulkbar" id="wk-bulkbar">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="me-2"><span class="fs-5 fw-bold" id="wk-count">0</span> <span class="small">selecionada(s)</span></div>
                    <select name="company_id" class="form-select form-select-sm" style="max-width: 300px;" required>
                        <option value="">Despachar ao N1 da empresa…</option>
                        @foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-success btn-dash" onclick="return confirm('Despachar as Notas selecionadas ao N1 da empresa escolhida?')"><i class="ri-send-plane-line"></i> Despachar ao N1</button>
                    @if ($companies->isEmpty())<span class="small text-warning">Nenhuma empresa tem N1 cadastrado (Configuração › Equipe).</span>@endif
                    <button type="button" class="btn btn-sm btn-outline-light ms-auto" id="wk-clear">Limpar seleção</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const form = document.getElementById('wk-form');
            const rows = () => Array.from(form.querySelectorAll('.wk-row'));
            const bar = document.getElementById('wk-bulkbar');
            const banner = document.getElementById('wk-select-all-banner');
            const allFlag = document.getElementById('wk-all-filtered');
            const pickAll = document.getElementById('wk-pick-all');
            const picked = document.getElementById('wk-all-picked');
            const clearAll = document.getElementById('wk-clear-all');
            const total = {{ min($pool->total(), 2000) }};

            function refresh() {
                const all = allFlag.value === '1';
                const n = all ? total : rows().filter(r => r.checked).length;
                bar.classList.toggle('is-visible', n > 0);
                document.getElementById('wk-count').textContent = n;
                rows().forEach(r => r.closest('.ob').classList.toggle('is-selected', r.checked));
                const pageAll = rows().length > 0 && rows().every(r => r.checked);
                document.getElementById('wk-check-page').checked = pageAll;
                banner.classList.toggle('d-none', !pageAll);
                if (pickAll) pickAll.classList.toggle('d-none', all);
                picked.classList.toggle('d-none', !all);
            }
            form.addEventListener('change', e => {
                if (e.target.id === 'wk-check-page') { rows().forEach(r => r.checked = e.target.checked); allFlag.value = '0'; }
                if (e.target.classList.contains('wk-row')) { allFlag.value = '0'; }
                refresh();
            });
            if (pickAll) pickAll.addEventListener('click', e => { e.preventDefault(); allFlag.value = '1'; refresh(); });
            if (clearAll) clearAll.addEventListener('click', e => { e.preventDefault(); allFlag.value = '0'; refresh(); });
            document.getElementById('wk-clear').addEventListener('click', () => { rows().forEach(r => r.checked = false); allFlag.value = '0'; refresh(); });
            refresh();
        })();
    </script>
@endpush
