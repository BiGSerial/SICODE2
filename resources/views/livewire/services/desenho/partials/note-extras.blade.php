{{-- Selos extras da célula da nota em Serviços → Desenho: nota expressa, prioridade e demandas jurídicas. --}}
@if ($row['express'])
    <x-ops.badge sm tone="red" icon="ri-fire-line" label="Expressa" :hint="'Nota expressa: prazo de execução de ' . $row['expressDays'] . ' dias'" />
@endif
@if ($row['priority'])
    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
        wire:click.prevent="$emit('infoPriority', '{{ $row['id'] }}')" />
@endif
<x-legal.note-demand-tags :demands="$legalTagsByNoteId[$row['noteId']] ?? []" :row-key="'services-desenho-accompany-' . $row['id']" />
