@props(['items' => []])

<div class="inform-facts">
    @foreach ($items as $item)
        <div>
            <span>{{ $item['label'] }}</span>
            <strong>{{ filled($item['value'] ?? null) ? mb_strtoupper((string) $item['value']) : '—' }}</strong>
        </div>
    @endforeach
</div>
