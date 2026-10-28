<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\{HistoricNote, OvPhaseDuration};
use App\Services\Notes\OvPhaseDurationService;
use Illuminate\Console\Command;

class RebuildOvPhases extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:rebuild_ov_phases {--ov= : OV para conferir (mostra eventos e fases)} {--dry-run}';

    protected $description = 'Recalcula ov_phase_durations (fases 27/28) do zero a partir do historic_notes';

    public function handle(OvPhaseDurationService $phases): int
    {
        if ($ov = trim((string) $this->option('ov'))) {
            return $this->inspect($phases, $ov);
        }

        $noteIds = HistoricNote::query()->select('note_id')->distinct()->orderBy('note_id')->pluck('note_id');
        $this->info("Notas com histórico: {$noteIds->count()}");

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        // Tabela derivada: apaga e recria, para sumir qualquer linha calculada por regra antiga.
        OvPhaseDuration::query()->delete();

        $bar = $this->createProgressBar($noteIds->count());
        $bar->start();

        foreach ($noteIds->chunk(1000) as $chunk) {
            $phases->rebuildForNotes($chunk->all());
            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine();
        $this->info('Fases recalculadas: ' . OvPhaseDuration::count());

        return self::SUCCESS;
    }

    /** Mostra os eventos da OV e a fase calculada, para conferir contra a origem. */
    private function inspect(OvPhaseDurationService $phases, string $ov): int
    {
        $note = \App\Models\Note::where('note', $ov)->where('type_note', 2)->first();

        if (!$note) {
            $this->error("OV {$ov} não encontrada em notes (type_note = 2).");

            return self::FAILURE;
        }

        $this->table(
            ['id_num', 'status', 'dt_stats', 'usuário'],
            HistoricNote::where('note_id', $note->id)->orderBy('id_num')->get()->map(fn ($h) => [$h->id_num, $h->num_stat, $h->dt_stats?->format('d/m/Y H:i:s'), $h->transicao_usuario])->all(),
        );

        if (!$this->option('dry-run')) {
            $phases->rebuildForNotes([$note->id]);
        }

        $this->table(
            ['fase', 'início', 'término', 'dias'],
            OvPhaseDuration::where('note_id', $note->id)->orderBy('phase')->get()->map(fn ($p) => [$p->phase, $p->started_at->format('d/m/Y H:i:s'), $p->ended_at->format('d/m/Y H:i:s'), $p->days])->all(),
        );

        return self::SUCCESS;
    }
}
