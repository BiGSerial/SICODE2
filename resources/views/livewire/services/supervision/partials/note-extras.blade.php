{{-- Selos extras da célula da Nota no Acompanhamento da Fiscalização (prioridade, informe cancelado e demandas jurídicas). Usa `$row`. --}}
@if ($row['priority'])
    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
        wire:click.prevent="$emit('infoPriority', '{{ $row['id'] }}')" />
@endif
<x-work-report.canceled-badge :report="$canceledByProduction[$row['id']] ?? null" />
<x-legal.note-demand-tags :demands="$legalTagsByNoteId[$row['noteId']] ?? []" :row-key="'services-supervision-accompany-' . $row['id']" />
