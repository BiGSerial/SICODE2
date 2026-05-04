<?php

namespace App\Console\Commands\Ads;

use App\Console\Commands\Concerns\ShowsProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RetrofitTacitDeliveredWithoutFiles extends Command
{
    use ShowsProgress;

    protected $signature = 'ads:retrofit-tacit-delivered-without-files
        {--dry : Simula sem gravar alterações}
        {--chunk=500 : Tamanho do lote de processamento}';

    protected $description = 'Remove tacit_delivered_at de ADS tácitas sem arquivo vinculado.';

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
                ->whereNotNull('af.tacit_delivered_at')
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('adsforms_files as aff')
                        ->whereColumn('aff.adsform_id', 'af.id');
                })
                ->select([
                    'af.id',
                    'af.note_id',
                    'af.work_report_id',
                    'af.tacit_due_at',
                    'af.tacit_delivered_at',
                ])
                ->orderBy('af.id');

            $total = (clone $query)->count();
            $this->info("ADS alvo do retrofit: {$total}");

            if ($total === 0) {
                $this->info('Nenhum registro para ajustar.');
                return self::SUCCESS;
            }

            $processed = 0;
            $updated = 0;
            $dryPreview = [];

            $bar = $this->createProgressBar($total);
            $bar->start();

            $query->chunkById($chunkSize, function ($rows) use ($dryRun, &$processed, &$updated, &$dryPreview, $bar) {
                $ids = [];

                foreach ($rows as $row) {
                    $processed++;
                    $ids[] = (int) $row->id;

                    if ($dryRun && count($dryPreview) < 30) {
                        $dryPreview[] = [
                            'adsform_id' => (int) $row->id,
                            'note_id' => (int) $row->note_id,
                            'work_report_id' => (int) $row->work_report_id,
                            'tacit_due_at' => (string) ($row->tacit_due_at ?? ''),
                            'tacit_delivered_at' => (string) ($row->tacit_delivered_at ?? ''),
                        ];
                    }

                    $bar->advance();
                }

                if ($dryRun || empty($ids)) {
                    $updated += count($ids);
                    return;
                }

                DB::table('adsforms')
                    ->whereIn('id', $ids)
                    ->update([
                        'tacit_delivered_at' => null,
                        'updated_at' => now(),
                    ]);

                $updated += count($ids);
            }, 'af.id', 'id');

            $bar->finish();
            $this->newLine(2);

            $this->info('Retrofit concluído.');
            $this->line('Modo: ' . ($dryRun ? 'DRY RUN (sem gravação)' : 'ATUALIZAÇÃO REAL'));
            $this->line("Processados: {$processed}");
            $this->line("tacit_delivered_at zerado: {$updated}");

            if ($dryRun && !empty($dryPreview)) {
                $this->newLine();
                $this->warn('Amostra de registros que seriam ajustados (máx 30):');
                $this->table(
                    ['adsform_id', 'note_id', 'work_report_id', 'tacit_due_at', 'tacit_delivered_at'],
                    $dryPreview
                );
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
