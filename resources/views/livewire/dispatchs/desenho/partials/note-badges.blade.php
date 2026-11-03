{{-- Selos extras da célula da nota no Controle de Desenho: nota expressa e prioridade. --}}
@if ($row['is45'])
    <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" hint="Nota expressa: prazo de execução de 45 dias" />
@endif
@if ($row['priority'])
    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
        wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})" />
@endif
