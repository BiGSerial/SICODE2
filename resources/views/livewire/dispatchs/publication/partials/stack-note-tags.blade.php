{{-- Selos extras da Nota no Controle de Publicação: prioridade, expressa (45 dias) e transferência. --}}
@if ($row['priority'])
    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
        wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})" />
@endif
@if ($row['is45'])
    <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" hint="Nota expressa: prazo de execução de 45 dias" />
@endif
@if ($row['block'])
    <x-ops.badge sm tone="blue" icon="ri-arrow-left-right-line" label="Transferência" hint="Atividade em transferência" />
@endif
