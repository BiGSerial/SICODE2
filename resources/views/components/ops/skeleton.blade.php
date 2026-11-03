@props([
    'rows' => 8,
    'cols' => 8,
])

{{-- Esqueleto da tabela durante o carregamento (aparece após ~200 ms, para não piscar em respostas rápidas). Mostrar com `wire:loading.delay`. --}}
<div {{ $attributes->class(['ops-skeleton']) }} aria-hidden="true">
    @for ($r = 0; $r < $rows; $r++)
        <div class="ops-skeleton-row">
            @for ($c = 0; $c < $cols; $c++)
                <span class="ops-skeleton-cell" style="--w: {{ 45 + (($r * 7 + $c * 13) % 45) }}%"></span>
            @endfor
        </div>
    @endfor
</div>
