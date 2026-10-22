<?php

namespace App\Console\Commands\Tools;

use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Storage};

/**
 * Desfaz uma execução `sicode:retrofill-legacy-work-report-d5 --apply` a partir do diário (`*-journal.jsonl`).
 *
 * Seguro por construção:
 *  - dry-run por padrão;
 *  - só restaura colunas que ainda têm o valor que o retrofill gravou (alteração posterior = conflito, a linha é preservada);
 *  - só apaga vínculos/pivots/eventos criados pelo retrofill (por id) e que continuam intactos;
 *  - tudo numa única transação e o diário nunca é executado duas vezes.
 */
class RollbackLegacyWorkReportD5 extends Command
{
    protected $signature = 'sicode:rollback-legacy-work-report-d5
                            {journal : Caminho do diário (disco local), ex.: retrofill/legacy-d5-20261021-101500-journal.jsonl}
                            {--apply : Executa o rollback (padrão = dry-run)}
                            {--no-refresh : Não recalcula o status materializado dos informes afetados}';

    protected $description = 'Rollback seguro de uma execução do retrofill legado de informe/parcial/D5';

    private array $stats = [];

    private array $conflicts = [];

    /** @var array<int, true> */
    private array $reports = [];

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $path = (string) $this->argument('journal');
        $done = $path . '.rolledback';

        if (!$disk->exists($path)) {
            $this->error("Diário não encontrado: {$path}");

            return self::FAILURE;
        }

        if ($disk->exists($done)) {
            $this->error('Este diário já foi revertido (' . trim($disk->get($done)) . ').');

            return self::FAILURE;
        }

        $entries = collect(preg_split('/\R/', trim($disk->get($path))))->filter()->map(fn ($line) => json_decode($line, true));

        if (!isset($entries->first()['meta']['version'])) {
            $this->error('Diário inválido (sem cabeçalho meta).');

            return self::FAILURE;
        }

        $entries = $entries->skip(1)->reverse()->values();
        $apply   = (bool) $this->option('apply');
        $bar     = $this->output->createProgressBar($entries->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  decorrido %elapsed:6s%  restante ~%remaining:-6s%');
        $bar->start();

        $work = function () use ($entries, $apply, $bar): void {
            foreach ($entries as $entry) {
                $this->revertNote($entry, $apply);
                $bar->advance();
            }
        };

        $apply ? DB::transaction($work) : $work();

        $bar->finish();
        $this->newLine(2);

        if ($apply) {
            $disk->put($done, now()->toDateTimeString());

            if (!$this->option('no-refresh')) {
                $refresher = app(WorkReportCurrentStatusRefresher::class);

                $this->line('Atualizando o status de ' . count($this->reports) . ' informe(s)...');

                foreach ($this->output->progressIterate(array_keys($this->reports)) as $reportId) {
                    if (DB::table('work_reports')->where('id', $reportId)->exists()) {
                        $refresher->refresh($reportId);
                    }
                }
            }
        }

        $this->line('Modo: ' . ($apply ? 'APPLY' : 'DRY-RUN'));
        ksort($this->stats);

        foreach ($this->stats as $key => $value) {
            $this->line(str_pad($key, 40) . $value);
        }

        if ($this->conflicts) {
            $report = preg_replace('/\.jsonl$/', '', $path) . '-rollback-conflitos.csv';
            $lines  = array_merge(['nota;tabela;id;motivo'], array_map(fn ($c) => implode(';', $c), $this->conflicts));
            $disk->put($report, implode("\n", $lines));
            $this->warn(count($this->conflicts) . ' conflito(s) preservados (não revertidos): ' . $disk->path($report));
        }

        return self::SUCCESS;
    }

    private function revertNote(array $entry, bool $apply): void
    {
        $note = (int) $entry['note_id'];

        // 1) Eventos de timeline criados.
        foreach ($entry['created']['timeline_events'] ?? [] as $id) {
            $this->bump('eventos_timeline_removidos');
            $apply && DB::table('timeline_events')->where('id', $id)->delete();
        }

        // 2) Vínculos de parcial (pivot) criados.
        foreach ($entry['created']['productionables'] ?? [] as $id) {
            $this->bump('vinculos_parcial_removidos');
            $apply && DB::table('productionables')->where('id', $id)->delete();
        }

        // 3) Vínculos de informe criados: só se continuam como o retrofill os deixou.
        foreach ($entry['created']['work_report_flow_productions'] ?? [] as $id) {
            $row = DB::table('work_report_flow_productions')->find($id);

            if (!$row) {
                continue;
            }

            if ($row->source !== 'retrofill_legacy' || $row->reversed_at !== null) {
                $this->conflict($note, 'work_report_flow_productions', $id, 'vinculo_alterado_apos_retrofill');

                continue;
            }

            if ($row->stage !== 'publication') {
                $this->reports[(int) $row->work_report_id] = true;
            }

            $this->bump('vinculos_informe_removidos');
            $apply && DB::table('work_report_flow_productions')->where('id', $id)->delete();
        }

        // 4) `is_current` de vínculos que já existiam.
        foreach ($entry['current'] ?? [] as $id => $state) {
            $row = DB::table('work_report_flow_productions')->find($id);

            if (!$row) {
                continue;
            }

            if ((bool) $row->is_current !== (bool) ($state['after'] ?? null)) {
                $this->conflict($note, 'work_report_flow_productions', $id, 'is_current_alterado_apos_retrofill');

                continue;
            }

            $this->reports[(int) $row->work_report_id] = true;
            $this->bump('is_current_restaurados');
            $apply && DB::table('work_report_flow_productions')->where('id', $id)->update(['is_current' => (bool) $state['before'], 'updated_at' => now()]);
        }

        // 5) Colunas alteradas em partials / five_notes: só se ainda valem o que o retrofill gravou.
        foreach ($entry['rows'] ?? [] as $table => $rows) {
            foreach ($rows as $id => $data) {
                $row = DB::table($table)->find($id);

                if (!$row) {
                    continue;
                }

                $restore = [];
                $changed = false;

                foreach ($data['changes'] as $column => [$before, $after]) {
                    $current = $row->{$column} ?? null;

                    if (is_null($current) !== is_null($after) || (string) $current !== (string) $after) {
                        $changed = true;
                        $this->conflict($note, $table, $id, "coluna_{$column}_alterada_apos_retrofill");

                        break;
                    }

                    $restore[$column] = $before;
                }

                if ($changed) {
                    continue;
                }

                if (isset($row->work_report_id)) {
                    $this->reports[(int) $row->work_report_id] = true;
                }

                $this->bump("{$table}_restauradas");
                $apply && DB::table($table)->where('id', $id)->update($restore + ['updated_at' => now()]);
            }
        }
    }

    private function conflict(int $note, string $table, int|string $id, string $reason): void
    {
        $this->bump('conflitos');
        $this->conflicts[] = [$note, $table, $id, $reason];
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }
}
