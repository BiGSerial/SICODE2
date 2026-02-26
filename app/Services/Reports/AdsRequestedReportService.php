<?php

namespace App\Services\Reports;

use App\Enum\AdsRequestStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdsRequestedReportService
{
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $rows = $this->buildQuery($filters)->paginate($perPage);

        $rows->setCollection(
            $rows->getCollection()->map(fn ($row) => $this->enrichRow($row))
        );

        return $rows;
    }

    /**
     * @return array{
     *   opened_count:int,
     *   opened_daily_avg:float,
     *   delivered_avg_hours:float,
     *   delivered_avg_label:string,
     *   in_progress_now_count:int
     * }
     */
    public function summarize(array $filters): array
    {
        $openedCount = 0;
        $deliveredTotalHours = 0.0;
        $deliveredCount = 0;

        foreach ($this->buildQuery($filters)->cursor() as $row) {
            $openedCount++;

            $requestedAt = $this->asCarbon($row->requested_at ?? null);
            $deliveredAt = $this->resolveDeliveredAt($row);

            if ($requestedAt && $deliveredAt && $deliveredAt->greaterThan($requestedAt)) {
                $deliveredTotalHours += $requestedAt->diffInSeconds($deliveredAt) / 3600;
                $deliveredCount++;
            }
        }

        $periodDays = $this->resolvePeriodDays($filters);
        $dailyAvg = $periodDays > 0 ? $openedCount / $periodDays : 0.0;
        $avgHours = $deliveredCount > 0 ? $deliveredTotalHours / $deliveredCount : 0.0;

        $inProgressNowCount = $this->buildNowBaseQuery($filters)
            ->where('ar.status', AdsRequestStatus::IN_PROGRESS->value)
            ->count();

        return [
            'opened_count' => $openedCount,
            'opened_daily_avg' => round($dailyAvg, 2),
            'delivered_avg_hours' => round($avgHours, 2),
            'delivered_avg_label' => $this->formatDuration((int) round($avgHours * 3600)),
            'in_progress_now_count' => $inProgressNowCount,
        ];
    }

    public function buildQuery(array $filters)
    {
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;

        $query = $this->buildNowBaseQuery($filters)
            ->select([
                'ar.id',
                'ar.note_id',
                'ar.status',
                'ar.description',
                'ar.created_at as requested_at',
                'ar.completed_at',
                'ar.delivered_at',
                'n.note as note_number',
                DB::raw('COALESCE(c.name, "—") as company_name'),
                DB::raw('COALESCE(la.tacit, 0) as tacit_flag'),
            ])
            ->orderByDesc('ar.created_at');

        if ($dateIn) {
            $query->whereDate('ar.created_at', '>=', $dateIn);
        }

        if ($dateOut) {
            $query->whereDate('ar.created_at', '<=', $dateOut);
        }

        return $query;
    }

    public function enrichRow(object $row): array
    {
        $status = AdsRequestStatus::tryFrom((string) ($row->status ?? ''));
        $requestedAt = $this->asCarbon($row->requested_at ?? null);
        $deliveredAt = $this->resolveDeliveredAt($row);
        $referenceAt = $deliveredAt ?: now();
        $seconds = ($requestedAt && $referenceAt && $referenceAt->greaterThan($requestedAt))
            ? $requestedAt->diffInSeconds($referenceAt)
            : 0;

        return [
            'id' => (int) $row->id,
            'note_number' => (string) ($row->note_number ?? $row->note_id ?? '—'),
            'company_name' => (string) ($row->company_name ?? '—'),
            'status_value' => (string) ($row->status ?? ''),
            'status_label' => $status?->label() ?? (string) ($row->status ?? '—'),
            'status_badge' => $status?->badgeClass() ?? 'text-bg-secondary',
            'is_tacit' => $this->resolveTacitFlag($row),
            'requested_at' => $requestedAt,
            'delivered_at' => $deliveredAt,
            'elapsed_seconds' => $seconds,
            'elapsed_label' => $this->formatDuration($seconds),
        ];
    }

    private function buildNowBaseQuery(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $companyIds = $filters['companyIds'] ?? [];

        $query = DB::table('ads_requests as ar')
            ->leftJoin('notes as n', 'n.id', '=', 'ar.note_id')
            ->leftJoin('companies as c', 'c.id', '=', 'ar.company_id')
            ->leftJoinSub($this->latestAdsByNoteSubQuery(), 'la', function ($join) {
                $join->on('la.note_id', '=', 'ar.note_id');
            });

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('n.note', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%")
                    ->orWhere('ar.status', 'like', "%{$search}%");
            });
        }

        if (!empty($companyIds)) {
            $query->whereIn('ar.company_id', $companyIds);
        }

        return $query;
    }

    private function latestAdsByNoteSubQuery()
    {
        $latestIds = DB::table('adsforms')
            ->select('note_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('note_id');

        return DB::table('adsforms as af')
            ->joinSub($latestIds, 'latest', function ($join) {
                $join->on('latest.max_id', '=', 'af.id');
            })
            ->select('af.note_id', 'af.tacit');
    }

    private function resolveDeliveredAt(object $row): ?Carbon
    {
        $status = AdsRequestStatus::tryFrom((string) ($row->status ?? ''));
        $delivered = $this->asCarbon($row->delivered_at ?? null);
        if ($delivered) {
            return $delivered;
        }

        if ($status === AdsRequestStatus::DONE) {
            return $this->asCarbon($row->completed_at ?? null);
        }

        return null;
    }

    private function resolveTacitFlag(object $row): bool
    {
        if (!empty($row->tacit_flag)) {
            return true;
        }

        $description = strtolower((string) ($row->description ?? ''));
        return str_contains($description, 'tacita') || str_contains($description, 'tácita');
    }

    private function resolvePeriodDays(array $filters): int
    {
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;

        if (!$dateIn || !$dateOut) {
            return 1;
        }

        $start = Carbon::parse($dateIn)->startOfDay();
        $end = Carbon::parse($dateOut)->startOfDay();

        if ($end->lessThan($start)) {
            return 1;
        }

        return $start->diffInDays($end) + 1;
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0h';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        }

        $minutes = intdiv($seconds % 3600, 60);
        return "{$hours}h {$minutes}m";
    }
}
