<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Production;
use App\Models\ProjectReviewDraft;
use App\Models\ProjectReviewFinding;
use App\Models\ProjectReviewMessage;
use Illuminate\Console\Command;

class RetrofitProjectReviewCompleted extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:retrofit_project_review_completed
        {--dry-run : Apenas simula sem gravar alterações}
        {--chunk=500 : Tamanho do chunk para processamento}';

    protected $description = 'Retrofit das produções que passaram por Análise de Projeto para corrigir completed/completed_at.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(50, (int) $this->option('chunk'));

        $baseQuery = Production::query()
            ->whereHas('ProjectReviewCycles')
            ->with(['ProjectReviewCycles' => function ($q) {
                $q->latest('round_number');
            }]);

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            $this->info('Nenhuma produção em análise para retrofit.');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        $bar = $this->createProgressBar($total);
        $bar->start();

        $baseQuery->orderBy('id')->chunk($chunkSize, function ($productions) use ($dryRun, &$updated, &$skipped, $bar) {
            foreach ($productions as $production) {
                $cycle = $production->ProjectReviewCycles->first();
                if (!$cycle) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $completedAt = $this->resolveCompletedAt($production->id, (int) $cycle->id, (string) $production->user_id, $cycle->created_at);
                if (!$completedAt) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    $production->update([
                        'completed' => true,
                        'completed_at' => $completedAt,
                    ]);
                }

                $updated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Processo finalizado. Atualizadas: {$updated}. Ignoradas: {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveCompletedAt(int $productionId, int $cycleId, string $productionUserId, $fallbackCycleCreatedAt)
    {
        $candidateDates = [];

        $firstDraftAt = ProjectReviewDraft::query()
            ->where('production_id', $productionId)
            ->where('cycle_id', $cycleId)
            ->where('user_id', '!=', $productionUserId)
            ->min('created_at');
        if ($firstDraftAt) {
            $candidateDates[] = $firstDraftAt;
        }

        $firstFindingAt = ProjectReviewFinding::query()
            ->where('cycle_id', $cycleId)
            ->min('created_at');
        if ($firstFindingAt) {
            $candidateDates[] = $firstFindingAt;
        }

        $firstAnalystMessageAt = ProjectReviewMessage::query()
            ->where('cycle_id', $cycleId)
            ->where('user_id', '!=', $productionUserId)
            ->min('created_at');
        if ($firstAnalystMessageAt) {
            $candidateDates[] = $firstAnalystMessageAt;
        }

        if (count($candidateDates)) {
            sort($candidateDates);
            return $candidateDates[0];
        }

        return $fallbackCycleCreatedAt;
    }
}
