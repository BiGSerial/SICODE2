<?php

namespace App\Services\WorkReports;

use App\Models\WorkReport;

class WorkReportCurrentStatusRefresher
{
    public function __construct(private readonly WorkReportStatusResolver $resolver)
    {
    }

    public function refresh(WorkReport|int $workReport): ?array
    {
        $workReport = $workReport instanceof WorkReport
            ? $workReport
            : WorkReport::query()->find($workReport);

        if (!$workReport) {
            return null;
        }

        $workReport->loadMissing($this->relations());

        $status = $this->resolver->resolve($workReport);

        $workReport->forceFill([
            'current_status_key' => $status['key'],
            'current_status_label' => $status['label'],
            'current_status_class' => $status['class'],
            'current_status_updated_at' => now(),
        ])->saveQuietly();

        return $status;
    }

    public function refreshByProductionId(int $productionId): int
    {
        $ids = WorkReport::query()
            ->whereHas('FlowProductions', fn ($query) => $query->where('production_id', $productionId))
            ->pluck('id');

        foreach ($ids as $id) {
            $this->refresh((int) $id);
        }

        return $ids->count();
    }

    public function relations(): array
    {
        return [
            'Adsform',
            'Orders.Operations',
            'FlowProductions.Production.Service:id,uuid,service',
            'FlowProductions.Production.User:id,name,email',
            'FlowProductions.Production.Company:id,name',
            'Note.FiveNote.productions.Service:id,uuid,service',
        ];
    }
}
