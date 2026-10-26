<?php

namespace App\Console\Commands\Ads;

use App\Services\Ads\AdsDeadlinePolicy;
use Illuminate\Console\Command;

class SyncAdsDueAt extends Command
{
    protected $signature = 'ads:sync-due-at
        {--state= : Restringe aos informes de empresas deste UF}
        {--chunk=500 : Tamanho do lote}';

    protected $description = 'Recalcula work_reports.ads_due_at dos informes sem ADS tácita (backfill e mudança de regra/feriado).';

    public function handle(AdsDeadlinePolicy $policy): int
    {
        $total = $policy->syncPending($this->option('state') ?: null, max(100, (int) $this->option('chunk')));

        $this->info("Informes processados: {$total}");

        return self::SUCCESS;
    }
}
