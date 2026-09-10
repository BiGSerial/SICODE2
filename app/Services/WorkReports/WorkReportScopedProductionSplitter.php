<?php

namespace App\Services\WorkReports;

use App\Models\Production;
use App\Models\WorkReportFlowProduction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkReportScopedProductionSplitter
{
    public function splitRemainingScopes(Production $production, string $stage, array $closingScopes): ?Production
    {
        if ((bool) $production->partial) {
            return null;
        }

        $closingScopes = collect($closingScopes)
            ->map(fn ($scope) => (string) $scope)
            ->filter()
            ->unique()
            ->values();

        $currentLinks = $this->currentLinks($production, $stage);

        if ($currentLinks->count() <= 1 || $closingScopes->isEmpty()) {
            return null;
        }

        $remainingLinks = $currentLinks
            ->reject(fn (WorkReportFlowProduction $link) => $closingScopes->contains((string) $link->final_scope))
            ->values();

        if ($remainingLinks->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($production, $stage, $remainingLinks): Production {
            $mirror = $production->replicate([
                'completed',
                'completed_at',
                'confirmed',
                'confirmed_at',
                'returned',
                'postes_u',
                'postes_l',
                'created_at',
                'updated_at',
            ]);

            $mirror->forceFill([
                'status' => 2,
                'completed' => false,
                'completed_at' => null,
                'confirmed' => false,
                'confirmed_at' => null,
                'returned' => false,
                'priority' => false,
                'postes_u' => null,
                'postes_l' => null,
                'att_at' => now(),
            ]);
            $mirror->save();

            foreach ($remainingLinks as $link) {
                $link->forceFill([
                    'is_current' => false,
                    'reversed_at' => now(),
                    'reversed_by' => auth()->id(),
                    'reverse_reason' => 'split_remaining_scope',
                ])->save();

                WorkReportFlowProduction::query()->create([
                    'work_report_id' => $link->work_report_id,
                    'production_id' => $mirror->id,
                    'stage' => $stage,
                    'final_scope' => $link->final_scope,
                    'is_current' => true,
                    'linked_at' => now(),
                    'linked_by' => auth()->id(),
                    'source' => 'split_remaining_scope',
                    'metadata' => [
                        'origin_production_id' => $production->id,
                        'note_id' => $production->note_id,
                        'service_id' => $production->service_id,
                    ],
                ]);
            }

            return $mirror;
        });
    }

    public function currentScopeOptions(Production $production, string $stage): array
    {
        return $this->currentLinks($production, $stage)
            ->map(fn (WorkReportFlowProduction $link) => [
                'scope' => (string) $link->final_scope,
                'label' => $production->workReportFinalScopeLabel((string) $link->final_scope),
                'class' => $production->workReportFinalScopeBadgeClass((string) $link->final_scope),
            ])
            ->unique('scope')
            ->values()
            ->all();
    }

    private function currentLinks(Production $production, string $stage): Collection
    {
        return WorkReportFlowProduction::query()
            ->where('production_id', $production->id)
            ->where('stage', $stage)
            ->where('is_current', true)
            ->orderByRaw("CASE final_scope WHEN 'network' THEN 1 WHEN 'connection' THEN 2 ELSE 3 END")
            ->get();
    }
}
