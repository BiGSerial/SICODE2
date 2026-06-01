<?php

namespace App\Console\Commands\Legal;

use App\Models\Legal\LegalDemand;
use App\Services\Legal\LegalDemandSubdemandMetricsService;
use Illuminate\Console\Command;

class RefreshSubdemandSlaCommand extends Command
{
    protected $signature = 'legal:subdemands:refresh-sla {--demand_id=} {--limit=500}';
    protected $description = 'Recalcula SLA/criticidade agregados das demandas com subdemanda.';

    public function handle(LegalDemandSubdemandMetricsService $metrics): int
    {
        $demandId = $this->option('demand_id');
        $limit = max(1, (int) $this->option('limit'));

        $query = LegalDemand::query()->whereHas('subdemands');
        if ($demandId) {
            $query->where('id', (int) $demandId);
        }

        $total = 0;
        $query->orderBy('id')->chunkById($limit, function ($demands) use ($metrics, &$total) {
            foreach ($demands as $demand) {
                $metrics->refreshForDemand($demand);
                $total++;
            }
        });

        $this->info("Demandas atualizadas: {$total}");
        return self::SUCCESS;
    }
}

