<?php

namespace App\Console\Commands\Tools;

use App\Models\WorkReport;
use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use App\Services\WorkReports\WorkReportStatusResolver;
use Illuminate\Console\Command;

class RefreshWorkReportCurrentStatuses extends Command
{
    protected $signature = 'sicode:refresh-work-report-statuses
        {--chunk=500 : Quantidade de informes por lote}
        {--only-missing : Atualiza apenas informes sem status materializado}';

    protected $description = 'Atualiza o status atual materializado dos informes de obra finais.';

    public function handle(WorkReportCurrentStatusRefresher $refresher, WorkReportStatusResolver $resolver): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $query = WorkReport::query()->orderBy('id');

        if ($this->option('only-missing')) {
            $query->whereNull('current_status_key');
        }

        $total = (clone $query)->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        $query
            ->with($refresher->relations())
            ->chunkById($chunk, function ($workReports) use ($resolver, $bar, &$updated) {
                foreach ($workReports as $workReport) {
                    $status = $resolver->resolve($workReport);

                    $workReport->forceFill([
                        'current_status_key' => $status['key'],
                        'current_status_label' => $status['label'],
                        'current_status_class' => $status['class'],
                        'current_status_updated_at' => now(),
                    ])->saveQuietly();

                    $updated++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Status de informes atualizados: {$updated}");

        return self::SUCCESS;
    }
}
