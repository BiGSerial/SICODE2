<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUserAssignments extends Command
{
    use ShowsProgress;

    protected $signature = 'fix:user-assignments
                            {--dry : Executa em modo simulação (não grava no banco)}
                            {--since= : Considera apenas completados com created_at >= (YYYY-MM-DD)}';

    protected $description = 'Sincroniza completed/ended_at entre registros com o mesmo assignable_type + assignable_id';

    public function handle(): int
    {
        $dry   = (bool) $this->option('dry');
        $since = $this->option('since');

        $this->info('> Preparando grupos com completed=1…');

        $groups = DB::table('user_assignments')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->where('completed', 1)
            ->select([
                'assignable_type',
                'assignable_id',
                DB::raw('MAX(ended_at) as max_ended_at'),
            ])
            ->groupBy('assignable_type', 'assignable_id')
            ->orderBy('assignable_type')
            ->orderBy('assignable_id')
            ->cursor();

        $groupedKeys = [];
        foreach ($groups as $g) {
            $groupedKeys[] = [
                'assignable_type' => $g->assignable_type,
                'assignable_id'   => $g->assignable_id,
                'max_ended_at'    => $g->max_ended_at,
            ];
        }

        $progress = $this->createProgressBar(count($groupedKeys));
        $progress->setFormat('<info>%current%/%max%</info> [%bar%] %percent:3s%% | %elapsed:6s%');
        $progress->start();

        $totalUpdated = 0;
        $totalGroups  = count($groupedKeys);

        // NOVO: coletores para o relatório final em --dry
        $affectedAssignableIds = []; // lista simples de assignable_id afetados
        $updatedIdsByGroup = [];     // opcional: ids de linhas por grupo

        foreach ($groupedKeys as $g) {
            $assignableType = $g['assignable_type'];
            $assignableId   = $g['assignable_id'];
            $endedAtRef     = $g['max_ended_at'];

            if (!$endedAtRef) {
                $fallback = DB::table('user_assignments')
                    ->where('assignable_type', $assignableType)
                    ->where('assignable_id', $assignableId)
                    ->where('completed', 1)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('started_at')
                    ->first(['ended_at', 'updated_at', 'started_at']);

                $endedAtRef = $fallback->ended_at
                    ?? $fallback->updated_at
                    ?? $fallback->started_at
                    ?? now();
            }

            $endedAtRef = CarbonImmutable::parse($endedAtRef)->toDateTimeString();

            $needsFix = DB::table('user_assignments')
                ->where('assignable_type', $assignableType)
                ->where('assignable_id', $assignableId)
                ->where(function ($q) use ($endedAtRef) {
                    $q->where('completed', '!=', 1)
                      ->orWhereNull('ended_at')
                      ->orWhere('ended_at', '!=', $endedAtRef);
                })
                ->select('id', 'completed', 'ended_at')
                ->get();

            if ($needsFix->isNotEmpty()) {
                // Marca o grupo como afetado (para o relatório --dry)
                $affectedAssignableIds[$assignableId] = true;

                DB::beginTransaction();
                try {
                    foreach ($needsFix as $row) {
                        $payload = [
                            'completed'  => 1,
                            'ended_at'   => $endedAtRef,
                            'updated_at' => now(),
                        ];

                        if (!$dry) {
                            DB::table('user_assignments')
                                ->where('id', $row->id)
                                ->update($payload);
                        } else {
                            // Em simulação, guardo IDs que seriam atualizados
                            $updatedIdsByGroup[$assignableType][$assignableId][] = $row->id;
                        }

                        $totalUpdated++;
                    }

                    $dry ? DB::rollBack() : DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->error(PHP_EOL . "Erro no grupo {$assignableType}#{$assignableId}: {$e->getMessage()}");
                }
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        $this->info("Grupos processados: {$totalGroups}");
        $this->info("Registros corrigidos: {$totalUpdated}" . ($dry ? ' (simulação)' : ''));

        if ($dry) {
            // ---- Relatório final de simulação (assignable_id) ----
            $ids = array_keys($affectedAssignableIds);          // mantém a ordem encontrada
            $total = count($ids);
            $first = array_slice($ids, 0, 20);                  // 20 primeiros

            $this->line('');
            $this->warn('SIMULAÇÃO: assignable_id afetados (primeiros 20):');
            if (empty($first)) {
                $this->line('(nenhum)');
            } else {
                foreach ($first as $i => $aid) {
                    $this->line(sprintf('%2d) %s', $i + 1, $aid));
                }
                if ($total > 20) {
                    $this->line(sprintf('… (+%d restantes)', $total - 20));
                }
            }

            // (Opcional) imprimir também os IDs de linhas por grupo:
            /*
            $this->line('');
            $this->warn('SIMULAÇÃO: IDs de user_assignments que seriam atualizados por grupo (primeiros 20 grupos):');
            $printed = 0;
            foreach ($updatedIdsByGroup as $type => $byId) {
                foreach ($byId as $aid => $rowIds) {
                    if ($printed >= 20) break 2;
                    $this->line(" - {$type}#{$aid}: " . implode(',', $rowIds));
                    $printed++;
                }
            }
            */
        }

        $this->line('Concluído.');
        return self::SUCCESS;
    }
}
