@props([
    'demands' => [],
    'noteId' => null,
    'rowKey' => null,
])

@php
    if (empty($demands) && $noteId) {
        $linkedDemandIds = collect()
            ->merge(
                \Illuminate\Support\Facades\DB::table('legal_demand_note_instructions')
                    ->where('note_id', (int) $noteId)
                    ->where('active', true)
                    ->pluck('legal_demand_id')
            )
            ->merge(
                \Illuminate\Support\Facades\DB::table('legal_demand_note_activity_links')
                    ->where('note_id', (int) $noteId)
                    ->whereNull('unlinked_at')
                    ->pluck('legal_demand_id')
            )
            ->filter()
            ->unique()
            ->values();

        $rows = $linkedDemandIds->isEmpty()
            ? collect()
            : \Illuminate\Support\Facades\DB::table('legal_demands as ld')
            ->whereIn('ld.id', $linkedDemandIds->all())
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
                'type_label' => $type === 'injunction' ? 'Liminar' : ($type === 'sentence' ? 'Sentença' : 'Subsídio'),
                'status' => (string) ($row->source_status ?: 'Sem status'),
                'due_at' => $row->source_due_at ? \Carbon\Carbon::parse($row->source_due_at)->format('d/m/Y H:i') : 'Sem prazo',
                'due_at_raw' => $row->source_due_at,
                'is_overdue' => $row->source_due_at ? \Carbon\Carbon::parse($row->source_due_at)->isPast() : false,
                'badge_class' => $type === 'injunction' ? 'text-bg-danger' : ($type === 'sentence' ? 'text-bg-warning' : 'text-bg-info'),
            ];
        })->all();
    }

    $demandIds = collect($demands)->pluck('id')->filter()->unique()->values()->all();

    $detailsByDemandId = [];
    $instructionByDemandId = [];
    $activeLinksByDemandId = [];
    $sharedCommentsByDemandId = [];
    if (!empty($demandIds)) {
        $detailsByDemandId = \Illuminate\Support\Facades\DB::table('legal_demands')
            ->whereIn('id', $demandIds)
            ->select([
                'id',
                'title',
                'summary',
                'source_subject',
                'source_description',
                'source_status',
                'source_status_group',
                'source_case_number',
                'source_process_number',
                'responsible_area_name',
                'internal_status',
                'operator_sla_due_at',
                'operator_sla_note',
            ])
            ->get()
            ->keyBy('id');

        $sharedCommentsByDemandId = \Illuminate\Support\Facades\DB::table('legal_demand_comments as ldc')
            ->leftJoin('users as u', 'u.id', '=', 'ldc.user_id')
            ->whereIn('ldc.legal_demand_id', $demandIds)
            ->where('ldc.visibility', 'shared')
            ->whereNull('ldc.legal_demand_subdemand_id')
            ->orderBy('ldc.created_at')
            ->select([
                'ldc.legal_demand_id',
                'ldc.comment',
                'ldc.created_at',
                'u.name as user_name',
            ])
            ->get()
            ->groupBy('legal_demand_id');

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
                $sharedComments = $sharedCommentsByDemandId[$demand['id']] ?? collect();
                $sourceDueAt = null;
                if (!empty($demand['due_at_raw'])) {
                    try { $sourceDueAt = \Carbon\Carbon::parse($demand['due_at_raw']); } catch (\Throwable $e) {}
                }
                if (!$sourceDueAt && !empty($demand['due_at']) && $demand['due_at'] !== 'Sem prazo') {
                    try { $sourceDueAt = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $demand['due_at']); } catch (\Throwable $e) {}
                }
                $sourceDueIsOverdue = $sourceDueAt?->isPast() ?? false;
                $sourceDueIsToday = $sourceDueAt?->isToday() ?? false;
                $deadlineRaw = $detail?->operator_sla_due_at ?: ($demand['due_at_raw'] ?? null);
                $deadlineAt = null;
                if ($deadlineRaw && $deadlineRaw !== 'Sem prazo') {
                    try { $deadlineAt = \Carbon\Carbon::parse($deadlineRaw); } catch (\Throwable $e) {}
                }
                if (!$deadlineAt && !empty($demand['due_at']) && $demand['due_at'] !== 'Sem prazo') {
                    try { $deadlineAt = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $demand['due_at']); } catch (\Throwable $e) {}
                }
                $deadlineIsOverdue = $deadlineAt?->isPast() ?? false;
                $deadlineIsToday   = $deadlineAt?->isToday() ?? false;
                $contextText = $detail?->summary ?: ($detail?->source_subject ?: ($detail?->source_description ?: null));
                $statusLabels = [
                    'new_imported'     => 'Nova',
                    'triage'           => 'Em triagem',
                    'assigned'         => 'Atribuída',
                    'in_progress'      => 'Em andamento',
                    'awaiting_field'   => 'Aguardando campo',
                    'field_returned'   => 'Retornada pelo campo',
                    'closed'           => 'Encerrada',
                    'cancelled'        => 'Cancelada',
                    'ignored'          => 'Ignorada',
                    'open_redirected'  => 'Redirecionada',
                    'open'             => 'Aberta',
                    'returned_for_correction' => 'Para correção',
                    'unknown'          => 'Não classificado',
                ];
                $internalStatus = $statusLabels[$detail?->internal_status] ?? ($detail?->internal_status ?: null);
                $externalStatus  = $statusLabels[$detail?->source_status_group] ?? ($detail?->source_status_group ?: null);
                $sourceType = collect($demands)->firstWhere('id', $demand['id'])['type_label'] ?? 'Demanda';
                $sourceTypeLower = mb_strtolower($sourceType);

                // Deadline visual tokens
                if (!$deadlineAt) {
                    $deadlineBg    = '#64748b';
                    $deadlineLabel = 'Sem prazo';
                    $deadlineIcon  = 'bi-dash-circle';
                    $bannerStyle   = 'background:#f1f5f9;border-color:#e2e8f0;';
                    $bannerTextStyle = 'color:#475569;';
                } elseif ($deadlineIsOverdue) {
                    $deadlineBg    = '#dc2626';
                    $deadlineLabel = 'VENCIDO';
                    $deadlineIcon  = 'bi-exclamation-octagon-fill';
                    $bannerStyle   = 'background:rgba(220,38,38,.07);border-color:#dc2626;';
                    $bannerTextStyle = 'color:#dc2626;';
                } elseif ($deadlineIsToday) {
                    $deadlineBg    = '#d97706';
                    $deadlineLabel = 'VENCE HOJE';
                    $deadlineIcon  = 'bi-clock-history';
                    $bannerStyle   = 'background:rgba(217,119,6,.07);border-color:#d97706;';
                    $bannerTextStyle = 'color:#92400e;';
                } else {
                    $deadlineBg    = '#059669';
                    $deadlineLabel = 'No prazo';
                    $deadlineIcon  = 'bi-check-circle-fill';
                    $bannerStyle   = 'background:rgba(5,150,105,.06);border-color:#059669;';
                    $bannerTextStyle = 'color:#065f46;';
                }

                if ($sourceDueIsOverdue) {
                    $sourceDueLabel = 'Prazo do ' . $sourceTypeLower . ' vencido';
                    $sourceDueStyle = 'background:#fef2f2;border-color:#dc2626;color:#991b1b;';
                    $sourceDueIcon = 'bi-exclamation-octagon-fill';
                } elseif ($sourceDueIsToday) {
                    $sourceDueLabel = 'Prazo do ' . $sourceTypeLower . ' vence hoje';
                    $sourceDueStyle = 'background:#fffbeb;border-color:#d97706;color:#92400e;';
                    $sourceDueIcon = 'bi-clock-history';
                } else {
                    $sourceDueLabel = $sourceDueAt ? 'Prazo original no prazo' : 'Sem prazo original';
                    $sourceDueStyle = 'background:#f8fafc;border-color:#cbd5e1;color:#475569;';
                    $sourceDueIcon = 'bi-info-circle-fill';
                }

                // Header accent per type
                $typeIcon = $demand['type_label'] === 'Liminar' ? 'bi-lightning-charge-fill' : ($demand['type_label'] === 'Sentença' ? 'bi-balance-scale' : 'bi-file-earmark-text-fill');
            @endphp

            {{-- Badge trigger --}}
            <button
                type="button"
                class="badge border-0 rounded-pill {{ $demand['badge_class'] ?? 'text-bg-secondary' }} {{ ($deadlineIsOverdue || $sourceDueIsOverdue) ? 'legal-pulse' : '' }}"
                style="font-size:.72rem;"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}"
            >{{ $demand['type_label'] ?? 'Demanda' }}</button>

            {{-- Modal --}}
            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:860px;">
                    <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius:1rem;">

                        {{-- Header escuro com identidade jurídica --}}
                        <div class="modal-header border-0 px-4 pt-4 pb-3" style="background:var(--legal-primary,#1e3a5f);">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 me-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:40px;height:40px;background:rgba(255,255,255,.12);">
                                    <i class="bi {{ $typeIcon }} text-white" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="text-white fw-bold" style="font-size:.95rem;letter-spacing:.01em;">
                                        {{ $demand['type_label'] ?? 'Demanda' }}
                                        @if($detail?->source_case_number)
                                            <span style="opacity:.55;font-weight:400;"> · Caso {{ $detail->source_case_number }}</span>
                                        @endif
                                    </div>
                                    @if($detail?->responsible_area_name)
                                        <div style="font-size:.72rem;color:rgba(255,255,255,.55);margin-top:1px;">
                                            {{ $detail->responsible_area_name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>

                        {{-- Body --}}
                        <div class="modal-body p-0" style="background:#f8fafc;">

                            {{-- Banner de prazo --}}
                            <div class="px-4 pt-3 pb-3">
                                <div class="rounded-3 px-3 py-3 border" style="{{ $bannerStyle }}border-width:1.5px!important;">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div>
                                            <div style="font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;{{ $bannerTextStyle }}opacity:.7;">
                                                SLA solicitado para conclusão
                                            </div>
                                            <div class="fw-bold mt-1" style="font-size:1.2rem;{{ $bannerTextStyle }}">
                                                {{ $displaySla }}
                                            </div>
                                            <div style="font-size:.72rem;color:#64748b;margin-top:2px;">
                                                Este é o prazo máximo solicitado pelo controlador para retorno da nota.
                                            </div>
                                        </div>
                                        <span class="badge d-flex align-items-center gap-1 px-2 py-2 rounded-pill flex-shrink-0"
                                              style="background:{{ $deadlineBg }};font-size:.7rem;font-weight:700;letter-spacing:.04em;">
                                            <i class="bi {{ $deadlineIcon }}"></i>
                                            {{ $deadlineLabel }}
                                        </span>
                                    </div>
                                    <div class="mt-3 rounded-3 px-3 py-2 border" style="{{ $sourceDueStyle }}border-width:1.5px!important;">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi {{ $sourceDueIcon }} mt-1"></i>
                                            <div>
                                                <div style="font-size:.75rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;">
                                                    {{ $sourceDueLabel }}
                                                </div>
                                                <div style="font-size:.78rem;margin-top:2px;">
                                                    Prazo original do {{ $sourceTypeLower }}:
                                                    <strong>{{ $demand['due_at'] ?? 'Sem prazo' }}</strong>.
                                                    @if($sourceDueIsOverdue)
                                                        Trate com urgência, mesmo que o SLA solicitado ainda esteja no prazo.
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Instrução do Controlador — bloco hero --}}
                            @if($noteId)
                                <div class="px-4 pb-3">
                                    <div class="rounded-3 p-3"
                                         style="background:#fff;border:1.5px solid {{ $ins ? 'var(--legal-primary,#1e3a5f)' : '#e2e8f0' }};">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="d-flex align-items-center justify-content-center rounded"
                                                  style="width:24px;height:24px;background:{{ $ins ? 'var(--legal-primary,#1e3a5f)' : '#e2e8f0' }};flex-shrink:0;">
                                                <i class="bi bi-clipboard2-check-fill" style="font-size:.75rem;color:{{ $ins ? '#fff' : '#94a3b8' }};"></i>
                                            </span>
                                            <span style="font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:{{ $ins ? 'var(--legal-primary,#1e3a5f)' : '#94a3b8' }};">
                                                Instrução do Controlador
                                            </span>
                                        </div>
                                        @if($ins)
                                            <p class="mb-0" style="font-size:.85rem;color:#0f172a;line-height:1.55;">{{ $ins->instruction }}</p>
                                        @else
                                            <p class="mb-0 fst-italic" style="font-size:.82rem;color:#94a3b8;">Sem instrução cadastrada para esta nota.</p>
                                        @endif

                                        @if(count($links))
                                            <div class="mt-3 pt-2" style="border-top:1px solid #f1f5f9;">
                                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">
                                                    Vínculo atual
                                                </div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($links as $lnk)
                                                        <span class="badge rounded-pill text-bg-primary" style="font-size:.7rem;font-weight:500;">
                                                            {{ $lnk->activity_type }} #{{ $lnk->activity_id }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Contexto + Situação --}}
                            <div class="px-4 pb-3">
                                <div class="row g-2">
                                    {{-- Contexto --}}
                                    @if($contextText || $detail?->title)
                                        <div class="{{ ($internalStatus || $externalStatus || $detail?->source_case_number || $detail?->source_process_number) ? 'col-12 col-sm-6' : 'col-12' }}">
                                            <div class="rounded-3 p-3 h-100" style="background:#fff;border:1px solid #e2e8f0;">
                                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--legal-primary,#1e3a5f);margin-bottom:6px;">
                                                    Contexto
                                                </div>
                                                @if($detail?->title)
                                                    <div class="fw-semibold mb-1" style="font-size:.82rem;color:#0f172a;">{{ $detail->title }}</div>
                                                @endif
                                                @if($contextText)
                                                    <div style="font-size:.78rem;color:#64748b;line-height:1.5;">{{ $contextText }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Situação --}}
                                    @if($internalStatus || $externalStatus || $demand['status'] || $detail?->source_case_number || $detail?->source_process_number)
                                        <div class="{{ ($contextText || $detail?->title) ? 'col-12 col-sm-6' : 'col-12' }}">
                                            <div class="rounded-3 p-3 h-100" style="background:#fff;border:1px solid #e2e8f0;">
                                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--legal-primary,#1e3a5f);margin-bottom:6px;">
                                                    Situação
                                                </div>
                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    @if($demand['status'] ?? null)
                                                        <span class="badge rounded-pill" style="background:var(--legal-primary,#1e3a5f);font-size:.68rem;">{{ $demand['status'] }}</span>
                                                    @endif
                                                    @if($internalStatus)
                                                        <span class="badge rounded-pill text-bg-secondary" style="font-size:.68rem;">{{ $internalStatus }}</span>
                                                    @endif
                                                    @if($externalStatus)
                                                        <span class="badge rounded-pill" style="background:#e2e8f0;color:#475569;font-size:.68rem;">{{ $externalStatus }}</span>
                                                    @endif
                                                </div>
                                                @if($detail?->source_process_number)
                                                    <div style="font-size:.75rem;color:#64748b;">
                                                        <span style="opacity:.7;">Processo</span>
                                                        <span class="d-block fw-semibold text-truncate" style="color:#0f172a;max-width:100%;" title="{{ $detail->source_process_number }}">{{ $detail->source_process_number }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- SLA Note — apenas se preenchida --}}
                            @if($detail?->operator_sla_note)
                                <div class="px-4 pb-3">
                                    <div class="rounded-3 px-3 py-2" style="background:#fff;border:1px solid #fde68a;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:.8rem;"></i>
                                            <span style="font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#92400e;">Motivo / Observação do SLA</span>
                                        </div>
                                        <div style="font-size:.8rem;color:#0f172a;">{{ $detail->operator_sla_note }}</div>
                                    </div>
                                </div>
                            @endif

                            {{-- Comentários compartilhados --}}
                            @if($sharedComments->count())
                                <div class="px-4 pb-4">
                                    <div style="font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;margin-bottom:8px;">
                                        Comentários compartilhados
                                    </div>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($sharedComments as $comment)
                                            <div class="rounded-3 px-3 py-2" style="background:#fff;border-left:3px solid var(--legal-primary,#1e3a5f);border-top:1px solid #e2e8f0;border-right:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;">
                                                <div style="font-size:.8rem;color:#0f172a;line-height:1.5;">{{ $comment->comment }}</div>
                                                <div class="mt-1" style="font-size:.68rem;color:#94a3b8;">
                                                    {{ $comment->user_name ?: 'Sistema' }} · {{ \Carbon\Carbon::parse($comment->created_at)->format('d/m/Y H:i') }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>{{-- /modal-body --}}
                    </div>
                </div>
            </div>
        @endforeach

        @if($hiddenCount > 0)
            <button
                type="button"
                class="badge border-0 rounded-pill text-bg-secondary"
                style="font-size:.72rem;"
                data-bs-toggle="modal"
                data-bs-target="#{{ $extraModalId }}"
            >+{{ $hiddenCount }}</button>

            <div class="modal fade" id="{{ $extraModalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
                    <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius:1rem;">
                        <div class="modal-header border-0 px-4 pt-4 pb-3" style="background:var(--legal-primary,#1e3a5f);">
                            <h6 class="modal-title mb-0 text-white fw-semibold" style="font-size:.9rem;">
                                Demais demandas vinculadas
                                <span style="opacity:.5;font-weight:400;">({{ $hiddenCount }})</span>
                            </h6>
                            <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body px-4 py-3" style="background:#f8fafc;">
                            <div class="d-flex flex-column gap-2">
                                @foreach($hiddenDemands as $demand)
                                    <div class="d-flex align-items-center justify-content-between rounded-3 px-3 py-2" style="background:#fff;border:1px solid #e2e8f0;">
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill {{ $demand['badge_class'] ?? 'text-bg-secondary' }}" style="font-size:.68rem;">{{ $demand['type_label'] ?? 'Demanda' }}</span>
                                            <span style="font-size:.78rem;color:#475569;">#{{ $demand['id'] ?? '—' }}</span>
                                        </span>
                                        <span style="font-size:.75rem;color:#94a3b8;">{{ $demand['due_at'] ?? 'Sem prazo' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
