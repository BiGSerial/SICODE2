<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Edp_depc\BaseOV;
use App\Services\Notes\OvStatusHistoryService;
use Illuminate\Console\Command;

class BackfillOvHistory extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:backfill_ov_history {--from=2026-01-01} {--ov=} {--dry-run}';

    protected $description = 'Popula historic_notes com o histórico de status da BaseOV (idempotente; --ov= para uma OV)';

    public function handle(OvStatusHistoryService $history): int
    {
        $from = $this->option('from') . ' 00:00:00';

        if ($ov = trim((string) $this->option('ov'))) {
            $result = $this->option('dry-run')
                ? ['written' => BaseOV::where('OV', $ov)->where('dtStat', '>=', substr($from, 0, 10))->count(), 'skipped' => 0]
                : $history->syncForOvs([$ov], $from);

            $this->info("OV {$ov}: {$result['written']} evento(s), {$result['skipped']} ignorado(s).");

            return self::SUCCESS;
        }

        $query = fn () => BaseOV::query()
            ->select(array_merge(['id'], OvStatusHistoryService::SOURCE_COLUMNS))
            ->where('dtStat', '>=', substr($from, 0, 10));

        $total = $query()->count();
        $this->info("Eventos na origem desde {$from}: {$total}");

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $bar = $this->createProgressBar($total);
        $bar->start();

        $written = 0;
        $skipped = 0;

        $query()->chunkById(2000, function ($rows) use ($history, $bar, &$written, &$skipped) {
            $noteIds = $history->noteIdsByOv($rows->pluck('OV')->map(fn ($ov) => trim((string) $ov))->unique());
            $result  = $history->upsertFromSource($rows, $noteIds);

            $written += $result['written'];
            $skipped += $result['skipped'];
            $bar->advance($rows->count());
        }, 'id');

        $bar->finish();
        $this->newLine();
        $this->info("Gravados: {$written}. Ignorados (OV sem nota type_note=2 ou dados inválidos): {$skipped}.");

        return self::SUCCESS;
    }
}
