<?php

namespace App\Services\Notes;

use App\Models\Edp_depc\BaseOV;
use App\Models\{HistoricNote, Note};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Histórico de status da base OV (type_note = 2) em historic_notes.
 * Chave do upsert: note_id + id_num + num_stat.
 */
class OvStatusHistoryService
{
    /** Piso do histórico: não precisamos de dados anteriores a 2026. */
    public const FLOOR = '2026-01-01 00:00:00';

    private const UPSERT_CHUNK = 1000;

    public const SOURCE_COLUMNS = [
        'OV', 'idNum', 'numStat', 'dtStat', 'dhStat',
        'considerarPrazo', 'PzE', 'status', 'transicaoUsuario',
    ];

    /**
     * Lê da origem os eventos das OVs, resolve o note_id e grava em lote.
     *
     * @param  iterable<string>  $ovs
     * @return array{written:int, skipped:int}
     */
    public function syncForOvs(iterable $ovs, ?string $from = null): array
    {
        $ovs = collect($ovs)
            ->map(fn ($ov) => trim((string) $ov))
            ->filter()
            ->unique()
            ->values();

        if ($ovs->isEmpty()) {
            return ['written' => 0, 'skipped' => 0];
        }

        $from ??= self::FLOOR;

        $rows = BaseOV::query()
            ->select(self::SOURCE_COLUMNS)
            ->whereIn('OV', $ovs->all())
            ->where('dtStat', '>=', substr($from, 0, 10))
            ->get();

        return $this->upsertFromSource($rows, $this->noteIdsByOv($ovs));
    }

    /**
     * @param  iterable<object>  $rows  linhas da BaseOV
     * @param  array<string,int>  $noteIdByOv
     * @return array{written:int, skipped:int}
     */
    public function upsertFromSource(iterable $rows, array $noteIdByOv): array
    {
        $now     = now();
        $batch   = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $noteId  = $noteIdByOv[trim((string) $row->OV)] ?? null;
            $dtStats = $this->resolveDtStats($row);

            if (!$noteId || $dtStats === null || !is_numeric($row->idNum) || !is_numeric($row->numStat)) {
                $skipped++;

                continue;
            }

            $key = $noteId . '|' . (int) $row->idNum . '|' . (int) $row->numStat;

            $batch[$key] = [
                'note_id'           => $noteId,
                'id_num'            => (int) $row->idNum,
                'num_stat'          => (int) $row->numStat,
                'dt_stats'          => $dtStats,
                'considerar_prazo'  => $this->nullIfBlank($row->considerarPrazo),
                'pze'               => is_numeric($row->PzE) ? (int) $row->PzE : null,
                'status'            => $this->nullIfBlank($row->status),
                'transicao_usuario' => $this->nullIfBlank($row->transicaoUsuario),
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        foreach (array_chunk(array_values($batch), self::UPSERT_CHUNK) as $chunk) {
            // updated_at fora da lista: linha sem mudança não é regravada.
            HistoricNote::upsert(
                $chunk,
                ['note_id', 'id_num', 'num_stat'],
                ['dt_stats', 'considerar_prazo', 'pze', 'status', 'transicao_usuario'],
            );
        }

        // Fases 27/28 derivam do histórico; só as notas deste lote precisam ser recalculadas.
        app(OvPhaseDurationService::class)->rebuildForNotes(array_unique(array_column($batch, 'note_id')));

        return ['written' => count($batch), 'skipped' => $skipped];
    }

    /**
     * @param  Collection<int,string>|iterable<string>  $ovs
     * @return array<string,int>
     */
    public function noteIdsByOv(iterable $ovs): array
    {
        return Note::query()
            ->where('type_note', 2)
            ->whereIn('note', collect($ovs)->all())
            ->pluck('id', 'note')
            ->all();
    }

    /**
     * Data do evento = data de dtStat + hora de dhStat (a concatenação definida pelo produto). Se faltar um dos dois, vale o que houver.
     * Só a parte de hora de dhStat é usada: a data dele pode ser a do último status da OV, não a do evento.
     */
    private function resolveDtStats(object $row): ?string
    {
        $dt = trim((string) ($row->dtStat ?? ''));
        $dh = trim((string) ($row->dhStat ?? ''));

        if ($dt === '' && $dh === '') {
            return null;
        }

        try {
            $date = $dt !== '' ? Carbon::parse($dt) : null;
            $time = $dh !== '' ? Carbon::parse($dh) : null;

            if ($date && $time) {
                return $date->format('Y-m-d') . ' ' . $time->format('H:i:s');
            }

            return ($date ?? $time)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function nullIfBlank(mixed $value): mixed
    {
        return $value === null || (is_string($value) && trim($value) === '') ? null : $value;
    }
}
