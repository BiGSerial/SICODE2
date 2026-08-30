<?php

namespace App\Services\WorkReports;

use App\Models\Note;
use App\Models\WorkReport;
use App\Support\SicodeRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkReportFinalScopeOptions
{
    public function forNote(Note $note, bool $publicationOnly = false): array
    {
        if (!SicodeRules::workReportSplitsBtzeroEpFinalFlows() || (int) $note->type_note !== 1) {
            return [[
                'scope' => WorkReportFinalScopeResolver::SCOPE_GENERAL,
                'label' => 'Geral',
                'publication_required' => true,
            ]];
        }

        $workReport = $this->currentWorkReport($note);
        if (!$workReport) {
            return [];
        }

        $materialized = $this->materializedScopes((int) $workReport->id, $publicationOnly);
        if (!empty($materialized)) {
            return $materialized;
        }

        $resolved = app(WorkReportFinalScopeResolver::class)
            ->resolve((int) $note->type_note, $this->ordersFor($workReport));

        return collect($resolved)
            ->filter(function (array $item) use ($publicationOnly) {
                return !$publicationOnly
                    || app(WorkReportFinalScopeResolver::class)->publicationRequired($item['scope']);
            })
            ->map(fn (array $item) => [
                'scope' => $item['scope'],
                'label' => $this->label($item['scope']),
                'publication_required' => app(WorkReportFinalScopeResolver::class)->publicationRequired($item['scope']),
            ])
            ->values()
            ->all();
    }

    public function validScopesForNote(Note $note, array $requestedScopes, bool $publicationOnly = false): array
    {
        $available = collect($this->forNote($note, $publicationOnly))->pluck('scope')->all();

        return collect($requestedScopes)
            ->map(fn ($scope) => (string) $scope)
            ->filter(fn (string $scope) => in_array($scope, $available, true))
            ->unique()
            ->values()
            ->all();
    }

    public function label(string $scope): string
    {
        return match ($scope) {
            WorkReportFinalScopeResolver::SCOPE_NETWORK => 'Rede',
            WorkReportFinalScopeResolver::SCOPE_CONNECTION => 'Ligacao',
            default => 'Geral',
        };
    }

    private function currentWorkReport(Note $note): ?WorkReport
    {
        if ($note->relationLoaded('WorkForm') && $note->WorkForm) {
            return $note->WorkForm;
        }

        return WorkReport::query()
            ->where('note_id', $note->id)
            ->where('canceled', false)
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id')
            ->first();
    }

    private function materializedScopes(int $workReportId, bool $publicationOnly): array
    {
        return DB::table('note_inform_flows')
            ->where('flow_type', 'final')
            ->where('work_report_id', $workReportId)
            ->where('active', true)
            ->when($publicationOnly, fn ($query) => $query->where('publication_required', true))
            ->orderByRaw("CASE final_scope WHEN 'network' THEN 1 WHEN 'connection' THEN 2 ELSE 3 END")
            ->get(['final_scope', 'publication_required'])
            ->map(fn ($row) => [
                'scope' => $row->final_scope,
                'label' => $this->label((string) $row->final_scope),
                'publication_required' => (bool) $row->publication_required,
            ])
            ->all();
    }

    private function ordersFor(WorkReport $workReport): Collection
    {
        return DB::table('order_work_report as owr')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where('owr.work_report_id', $workReport->id)
            ->select([
                'o.id as order_id',
                'o.ordem as order_number',
            ])
            ->get();
    }
}
