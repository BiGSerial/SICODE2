{{-- Filtros por dados da Nota. Espera $noteFilterOptions. Dentro de um <form method="get">. --}}
@php
    $columns   = \App\Services\Quality\QualityNoteFilters::COLUMNS;
    $geo       = \App\Services\Quality\QualityNoteFilters::GEO;
    $selected  = fn ($key) => array_map('strval', (array) request($key));
    $activeCnt = collect(array_keys($columns + $geo))->filter(fn ($key) => filled(request($key)))->count() + (int) filled(request('dt_from')) + (int) filled(request('dt_to')) + (int) is_numeric(request('days_left_max'));
    $groups    = [
        'Nota'  => ['rubrica', 'nstats'],
        'Local' => ['region', 'regional', 'city'],
    ];
    $label = fn ($key) => $columns[$key] ?? $geo[$key];
@endphp
<details class="nf mt-2" @if ($activeCnt) open @endif>
    <summary class="nf-summary"><i class="ri-filter-3-line"></i> Mais filtros <span class="nf-hint">rubrica, status, região, município…</span>@if ($activeCnt)<span class="badge rounded-pill text-bg-primary ms-1">{{ $activeCnt }} ativo(s)</span>@endif</summary>
    <div class="nf-body">
        @foreach ($groups as $title => $keys)
            <div class="nf-group">
                <div class="nf-title">{{ $title }}</div>
                <div class="nf-grid">
                    @foreach ($keys as $key)
                        <div class="nf-field">
                            <label class="nf-label" for="nf-{{ $key }}">{{ $label($key) }}</label>
                            <select id="nf-{{ $key }}" name="{{ $key }}[]" multiple size="5" class="form-select form-select-sm nf-select">
                                @if ($key === 'city')
                                    @foreach ($noteFilterOptions['city'] ?? [] as $city)
                                        <option value="{{ $city['id'] }}" data-region="{{ $city['regiao'] }}" data-regional="{{ $city['regional'] }}" @selected(in_array($city['id'], $selected('city'), true))>{{ $city['name'] }}</option>
                                    @endforeach
                                @else
                                    @foreach ($noteFilterOptions[$key] ?? [] as $option)
                                        <option value="{{ $option }}" @selected(in_array((string) $option, $selected($key), true))>{{ $option }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="nf-group">
            <div class="nf-title">Período e prazo</div>
            <div class="nf-grid">
                <div class="nf-field"><label class="nf-label">Data do status de</label><input type="date" name="dt_from" value="{{ request('dt_from') }}" class="form-control form-control-sm"></div>
                <div class="nf-field"><label class="nf-label">Data do status até</label><input type="date" name="dt_to" value="{{ request('dt_to') }}" class="form-control form-control-sm"></div>
                <div class="nf-field"><label class="nf-label">Dias restantes (máx.)</label><input type="number" name="days_left_max" value="{{ request('days_left_max') }}" class="form-control form-control-sm"></div>
            </div>
        </div>
        <div class="nf-foot">
            <span class="small text-muted">Segure Ctrl (ou ⌘) para marcar mais de uma opção. Região e Regional restringem os municípios listados.</span>
            <span class="d-flex gap-2"><button class="btn btn-sm btn-primary btn-dash">Aplicar filtros</button><a class="btn btn-sm btn-outline-secondary" href="{{ url()->current() }}{{ request('fila') ? '?fila=' . request('fila') : '' }}">Limpar filtros</a></span>
        </div>
    </div>
</details>
@once
    <style>
        .nf-summary { cursor: pointer; font-weight: 700; font-size: .85rem; color: #263cc8; list-style: none; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .1rem; }
        .nf-summary::-webkit-details-marker { display: none; }
        .nf-summary::after { content: '▾'; font-size: .8rem; transition: transform .15s; }
        .nf[open] > .nf-summary::after { transform: rotate(180deg); }
        .nf-hint { font-weight: 400; color: #64748b; }
        .nf-body { margin-top: .5rem; padding: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; flex-direction: column; gap: 1rem; }
        .nf-title { font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; margin-bottom: .4rem; padding-bottom: .3rem; border-bottom: 1px solid #e2e8f0; }
        .nf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .75rem 1rem; }
        .nf-label { display: block; font-size: .72rem; font-weight: 700; color: #334155; margin-bottom: .25rem; }
        .nf-select { border-radius: 8px; border-color: #d5deea; background-color: #fff; font-size: .8rem; }
        .nf-select option { padding: .2rem .45rem; border-radius: 4px; }
        .nf-body .form-control { border-radius: 8px; border-color: #d5deea; }
        .nf-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; padding-top: .75rem; border-top: 1px solid #e2e8f0; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const region = document.getElementById('nf-region'), regional = document.getElementById('nf-regional'), city = document.getElementById('nf-city');
            if (!city) return;
            const picked = el => el ? Array.from(el.selectedOptions).map(o => o.value) : [];
            const apply = () => {
                const rg = picked(region), rl = picked(regional);
                Array.from(city.options).forEach(o => {
                    const ok = (!rg.length || rg.includes(o.dataset.region)) && (!rl.length || rl.includes(o.dataset.regional));
                    o.hidden = !ok; o.disabled = !ok;
                    if (!ok) o.selected = false;
                });
            };
            [region, regional].forEach(el => el && el.addEventListener('change', apply));
            apply();
        });
    </script>
@endonce
