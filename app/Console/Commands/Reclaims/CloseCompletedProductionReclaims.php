<?php

namespace App\Console\Commands\Reclaims;

use App\Models\Reclaim;
use App\Models\Production;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseCompletedProductionReclaims extends Command
{
    protected $signature = 'sicode:reclaims:close-completed-productions
        {--dry-run : Apenas simula (nao altera nada)}
        {--note_id= : Filtra por uma nota especifica}
        {--service_id= : Filtra por um servico especifico}
        {--production_id= : Filtra por uma production especifica}
        {--chunk=500 : Quantidade de registros por lote}';

    protected $description = 'Finaliza reclaims em aberto quando a production associada ja esta finalizada.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $query = $this->baseQuery();
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nenhum reclaim divergente encontrado.');
            return self::SUCCESS;
        }

        $this->line(sprintf(
            'Reclaims divergentes encontrados: %d | modo: %s',
            $total,
            $dryRun ? 'SIMULACAO' : 'EXECUCAO'
        ));

        if ($dryRun) {
            $this->showDryRunSample($query);
            $this->comment('Nada foi alterado porque o comando rodou em --dry-run.');

            return self::SUCCESS;
        }

        $closed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->with(['Production:id,completed,completed_at'])
            ->orderBy('reclaims.id')
            ->chunkById($chunkSize, function ($reclaims) use (&$closed, $bar) {
                DB::transaction(function () use ($reclaims, &$closed, $bar) {
                    foreach ($reclaims as $reclaim) {
                        $production = $this->matchedProduction($reclaim);
                        $completedAt = $production?->completed_at;

                        if (!$completedAt) {
                            $bar->advance();
                            continue;
                        }

                        $reclaim->forceFill([
                            'production_id' => $reclaim->production_id ?: $production->id,
                            'completed' => true,
                            'completed_at' => $completedAt,
                        ])->save();

                        $closed++;
                        $bar->advance();
                    }
                });
            }, 'id');

        $bar->finish();
        $this->newLine(2);
        $this->info("Reclaims finalizados: {$closed}");

        return self::SUCCESS;
    }

    private function baseQuery()
    {
        return Reclaim::query()
            ->select('reclaims.*')
            ->where('reclaims.completed', false)
            ->where(function ($query) {
                $query
                    ->whereHas('Production', function ($productionQuery) {
                        $productionQuery
                            ->where('productions.completed', true)
                            ->whereNotNull('productions.completed_at');
                    })
                    ->orWhereExists(function ($exists) {
                        $exists
                            ->selectRaw('1')
                            ->from('productions')
                            ->whereColumn('productions.note_id', 'reclaims.note_id')
                            ->whereColumn('productions.service_id', 'reclaims.service_id')
                            ->where('productions.completed', true)
                            ->whereNotNull('productions.completed_at');
                    });
            })
            ->when($this->option('note_id'), fn ($query, $noteId) => $query->where('reclaims.note_id', $noteId))
            ->when($this->option('service_id'), fn ($query, $serviceId) => $query->where('reclaims.service_id', $serviceId))
            ->when($this->option('production_id'), fn ($query, $productionId) => $query->where('reclaims.production_id', $productionId));
    }

    private function showDryRunSample($query): void
    {
        $rows = (clone $query)
            ->with(['Production:id,completed_at'])
            ->orderBy('reclaims.id')
            ->limit(20)
            ->get();

        foreach ($rows as $reclaim) {
            $production = $this->matchedProduction($reclaim);

            $this->line(sprintf(
                ' - reclaim #%d | note_id=%s | service_id=%s | production_id=%s | matched_production_id=%s | completed_at=%s',
                $reclaim->id,
                $reclaim->note_id,
                $reclaim->service_id ?? 'NULL',
                $reclaim->production_id ?? 'NULL',
                $production?->id ?? 'NULL',
                optional($production?->completed_at)->format('Y-m-d H:i:s') ?? 'NULL'
            ));
        }
    }

    private function matchedProduction(Reclaim $reclaim): ?Production
    {
        if (
            $reclaim->Production
            && $reclaim->Production->completed
            && $reclaim->Production->completed_at
        ) {
            return $reclaim->Production;
        }

        return Production::query()
            ->where('note_id', $reclaim->note_id)
            ->where('service_id', $reclaim->service_id)
            ->where('completed', true)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first(['id', 'note_id', 'service_id', 'completed', 'completed_at']);
    }
}
