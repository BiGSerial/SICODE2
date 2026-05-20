<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandInternalStatus;
use App\Enum\LegalSourcePresenceStatus;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandAssignment;
use App\Models\Legal\LegalImportBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LegalObservabilityService
{
    public function overviewCards(): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $soonEnd = $now->copy()->addDays(3)->endOfDay();

        $openStatuses = [
            LegalDemandInternalStatus::NEW_IMPORTED->value,
            LegalDemandInternalStatus::TRIAGE->value,
            LegalDemandInternalStatus::WAITING_CONTROLLER_ACTION->value,
            LegalDemandInternalStatus::SENT_TO_FIELD->value,
            LegalDemandInternalStatus::FIELD_RECEIVED->value,
            LegalDemandInternalStatus::WAITING_FIELD_RESPONSE->value,
            LegalDemandInternalStatus::RETURNED_BY_FIELD->value,
            LegalDemandInternalStatus::UNDER_CONTROLLER_REVIEW->value,
            LegalDemandInternalStatus::RETURNED_FOR_CORRECTION->value,
            LegalDemandInternalStatus::READY_TO_CLOSE_EXTERNAL->value,
            LegalDemandInternalStatus::CLOSED_INTERNAL->value,
            LegalDemandInternalStatus::REOPENED->value,
        ];

        return [
            'total_abertas' => LegalDemand::query()->whereIn('internal_status', $openStatuses)->count(),
            'total_vencidas' => LegalDemand::query()->whereNotNull('source_due_at')->where('source_due_at', '<', $now)->count(),
            'total_vencem_hoje' => LegalDemand::query()->whereBetween('source_due_at', [$todayStart, $todayEnd])->count(),
            'total_vencem_3_dias' => LegalDemand::query()->whereBetween('source_due_at', [$todayStart, $soonEnd])->count(),
            'total_sem_responsavel' => LegalDemand::query()->withoutResponsible()->count(),
            'total_aguardando_ponta' => LegalDemand::query()->whereIn('internal_status', [
                LegalDemandInternalStatus::SENT_TO_FIELD->value,
                LegalDemandInternalStatus::FIELD_RECEIVED->value,
                LegalDemandInternalStatus::WAITING_FIELD_RESPONSE->value,
            ])->count(),
            'total_missing_source' => LegalDemand::query()->where('source_presence_status', LegalSourcePresenceStatus::MISSING->value)->count(),
        ];
    }

    public function bySource(): Collection
    {
        return LegalDemand::query()
            ->select('source_type')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN source_due_at IS NOT NULL AND source_due_at < NOW() THEN 1 ELSE 0 END) as overdue')
            ->selectRaw('SUM(CASE WHEN source_presence_status = ? THEN 1 ELSE 0 END) as missing', [LegalSourcePresenceStatus::MISSING->value])
            ->groupBy('source_type')
            ->orderBy('source_type')
            ->get();
    }

    public function byAssignee(): Collection
    {
        return LegalDemandAssignment::query()
            ->leftJoin('users', 'users.id', '=', 'legal_demand_assignments.to_user_id')
            ->leftJoin('legal_demands', 'legal_demands.id', '=', 'legal_demand_assignments.legal_demand_id')
            ->selectRaw('COALESCE(users.name, "N/A") as assignee_name')
            ->selectRaw('COUNT(*) as received_total')
            ->selectRaw('SUM(CASE WHEN legal_demand_assignments.answered_at IS NULL THEN 1 ELSE 0 END) as pending_answer')
            ->selectRaw('SUM(CASE WHEN legal_demands.source_due_at IS NOT NULL AND legal_demands.source_due_at < NOW() AND legal_demand_assignments.answered_at IS NULL THEN 1 ELSE 0 END) as overdue')
            ->groupBy('assignee_name')
            ->orderByDesc('received_total')
            ->get();
    }

    public function importHealth(int $lastDays = 7): Collection
    {
        $since = now()->subDays($lastDays);

        return LegalImportBatch::query()
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')
            ->get()
            ->map(function (LegalImportBatch $batch) {
                $started = $batch->started_at ? Carbon::parse($batch->started_at) : null;
                $finished = $batch->finished_at ? Carbon::parse($batch->finished_at) : null;

                return [
                    'batch_id' => $batch->id,
                    'source_type' => $batch->source_type?->value ?? (string) $batch->source_type,
                    'started_at' => $started?->toDateTimeString(),
                    'finished_at' => $finished?->toDateTimeString(),
                    'duration_seconds' => ($started && $finished) ? $finished->diffInSeconds($started) : null,
                    'total_rows' => $batch->total_rows,
                    'new_rows' => $batch->new_rows,
                    'updated_rows' => $batch->updated_rows,
                    'unchanged_rows' => $batch->unchanged_rows,
                    'missing_rows' => $batch->missing_rows,
                    'returned_rows' => $batch->returned_rows,
                    'failed_rows' => $batch->failed_rows,
                    'status' => $batch->status,
                    'error_message' => $batch->error_message,
                ];
            });
    }

    public function bottlenecks(): array
    {
        return [
            'avg_hours_import_to_triage' => $this->avgEventDiffHours('imported', 'triage_started'),
            'avg_hours_triage_to_sent' => $this->avgEventDiffHours('triage_started', 'sent_to_field'),
            'avg_hours_sent_to_received' => $this->avgAssignmentDiffHours('sent_at', 'received_at'),
            'avg_hours_received_to_answered' => $this->avgAssignmentDiffHours('received_at', 'answered_at'),
        ];
    }

    private function avgAssignmentDiffHours(string $fromColumn, string $toColumn): ?float
    {
        $value = LegalDemandAssignment::query()
            ->whereNotNull($fromColumn)
            ->whereNotNull($toColumn)
            ->selectRaw("AVG(TIMESTAMPDIFF(HOUR, {$fromColumn}, {$toColumn})) as avg_hours")
            ->value('avg_hours');

        return $value !== null ? round((float) $value, 2) : null;
    }

    private function avgEventDiffHours(string $fromEvent, string $toEvent): ?float
    {
        $value = DB::table('legal_demand_events as e_from')
            ->join('legal_demand_events as e_to', function ($join) use ($toEvent) {
                $join->on('e_to.legal_demand_id', '=', 'e_from.legal_demand_id')
                    ->where('e_to.event_type', '=', $toEvent);
            })
            ->where('e_from.event_type', $fromEvent)
            ->whereColumn('e_to.occurred_at', '>=', 'e_from.occurred_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, e_from.occurred_at, e_to.occurred_at)) as avg_hours')
            ->value('avg_hours');

        return $value !== null ? round((float) $value, 2) : null;
    }
}
