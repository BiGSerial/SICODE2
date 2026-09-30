{{-- Filtros por dados da Nota. Espera $noteFilterOptions. Dentro de um <form method="get">. --}}
@php
    $labels = \App\Services\Quality\QualityNoteFilters::COLUMNS + \App\Services\Quality\QualityNoteFilters::GEO;
@endphp
<details class="mt-2" @if (collect(array_keys($labels))->contains(fn ($key) => filled(request($key))) || request('dt_from') || request('dt_to') || request('days_left_max') !== null) open @endif>
    <summary class="small fw-bold text-primary" style="cursor: pointer;"><i class="ri-filter-3-line"></i> Mais filtros (rubrica, região, município, localização...)</summary>
    <div class="row g-2 mt-1">
        @foreach ($labels as $key => $label)
            <div class="col-md-4 col-xl-3">
                <label class="form-label small mb-1">{{ $label }}</label>
                <select name="{{ $key }}[]" multiple size="3" class="form-select form-select-sm">
                    @foreach ($noteFilterOptions[$key] ?? [] as $option)
                        <option value="{{ $option }}" @selected(in_array((string) $option, array_map('strval', (array) request($key)), true))>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="col-md-4 col-xl-3"><label class="form-label small mb-1">Data do status de</label><input type="date" name="dt_from" value="{{ request('dt_from') }}" class="form-control form-control-sm"></div>
        <div class="col-md-4 col-xl-3"><label class="form-label small mb-1">Data do status até</label><input type="date" name="dt_to" value="{{ request('dt_to') }}" class="form-control form-control-sm"></div>
        <div class="col-md-4 col-xl-3"><label class="form-label small mb-1">Dias restantes (máx.)</label><input type="number" name="days_left_max" value="{{ request('days_left_max') }}" class="form-control form-control-sm"></div>
        <div class="col-12 small text-muted">Segure Ctrl para marcar mais de uma opção. <a href="{{ url()->current() }}{{ request('fila') ? '?fila=' . request('fila') : '' }}">Limpar filtros</a></div>
    </div>
</details>
