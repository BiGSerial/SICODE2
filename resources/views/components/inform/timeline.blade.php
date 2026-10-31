@props(['steps' => []])

<div class="inform-timeline">
    @foreach ($steps as $step)
        <div class="inform-step {{ $step['tone'] === 'danger' ? 'is-danger' : ($step['done'] ? 'is-done' : '') }}">
            <div class="icon"><i class="{{ $step['icon'] }}"></i></div>
            <div>
                <span>{{ $step['label'] }}</span>
                <strong>{{ $step['at'] ? $step['at']->format('d/m/Y H:i:s') : 'Pendente' }}</strong>
                @if ($step['who'])
                    <small>{{ $step['who'] }}</small>
                @endif
            </div>
        </div>
    @endforeach
</div>
