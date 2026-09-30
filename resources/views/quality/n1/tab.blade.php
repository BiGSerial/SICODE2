@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item">N1 · Minhas obras</li>
        <li class="breadcrumb-item active" aria-current="page">{{ $current['label'] }}</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    @php
        $bulkTab     = in_array($tab, ['despachar', 'com-usuarios', 'devolvidos'], true);
        $multiCompany = $companies->count() > 1;
        $isActionTab  = $current['action'];
        $firstRow     = $rows->first();
    @endphp
    <div class="closure-dash">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0 fw-bold" style="color: var(--dash-ink)">Minhas obras <span class="badge text-bg-warning fs-6 align-middle">N1</span></h1>
                <div class="small text-muted">{{ $companies->pluck('name')->implode(' · ') }}</div>
            </div>
            <div class="d-flex gap-2">
                @if ($tab === 'analisar' && $firstRow)
                    <a href="{{ route('quality.process', $firstRow) }}" class="btn btn-primary btn-dash"><i class="ri-play-circle-line"></i> Analisar a mais antiga</a>
                @endif
                <a href="{{ route('quality.n1.users') }}" class="btn btn-outline-primary btn-dash"><i class="ri-team-line"></i> Meus usuários</a>
            </div>
        </div>

        @include('quality._feedback')
        @livewire('quality.stage-strip', ['level' => 'n1', 'tab' => $tab], key('stage-strip'))

        <div class="wk-help mb-3"><strong>{{ $current['step'] }}. {{ $current['label'] }}</strong> — {{ $current['help'] }}</div>

        <div class="table-card p-3 mb-3">@include('quality._bulk-search', ['bulkScope' => $bulkScope, 'bulkNotes' => $bulkNotes, 'bulkReport' => $bulkReport])</div>

        {{-- Barra de busca e filtros --}}
        <form method="get" class="table-card wk-toolbar p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4"><div class="input-group input-group-sm"><span class="input-group-text"><i class="ri-search-line"></i></span><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por Nota, OV ou material"></div></div>
                @if ($multiCompany)
                    <div class="col-lg-3"><select name="company_id" class="form-select form-select-sm"><option value="">Todas as empresas</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') === $company->id)>{{ $company->name }}</option>@endforeach</select></div>
                @endif
                @if ($tab !== 'despachar')
                    <div class="col-lg-3">
                        <select name="designer_id" class="form-select form-select-sm">
                            <option value="">Todos os usuários</option>
                            @foreach ($companies as $company)
                                <optgroup label="{{ $company->name }}">@foreach ($designers[$company->id] ?? [] as $u)<option value="{{ $u->id }}" @selected(request('designer_id') === $u->id)>{{ $u->name }}</option>@endforeach</optgroup>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($tab !== 'despachar')
                    <div class="col-lg-2"><select name="phase" class="form-select form-select-sm"><option value="">Todos os ciclos</option><option value="PROJECT" @selected(request('phase') === 'PROJECT')>1º ciclo · {{ $activityNames['PROJECT'] ?? '' }}</option><option value="BUDGET" @selected(request('phase') === 'BUDGET')>2º ciclo · {{ $activityNames['BUDGET'] ?? '' }}</option></select></div>
                @endif
                <div class="col-lg-2"><select name="ordem" class="form-select form-select-sm"><option value="antigas" @selected(request('ordem', 'antigas') === 'antigas')>Mais antigas primeiro</option><option value="recentes" @selected(request('ordem') === 'recentes')>Mais recentes primeiro</option><option value="nota" @selected(request('ordem') === 'nota')>Por Nota</option></select></div>
                <div class="col-auto d-flex gap-2"><button class="btn btn-sm btn-primary btn-dash">Filtrar</button><a href="{{ route('quality.n1', $tab) }}" class="btn btn-sm btn-outline-secondary">Limpar</a></div>
            </div>
            @include('quality._note-filters')
        </form>

        {{-- Lista --}}
        <form method="post" id="wk-form" action="{{ $tab === 'com-usuarios' ? route('quality.n1.reassign') : route('quality.n1.dispatch') }}" class="table-card position-relative" style="overflow: visible;">
            @csrf
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="filter_query" value="{{ request()->getQueryString() }}">
            <input type="hidden" name="all_filtered" value="0" id="wk-all-filtered">

            <div class="d-flex justify-content-between align-items-center px-3 pt-3">
                <div class="small text-muted"><strong>{{ $rows->total() }}</strong> obra(s) nesta etapa · ordenadas {{ ['antigas' => 'das mais paradas', 'recentes' => 'das mais recentes', 'nota' => 'por Nota'][request('ordem', 'antigas')] ?? '' }}</div>
                @if ($bulkTab)<div class="small text-muted">Marque as obras para agir em massa</div>@endif
            </div>
            <div id="wk-select-all-banner" class="alert alert-info small py-2 mx-3 mt-2 mb-0 d-none">
                Você marcou as <strong>{{ $rows->count() }}</strong> obras desta página.
                @if ($rows->total() > $rows->count())<a href="#" id="wk-pick-all" class="fw-bold">Selecionar as {{ $rows->total() }} obras de todas as páginas</a>@endif
                <span id="wk-all-picked" class="d-none fw-bold">Todas as {{ min($rows->total(), 500) }} obras do filtro estão selecionadas. <a href="#" id="wk-clear-all">Desfazer</a></span>
            </div>

            @if ($bulkTab && $rows->count())
                <div class="px-3 pt-2 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input" id="wk-check-page" aria-label="Marcar todas da página"><label for="wk-check-page" class="small text-muted mb-0">Marcar todas desta página</label></div>
            @endif
            <div class="ob-list">
                @forelse ($rows as $process)
                    @include('quality._obra', ['process' => $process, 'selectable' => $bulkTab, 'showCompany' => $multiCompany, 'showReason' => $tab === 'devolvidos', 'who' => $tab === 'despachar' ? 'n1' : ($tab === 'no-n2' ? 'n2' : 'auto'), 'primary' => $isActionTab, 'ageLabel' => ['despachar' => 'na fila do N1', 'com-usuarios' => 'com o usuário', 'analisar' => 'aguardando você', 'devolvidos' => 'desde a devolução', 'no-n2' => 'parado no N2'][$tab], 'action' => ['despachar' => 'Abrir', 'com-usuarios' => 'Abrir', 'analisar' => 'Analisar', 'devolvidos' => 'Responder', 'no-n2' => 'Abrir'][$tab]])
                @empty
                    <div class="ob-empty">
                        <i class="ri-inbox-2-line"></i>
                        <div class="fw-bold">Nada nesta etapa{{ request()->hasAny(['q', 'company_id', 'designer_id', 'rubrica', 'lexp']) ? ' com esses filtros' : '' }}.</div>
                        <div class="small">@if ($tab === 'despachar')Quando a Gestão enviar obras à sua empresa, elas aparecem aqui.@elseif ($tab === 'analisar')Quando um usuário finalizar uma obra, ela cai aqui para a sua análise.@else Acompanhe as outras etapas acima.@endif</div>
                    </div>
                @endforelse
            </div>
            <div class="p-3">{{ $rows->links('quality._pagination') }}</div>

            {{-- Ação em massa (aparece ao marcar) --}}
            @if ($bulkTab)
                <div class="wk-bulkbar" id="wk-bulkbar">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="me-2"><span class="fs-5 fw-bold" id="wk-count">0</span> <span class="small">selecionada(s)</span></div>
                        @if ($tab === 'devolvidos')
                            <button class="btn btn-sm btn-success btn-dash" onclick="return confirm('Reencaminhar as obras marcadas ao mesmo usuário de antes?')"><i class="ri-send-plane-line"></i> Reencaminhar ao mesmo usuário</button>
                            <span class="small text-white-50">Para trocar o usuário ou questionar o N2, abra a obra.</span>
                        @else
                            <select name="designer_id" class="form-select form-select-sm" style="max-width: 320px;" required>
                                <option value="">{{ $tab === 'com-usuarios' ? 'Reatribuir para…' : 'Despachar para…' }}</option>
                                @foreach ($companies as $company)
                                    @foreach (($tab === 'despachar' ? ['PROJECT'] : (request('phase') ? [request('phase')] : ['PROJECT', 'BUDGET'])) as $phaseKey)
                                        @php $options = $designersByPhase[$company->id][$phaseKey] ?? collect(); @endphp
                                        @continue($options->isEmpty())
                                        <optgroup label="{{ $activityNames[$phaseKey] ?? $phaseKey }} · {{ $phaseKey === 'PROJECT' ? '1º ciclo' : '2º ciclo' }}{{ $multiCompany ? ' · ' . $company->name : '' }}">
                                            @foreach ($options as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                        </optgroup>
                                    @endforeach
                                @endforeach
                            </select>
                            <input name="{{ $tab === 'com-usuarios' ? 'reason' : 'observation' }}" class="form-control form-control-sm" style="max-width: 280px;" maxlength="500" placeholder="{{ $tab === 'com-usuarios' ? 'Motivo (opcional)' : 'Orientação ao usuário (opcional)' }}">
                            <button class="btn btn-sm {{ $tab === 'com-usuarios' ? 'btn-warning' : 'btn-success' }} btn-dash" onclick="return confirm('{{ $tab === 'com-usuarios' ? 'Trocar o usuário das atividades marcadas?' : 'Despachar as obras marcadas ao usuário escolhido?' }}')">
                                <i class="ri-{{ $tab === 'com-usuarios' ? 'exchange-line' : 'send-plane-line' }}"></i> {{ $tab === 'com-usuarios' ? 'Reatribuir' : 'Despachar' }}
                            </button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-light ms-auto" id="wk-clear">Limpar seleção</button>
                    </div>
                </div>
            @endif
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
            const total = {{ min($rows->total(), 500) }};

            function refresh() {
                const n = allFlag.value === '1' ? total : rows().filter(r => r.checked).length;
                if (bar) { bar.classList.toggle('is-visible', n > 0); document.getElementById('wk-count').textContent = n; }
                const pageAll = rows().length > 0 && rows().every(r => r.checked);
                if (banner) { banner.classList.toggle('d-none', !pageAll); }
                const checkPage = document.getElementById('wk-check-page');
                if (checkPage) { checkPage.checked = pageAll; }
            }

            form.addEventListener('change', e => {
                if (e.target.id === 'wk-check-page') { rows().forEach(r => r.checked = e.target.checked); allFlag.value = '0'; if (pickAll) pickAll.classList.remove('d-none'); if (picked) picked.classList.add('d-none'); }
                if (e.target.classList.contains('wk-row')) { allFlag.value = '0'; }
                refresh();
            });
            if (pickAll) pickAll.addEventListener('click', e => { e.preventDefault(); allFlag.value = '1'; pickAll.classList.add('d-none'); picked.classList.remove('d-none'); refresh(); });
            const clearAll = document.getElementById('wk-clear-all');
            if (clearAll) clearAll.addEventListener('click', e => { e.preventDefault(); allFlag.value = '0'; pickAll && pickAll.classList.remove('d-none'); picked.classList.add('d-none'); refresh(); });
            const clear = document.getElementById('wk-clear');
            if (clear) clear.addEventListener('click', () => { rows().forEach(r => r.checked = false); allFlag.value = '0'; refresh(); });

            form.addEventListener('change', e => { if (e.target.classList.contains('wk-row')) { e.target.closest('.ob').classList.toggle('is-selected', e.target.checked); } if (e.target.id === 'wk-check-page') { rows().forEach(r => r.closest('.ob').classList.toggle('is-selected', r.checked)); } });
            refresh();
        })();
    </script>
@endpush
