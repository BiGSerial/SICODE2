<?php

namespace App\Console\Commands\Reclaims;

use App\Models\Production;
use App\Models\Reclaim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateActive extends Command
{
    protected $signature = 'sicode:reclaims:dedupe-active
        {--dry-run : Apenas simula (nao remove nada)}
        {--note_id= : Filtra por uma nota especifica}
        {--service_id= : Filtra por um servico especifico}
        {--keep=oldest : Criterio de retencao (oldest|newest)}
        {--no-production-delete : Nao remove production associada}';

    protected $description = 'Remove reclaims ativos duplicados (mesma nota + servico), com opcao de limpar production associada.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keep = strtolower((string) $this->option('keep'));
        $deleteProduction = !$this->option('no-production-delete');

        if (!in_array($keep, ['oldest', 'newest'], true)) {
            $this->error("Opcao --keep invalida. Use 'oldest' ou 'newest'.");
            return self::INVALID;
        }

        $groupQuery = Reclaim::query()
            ->where('completed', false)
            ->when($this->option('note_id'), fn ($q, $noteId) => $q->where('note_id', $noteId))
            ->when($this->option('service_id'), fn ($q, $serviceId) => $q->where('service_id', $serviceId))
            ->select('note_id', 'service_id', DB::raw('COUNT(*) as total'))
            ->groupBy('note_id', 'service_id')
            ->having('total', '>', 1);

        $groups = $groupQuery->get();

        if ($groups->isEmpty()) {
            $this->info('Nenhum reclaim ativo duplicado encontrado.');
            return self::SUCCESS;
        }

        $this->line(sprintf(
            'Grupos duplicados encontrados: %d | modo: %s',
            $groups->count(),
            $dryRun ? 'SIMULACAO' : 'EXECUCAO'
        ));

        $deletedReclaims = 0;
        $deletedProductions = 0;
        $skippedProductions = 0;

        foreach ($groups as $group) {
            $reclaims = Reclaim::query()
                ->where('completed', false)
                ->where('note_id', $group->note_id)
                ->when(
                    is_null($group->service_id),
                    fn ($q) => $q->whereNull('service_id'),
                    fn ($q) => $q->where('service_id', $group->service_id)
                )
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            if ($reclaims->count() <= 1) {
                continue;
            }

            $keeper = $keep === 'newest' ? $reclaims->last() : $reclaims->first();
            $duplicates = $reclaims->where('id', '!=', $keeper->id)->values();

            $this->line(sprintf(
                'Nota %s | Servico %s | manter reclaim #%d | remover %d',
                $group->note_id,
                $group->service_id ?? 'NULL',
                $keeper->id,
                $duplicates->count()
            ));

            foreach ($duplicates as $duplicate) {
                if ($dryRun) {
                    $this->line(" - [dry-run] reclaim #{$duplicate->id} (production_id={$duplicate->production_id})");
                    continue;
                }

                DB::transaction(function () use (
                    $duplicate,
                    $group,
                    $deleteProduction,
                    &$deletedReclaims,
                    &$deletedProductions,
                    &$skippedProductions
                ) {
                    $productionId = $duplicate->production_id;

                    // Limpa relacionamentos sem FK/cascade garantidos.
                    $duplicate->Waiting()->delete();
                    $duplicate->Comments()->detach();
                    $duplicate->Viabilities()->detach();
                    $duplicate->Approvals()->detach();
                    $duplicate->Externals()->detach();
                    $duplicate->delete();
                    $deletedReclaims++;

                    if (!$deleteProduction || !$productionId) {
                        return;
                    }

                    $stillReferenced = Reclaim::query()
                        ->where('production_id', $productionId)
                        ->exists();

                    if ($stillReferenced) {
                        $skippedProductions++;
                        return;
                    }

                    $production = Production::query()->find($productionId);

                    if (!$production) {
                        return;
                    }

                    $sameNote = (int) $production->note_id === (int) $group->note_id;
                    $sameService = is_null($group->service_id)
                        ? is_null($production->service_id)
                        : (string) $production->service_id === (string) $group->service_id;

                    // So remove production quando estiver no mesmo contexto e ainda ativa.
                    if (!$sameNote || !$sameService || (bool) $production->completed) {
                        $skippedProductions++;
                        return;
                    }

                    $production->delete();
                    $deletedProductions++;
                });
            }
        }

        $this->newLine();
        $this->info("Reclaims removidos: {$deletedReclaims}");
        $this->info("Productions removidas: {$deletedProductions}");
        $this->line("Productions preservadas (seguranca): {$skippedProductions}");

        if ($dryRun) {
            $this->comment('Nada foi removido porque o comando rodou em --dry-run.');
        }

        return self::SUCCESS;
    }
}

