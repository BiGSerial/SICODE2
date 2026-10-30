@props([
    'partial',
    'dark'  => false,
    'dates' => true,
])

{{-- Checkpoints da parcial: Atividade → Aprovação → Fiscalização → Medição → Finalizado.
     Regras em App\Services\Engineers\PartialProgress. Estilo: resources/sass/closure/_steps.scss. --}}
@php
    $steps = \App\Services\Engineers\PartialProgress::steps($partial);
    $icons = ['done' => 'ri-check-line', 'current' => 'ri-loader-4-line', 'rejected' => 'ri-close-line', 'todo' => ''];
    $names = ['done' => 'concluída', 'current' => 'em andamento', 'rejected' => 'rejeitada', 'todo' => 'pendente'];
@endphp

<ol {{ $attributes->class(['pstep', 'is-dark' => $dark]) }} aria-label="Posição do informe parcial: {{ \App\Services\Engineers\PartialProgress::headline($partial) }}">
    @foreach ($steps as $step)
        <li class="pstep-item is-{{ $step['state'] }}"
            title="{{ $step['label'] }} · {{ $names[$step['state']] }}{{ $step['at'] ? ' · ' . $step['at']->format('d/m/Y H:i') : '' }}{{ $step['by'] ? ' · ' . $step['by'] : '' }}">
            <span class="pstep-dot"><i class="{{ $icons[$step['state']] }}" aria-hidden="true"></i></span>
            <span class="pstep-label">{{ $step['label'] }}</span>
            @if ($dates && $step['at'] && $step['state'] !== 'todo')
                <span class="pstep-date">{{ $step['at']->format('d/m') }}</span>
            @endif
        </li>
    @endforeach
</ol>
