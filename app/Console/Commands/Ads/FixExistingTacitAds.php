<?php

namespace App\Console\Commands\Ads;

use App\Console\Commands\Concerns\ShowsProgress;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class FixExistingTacitAds extends Command
{
    use ShowsProgress;

    protected $signature = 'ads:fix-existing-tacit
        {--dry : Simula sem gravar alterações}
        {--chunk=500 : Tamanho do lote de processamento}';

    protected $description = 'Corrige ADS tácitas existentes conforme regra de prazo por informed_at do WorkReport (D+6 23:59:59).';

    public function handle(): int
    {
        try {
            $dryRun = (bool) $this->option('dry');
            $chunkSize = max(100, (int) $this->option('chunk'));

            $query = DB::table('adsforms as af')
                ->join('work_reports as wr', 'wr.id', '=', 'af.work_report_id')
                ->where('af.tacit', true)
                ->where('wr.rejected', false)
                ->where('wr.canceled', false)
                ->select([
                    'af.id',
                    'af.tacit_due_at',
                    'af.tacit_delivered_at',
                    'wr.informed_at as informed_at',
                ])
                ->whereNotNull('wr.informed_at')
                ->orderBy('af.id');

            $total = (clone $query)->count();
            $this->info("ADS tácitas encontradas: {$total}");

            if ($total === 0) {
                $this->info('Nada para corrigir.');
                return self::SUCCESS;
            }

            $dueUpdated = 0;
            $tacitCleared = 0;
            $adsDeleted = 0;
            $processed = 0;
            $dryDeleteList = [];

            $bar = $this->createProgressBar($total);
            $bar->start();

            $query->chunkById($chunkSize, function ($rows) use ($dryRun, &$dueUpdated, &$tacitCleared, &$adsDeleted, &$processed, &$dryDeleteList, $bar) {
                foreach ($rows as $row) {
                    $processed++;

                    $newDueAt = Carbon::parse($row->informed_at)
                        ->addDays(6)
                        ->endOfDay();

                    $currentDueAt = $row->tacit_due_at ? Carbon::parse($row->tacit_due_at) : null;
                    $deliveredAt = $row->tacit_delivered_at ? Carbon::parse($row->tacit_delivered_at) : null;

                    $mustUpdateDue = !$currentDueAt || !$currentDueAt->equalTo($newDueAt);
                    $mustClearTacitFields = $deliveredAt && $deliveredAt->lte($newDueAt);
                    $mustDeleteAdsForm = !$deliveredAt && now()->lte($newDueAt);

                    if ($mustUpdateDue && !$mustClearTacitFields && !$mustDeleteAdsForm) {
                        $dueUpdated++;
                    }

                    if ($mustClearTacitFields) {
                        $tacitCleared++;
                    }

                    if ($mustDeleteAdsForm) {
                        $adsDeleted++;
                        if ($dryRun) {
                            $dryDeleteList[] = [
                                'id' => (int) $row->id,
                                'informed_at' => Carbon::parse($row->informed_at)->format('Y-m-d H:i:s'),
                                'calculated_due_at' => $newDueAt->format('Y-m-d H:i:s'),
                            ];
                        }
                    }

                    if (!$dryRun) {
                        if ($mustDeleteAdsForm) {
                            DB::table('adsforms')
                                ->where('id', $row->id)
                                ->delete();
                        } elseif ($mustClearTacitFields) {
                            DB::table('adsforms')
                                ->where('id', $row->id)
                                ->update([
                                    'tacit' => false,
                                    'tacit_due_at' => null,
                                    'tacit_delivered_at' => null,
                                    'updated_at' => now(),
                                ]);
                        } elseif ($mustUpdateDue) {
                            DB::table('adsforms')
                                ->where('id', $row->id)
                                ->update([
                                    'tacit_due_at' => $newDueAt,
                                    'updated_at' => now(),
                                ]);
                        }
                    }

                    $bar->advance();
                }
            }, 'af.id', 'id');

            $bar->finish();
            $this->newLine(2);

            $this->info('Correção concluída.');
            $this->line('Modo: ' . ($dryRun ? 'DRY RUN (sem gravação)' : 'ATUALIZAÇÃO REAL'));
            $this->line("ADS processadas: {$processed}");
            $this->line("Prazo tácito corrigido (D+6 23:59:59): {$dueUpdated}");
            $this->line("Tácito limpo (entrega ate o prazo D+6 23:59:59): {$tacitCleared}");
            $this->line("ADS removida (sem entrega e ainda no prazo): {$adsDeleted}");

            if ($dryRun && !empty($dryDeleteList)) {
                $this->newLine();
                $this->warn('No DRY RUN, as ADS abaixo seriam DELETADAS:');
                foreach ($dryDeleteList as $item) {
                    $this->line(
                        " - adsform_id={$item['id']} | informed_at={$item['informed_at']} | due_at={$item['calculated_due_at']}"
                    );
                }
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
