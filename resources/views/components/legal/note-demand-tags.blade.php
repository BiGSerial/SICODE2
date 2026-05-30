@props([
    'demands' => [],
    'noteId' => null,
    'rowKey' => null,
])

@php
    if (empty($demands) && $noteId) {
        $rows = \Illuminate\Support\Facades\DB::table('legal_case_note as lcn')
            ->join('legal_demands as ld', 'ld.legal_case_id', '=', 'lcn.legal_case_id')
            ->where('lcn.note_id', (int) $noteId)
            ->whereIn('ld.source_type', ['injunction', 'sentence', 'subsidy'])
            ->whereNull('ld.closed_at')
            ->whereNotIn('ld.internal_status', ['cancelled', 'ignored'])
            ->orderByRaw('CASE WHEN ld.source_due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('ld.source_due_at')
            ->select(['ld.id', 'ld.source_type', 'ld.source_status', 'ld.source_due_at'])
            ->get();

        $demands = $rows->map(function ($row) {
            $type = (string) $row->source_type;
            return [
                'id' => (int) $row->id,
                'type_label' => $type === 'injunction' ? 'Liminar' : ($type === 'sentence' ? 'Sentenca' : 'Subsidio'),
                'status' => (string) ($row->source_status ?: 'Sem status'),
                'due_at' => $row->source_due_at ? \Carbon\Carbon::parse($row->source_due_at)->format('d/m/Y H:i') : 'Sem prazo',
                'is_overdue' => $row->source_due_at ? \Carbon\Carbon::parse($row->source_due_at)->isPast() : false,
                'badge_class' => $type === 'injunction' ? 'bg-danger text-white' : ($type === 'sentence' ? 'bg-warning text-dark' : 'bg-info text-dark'),
            ];
        })->all();
    }
@endphp

@if (!empty($demands))
    <div class="d-flex flex-wrap gap-1 justify-content-center">
        @foreach ($demands as $demand)
            @php
                $modalId = 'legal-demand-tag-modal-' . ($rowKey ?: 'row') . '-' . ($demand['id'] ?? uniqid());
            @endphp
            <button
                type="button"
                class="badge border-0 {{ $demand['badge_class'] ?? 'bg-secondary text-white' }}"
                style="font-size: .72rem;"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}"
            >
                {{ $demand['type_label'] ?? 'Demanda' }}
            </button>

            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title mb-0">{{ $demand['type_label'] ?? 'Demanda' }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body small">
                            <div><strong>Status:</strong> {{ $demand['status'] ?? 'Sem status' }}</div>
                            <div class="mt-1"><strong>Prazo conclusao:</strong> {{ $demand['due_at'] ?? 'Sem prazo' }}</div>
                            <div class="mt-1">
                                <strong>SLA:</strong>
                                @if (!empty($demand['is_overdue']))
                                    <span class="text-danger">Vencido</span>
                                @elseif (($demand['due_at'] ?? 'Sem prazo') === 'Sem prazo')
                                    <span class="text-muted">Sem prazo</span>
                                @else
                                    <span class="text-success">No prazo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
