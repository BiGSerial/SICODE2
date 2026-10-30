<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Edp_depc\BaseOV;
use App\Models\HistoricNote;
use App\Services\Notes\OvStatusHistoryService;
use Illuminate\Console\Command;

class BackfillOvHistory extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:backfill_ov_history {--from=2026-01-01} {--ov=} {--dry-run} {--fresh : Descarta o histórico do recorte (desde --from, ou só a OV de --ov=) e refaz da origem} {--force : Não pede confirmação do --fresh}';

    protected $description = 'Popula historic_notes com o histórico de status da BaseOV (idempotente; --ov= para uma OV; --fresh descarta e refaz)';

    public function handle(OvStatusHistoryService $history): int
    {
        $from = $this->option('from') . ' 00:00:00';

        if ($this->option('fresh') && !$this->discard($history, $from)) {
            return self::SUCCESS;
        }

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

        if ($this->option('fresh')) {
            // Notas que perderam todos os eventos não passam pelo upsert: as fases derivadas precisam ser refeitas do zero.
            $this->call('sicode:rebuild_ov_phases');
        }

        return self::SUCCESS;
    }

    /**
     * Apaga o histórico do recorte antes de refazer. Devolve false quando nada deve prosseguir (dry-run ou recusa).
     */
    private function discard(OvStatusHistoryService $history, string $from): bool
    {
        $query = HistoricNote::query()->where('dt_stats', '>=', $from);
        $scope = "desde {$from}";

        if ($ov = trim((string) $this->option('ov'))) {
            $query->whereIn('note_id', array_values($history->noteIdsByOv([$ov])));
            $scope = "da OV {$ov}, desde {$from}";
        }

        $count = $query->count();
        $this->warn("--fresh: {$count} evento(s) {$scope} serão descartados e refeitos da BaseOV.");

        if ($this->option('dry-run')) {
            return false;
        }

        if (!$this->option('force') && !$this->confirm('Descartar e refazer agora?')) {
            $this->info('Nada foi alterado.');

            return false;
        }

        $query->delete();

        return true;
    }
}
