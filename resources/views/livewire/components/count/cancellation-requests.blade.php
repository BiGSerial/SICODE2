<div wire:poll.180s>
    @if ($count)
        @if ($mode === 'unassigned')
            <span class="badge bg-danger align-middle ms-1">{{ $count }}</span>
        @elseif ($mode === 'in_progress')
            <span class="badge bg-warning text-dark align-middle ms-1">{{ $count }}</span>
        @elseif ($mode === 'engineer_pending')
            <span class="badge bg-info text-dark align-middle ms-1">{{ $count }}</span>
        @elseif ($mode === 'engineer_history')
            <span class="badge bg-secondary align-middle ms-1">{{ $count }}</span>
        @else
            <span class="badge bg-secondary align-middle ms-1">{{ $count }}</span>
        @endif
    @endif
</div>
