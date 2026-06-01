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
                'badge_class' => $type === 'injunction' ? 'text-bg-danger' : ($type === 'sentence' ? 'text-bg-warning' : 'text-bg-info'),
            ];
        })->all();
    }

    $demandIds = collect($demands)->pluck('id')->filter()->unique()->values()->all();

    $detailsByDemandId = [];
    $instructionByDemandId = [];
    $activeLinksByDemandId = [];
    if (!empty($demandIds)) {
        $detailsByDemandId = \Illuminate\Support\Facades\DB::table('legal_demands')
            ->whereIn('id', $demandIds)
            ->select(['id', 'internal_status', 'source_status_group', 'operator_sla_due_at', 'operator_sla_note'])
            ->get()
            ->keyBy('id');

        if ($noteId) {
            $instructionByDemandId = \Illuminate\Support\Facades\DB::table('legal_demand_note_instructions')
                ->whereIn('legal_demand_id', $demandIds)
                ->where('note_id', (int) $noteId)
                ->where('active', true)
                ->orderByDesc('id')
                ->get()
                ->groupBy('legal_demand_id')
                ->map(fn($g) => $g->first())
                ->all();

            $activeLinksByDemandId = \Illuminate\Support\Facades\DB::table('legal_demand_note_activity_links')
                ->whereIn('legal_demand_id', $demandIds)
                ->where('note_id', (int) $noteId)
                ->whereNull('unlinked_at')
                ->orderByDesc('linked_at')
                ->select(['legal_demand_id', 'activity_type', 'activity_id', 'linked_at'])
                ->get()
                ->groupBy('legal_demand_id')
                ->all();
        }
    }
@endphp

@if (!empty($demands))
    @php
        $maxVisibleBadges = 3;
        $visibleDemands = collect($demands)->take($maxVisibleBadges)->values();
        $hiddenDemands = collect($demands)->slice($maxVisibleBadges)->values();
        $hiddenCount = $hiddenDemands->count();
        $extraModalId = 'legal-demand-tag-extra-modal-' . ($rowKey ?: 'row');
    @endphp
    <div class="d-flex flex-wrap gap-1 justify-content-center">
        @foreach ($visibleDemands as $demand)
            @php
                $modalId = 'legal-demand-tag-modal-' . ($rowKey ?: 'row') . '-' . ($demand['id'] ?? uniqid());
                $detail = $detailsByDemandId[$demand['id']] ?? null;
                $operatorSla = $detail?->operator_sla_due_at ? \Carbon\Carbon::parse($detail->operator_sla_due_at)->format('d/m/Y H:i') : null;
                $displaySla = $operatorSla ?: ($demand['due_at'] ?? 'Sem prazo');
                $ins = $instructionByDemandId[$demand['id']] ?? null;
                $links = $activeLinksByDemandId[$demand['id']] ?? collect();
            @endphp
            <button
                type="button"
                class="badge border-0 rounded-pill {{ $demand['badge_class'] ?? 'text-bg-secondary' }}"
                style="font-size: .72rem;"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}"
            >
                {{ $demand['type_label'] ?? 'Demanda' }}
            </button>

            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title mb-0">{{ $demand['type_label'] ?? 'Demanda' }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body small">
                            <div><strong>Status:</strong> {{ $demand['status'] ?? 'Sem status' }}</div>
                            <div class="mt-1"><strong>Prazo conclusao:</strong> {{ $demand['due_at'] ?? 'Sem prazo' }}</div>
                            <div class="mt-1">
                                <strong>SLA operador:</strong> {{ $displaySla }}
                                @if (!empty($demand['is_overdue']))
                                    <span class="text-danger">Vencido</span>
                                @elseif (($displaySla ?? 'Sem prazo') === 'Sem prazo')
                                    <span class="text-muted">Sem prazo</span>
                                @else
                                    <span class="text-success">No prazo</span>
                                @endif
                            </div>
                            @if($detail)
                                <div class="mt-1"><strong>Status interno:</strong> {{ $detail->internal_status ?: '—' }}</div>
                                <div class="mt-1"><strong>Status externo:</strong> {{ $detail->source_status_group ?: '—' }}</div>
                                @if(!empty($detail->operator_sla_note))
                                    <div class="mt-1"><strong>Obs SLA:</strong> {{ $detail->operator_sla_note }}</div>
                                @endif
                            @endif

                            @if($noteId)
                                <hr>
                                <div><strong>Instrucao atual da nota:</strong> {{ $ins->instruction ?? 'Sem instrucao' }}</div>
                                <div class="mt-2">
                                    <strong>Associada atualmente em:</strong>
                                    @if(count($links))
                                        @foreach($links as $lnk)
                                            <div>- {{ $lnk->activity_type }} #{{ $lnk->activity_id }} ({{ \Carbon\Carbon::parse($lnk->linked_at)->format('d/m/Y H:i') }})</div>
                                        @endforeach
                                    @else
                                        <div class="text-muted">Sem atividade vinculada no momento.</div>
                                    @endif
                                </div>
                                <hr>
                                <form method="POST" action="{{ route('legal.tag.update') }}">
                                    @csrf
                                    <input type="hidden" name="demand_id" value="{{ $demand['id'] }}">
                                    <input type="hidden" name="note_id" value="{{ $noteId }}">
                                    <div class="mb-2">
                                        <label class="form-label mb-1">SLA do operador</label>
                                        <input type="datetime-local" class="form-control form-control-sm" name="operator_sla_due_at"
                                            value="{{ $detail?->operator_sla_due_at ? \Carbon\Carbon::parse($detail->operator_sla_due_at)->format('Y-m-d\\TH:i') : '' }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label mb-1">Observacao SLA</label>
                                        <input type="text" class="form-control form-control-sm" name="operator_sla_note" value="{{ $detail?->operator_sla_note ?? '' }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label mb-1">Instrucao para esta nota</label>
                                        <textarea class="form-control form-control-sm" rows="3" name="instruction">{{ $ins->instruction ?? '' }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if($hiddenCount > 0)
            <button
                type="button"
                class="badge border-0 rounded-pill text-bg-secondary"
                style="font-size: .72rem;"
                data-bs-toggle="modal"
                data-bs-target="#{{ $extraModalId }}"
            >
                +{{ $hiddenCount }}
            </button>

            <div class="modal fade" id="{{ $extraModalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title mb-0">Demais demandas vinculadas ({{ $hiddenCount }})</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body small">
                            @foreach($hiddenDemands as $demand)
                                <div class="d-flex align-items-center justify-content-between border-bottom py-1">
                                    <span>
                                        <span class="badge {{ $demand['badge_class'] ?? 'text-bg-secondary' }}">{{ $demand['type_label'] ?? 'Demanda' }}</span>
                                        <span class="ms-1">#{{ $demand['id'] ?? '—' }}</span>
                                    </span>
                                    <span class="text-muted">{{ $demand['due_at'] ?? 'Sem prazo' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
