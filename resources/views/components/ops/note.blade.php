@props([
    'note',
    'kind' => null,
    'tone' => 'blue',
    'hint' => null,
    'sub'  => null,
])

{{-- Célula da Nota/OV em toda lista operacional. Sem `kind`: número simples. Com `kind="d5"` ou `kind="ri"`: o número dentro do selo
     (azul = 1º ciclo, verde = 2º ciclo, cinza = já despachada). RI segue o selo do 1º ciclo da D5 (azul). O `wire:click` do chamador
     vai para o selo. `sub` = linha pequena (Informe #, Parcial #). O slot recebe selos extras (prioridade, ADS tácita, demandas). --}}
<div class="ops-cell-stack is-center copy-text" data-value="{{ $note }}">
    @if ($kind)
        <x-ops.badge {{ $attributes->class(['ops-note-badge', 'ops-clickable' => $attributes->whereStartsWith('wire:click')->isNotEmpty()]) }} :tone="$tone" :hint="$hint"><strong>{{ mb_strtoupper($kind) }}</strong> {{ $note }}</x-ops.badge>
    @else
        <span class="ops-note">{{ $note }}</span>
    @endif
    @if ($sub)
        <span class="ops-sub">{{ $sub }}</span>
    @endif
    {{ $slot }}
</div>
