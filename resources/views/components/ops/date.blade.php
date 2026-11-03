@props([
    'date'  => null,
    'tone'  => 'muted',
    'time'  => false,
    'hint'  => null,
    'sub'   => null,
])

{{-- Data de tela operacional: sempre dd/mm/aaaa (hora opcional, ou o texto de `sub`, em segunda linha). O tom vem de `App\Support\Ops\Deadline`; "muted" = sem prazo, só informação. --}}
@if ($date)
    @php $date = $date instanceof \Carbon\CarbonInterface ? $date : \Carbon\Carbon::parse($date); @endphp
    <span {{ $attributes->class(['ops-date', 'tone-' . $tone, 'is-alert' => $tone !== 'muted']) }} @if ($hint) title="{{ $hint }}" @endif>
        <span class="ops-date-day">{{ $date->format('d/m/Y') }}</span>
        @if ($sub)
            <span class="ops-date-time">{{ $sub }}</span>
        @elseif ($time)
            <span class="ops-date-time">{{ $date->format('H:i') }}</span>
        @endif
    </span>
@else
    <span class="ops-empty" aria-label="Sem data">—</span>
@endif
