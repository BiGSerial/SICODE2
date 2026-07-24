<?php

namespace App\Support\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CancellationListQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int|string>|null  $visibleRequesterIds
     */
    public static function build(array $filters, ?array $visibleRequesterIds): Builder
    {
        $tokens = collect($filters['searchTokens'] ?? [])
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values()
            ->all();

        $requesterIds = collect($filters['requesterIds'] ?? [])
            ->filter(fn ($id) => is_string($id) || is_int($id))
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return DB::table('cancellation_requests as cr')
            ->leftJoin('notes as n', 'n.id', '=', 'cr.note_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'cr.requested_by')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'cr.assigned_to')
            ->leftJoin('users as engineer', 'engineer.id', '=', 'cr.engineer_approver_id')
            ->leftJoin('cancellation_categories as cc', 'cc.id', '=', 'cr.category_id')
            ->whereBetween(DB::raw('DATE(COALESCE(cr.submitted_at, cr.created_at))'), [
                $filters['dateFrom'],
                $filters['dateTo'],
            ])
            ->when($visibleRequesterIds !== null, fn (Builder $query) => $query->whereIn('cr.requested_by', $visibleRequesterIds))
            ->when($requesterIds !== [], fn (Builder $query) => $query->whereIn('cr.requested_by', $requesterIds))
            ->when(($filters['status'] ?? '') !== '', fn (Builder $query) => $query->where('cr.status', $filters['status']))
            ->when(($filters['scope'] ?? '') !== '', fn (Builder $query) => $query->where('cr.scope', $filters['scope']))
            ->when(($filters['categoryId'] ?? '') !== '', fn (Builder $query) => $query->where('cr.category_id', (int) $filters['categoryId']))
            ->when($tokens !== [], function (Builder $query) use ($tokens) {
                $requestIds = collect($tokens)
                    ->filter(fn ($value) => ctype_digit($value))
                    ->map(fn ($value) => (int) $value)
                    ->values()
                    ->all();

                $query->where(function (Builder $subquery) use ($tokens, $requestIds) {
                    $subquery->whereIn('n.note', $tokens)
                        ->when($requestIds !== [], fn (Builder $query) => $query->orWhereIn('cr.id', $requestIds));
                });
            });
    }
}
