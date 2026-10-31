@props(['items' => []])

<div class="inform-kpis">
    @foreach ($items as $item)
        <div class="inform-kpi {{ ($item['tone'] ?? null) === 'danger' ? 'is-danger' : '' }}">
            <span>{{ $item['label'] }}</span>
            <strong>{{ filled($item['value'] ?? null) ? $item['value'] : '—' }}</strong>
        </div>
    @endforeach
</div>
