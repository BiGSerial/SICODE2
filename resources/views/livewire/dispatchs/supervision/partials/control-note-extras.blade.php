{{-- Selos extras da célula da Nota no Controle de Fiscalização (prioridade e demandas jurídicas). Usa `$row` da lista. --}}
@if ($row['priority'])
    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
        wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})" />
@endif
<x-legal.note-demand-tags :demands="$row['legal']" :row-key="'dispatchs-supervision-' . $row['id']" />
