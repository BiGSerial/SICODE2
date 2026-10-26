@props([
    'items',
])

{{-- Linha do tempo de etapas. Cada item: ['label', 'at' (data), 'by' (opcional), 'duration' (opcional, ex. "3 dias"), 'tone' (opcional), 'note' (opcional)]. --}}
<ol class="ui-timeline">
    @foreach ($items as $item)
        <li class="tone-{{ $item['tone'] ?? 'muted' }}">
            <div class="tl-label">{{ $item['label'] }}</div>
            <div class="tl-meta">
                @if (!empty($item['at']))
                    {{ \Carbon\Carbon::parse($item['at'])->format('d/m/Y H:i') }}
                @endif
                @if (!empty($item['by']))
                    · {{ $item['by'] }}
                @endif
                @if (!empty($item['duration']))
                    · <strong>{{ $item['duration'] }}</strong>
                @endif
            </div>
            @if (!empty($item['note']))
                <div class="tl-note">{{ $item['note'] }}</div>
            @endif
        </li>
    @endforeach
</ol>
