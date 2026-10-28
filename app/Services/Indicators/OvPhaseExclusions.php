<?php

namespace App\Services\Indicators;

use App\Models\Note;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Expurgo escolhido na tela "Prazo de Projetos" (nada é fixo no código): o que o usuário marcar sai dos indicadores, na consulta.
 *  - rubrics:    rubrica da OV (notes.rubrica) a expurgar;
 *  - prefixes:   OVs cujo número começa com estes prefixos (ex.: 800);
 *  - next_stats: fases cujo evento imediatamente acima tem este status (ex.: 99).
 * O histórico e as fases continuam completos; o expurgo só filtra a leitura.
 */
class OvPhaseExclusions
{
    public const KEYS = ['rubrics', 'prefixes', 'next_stats'];

    private const OPTIONS_TTL = 3600;

    /**
     * @param  array<string,mixed>  $input
     * @return array{rubrics:array<int,string>,prefixes:array<int,string>,next_stats:array<int,int>}
     */
    public static function normalize(array $input): array
    {
        $clean = fn (string $key) => collect($input[$key] ?? [])->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->values();

        return [
            'rubrics'    => $clean('rubrics')->all(),
            'prefixes'   => $clean('prefixes')->all(),
            'next_stats' => $clean('next_stats')->filter(fn ($v) => ctype_digit($v))->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public static function isEmpty(array $exclude): bool
    {
        return $exclude['rubrics'] === [] && $exclude['prefixes'] === [] && $exclude['next_stats'] === [];
    }

    /** Aplica o expurgo numa consulta de `ov_phase_durations as d` (junta `notes as n` quando preciso; `$joinNotes = false` se a consulta já juntou). */
    public static function applyToPhases(Builder $query, array $exclude, bool $joinNotes = true): Builder
    {
        if ($exclude['next_stats'] !== []) {
            $query->where(fn ($q) => $q->whereNull('d.next_stat')->orWhereNotIn('d.next_stat', $exclude['next_stats']));
        }

        if ($exclude['rubrics'] !== [] || $exclude['prefixes'] !== []) {
            if ($joinNotes) {
                $query->join('notes as n', 'n.id', '=', 'd.note_id');
            }

            self::applyToNotes($query, $exclude, 'n.');
        }

        return $query;
    }

    /** Rubrica e prefixo sobre a tabela de notas (alias `n.` ou sem alias). */
    public static function applyToNotes($query, array $exclude, string $prefix = ''): void
    {
        foreach ($exclude['prefixes'] as $p) {
            $query->where($prefix . 'note', 'not like', $p . '%');
        }

        if ($exclude['rubrics'] !== []) {
            $query->where(fn ($q) => $q->whereNull($prefix . 'rubrica')->orWhereNotIn($prefix . 'rubrica', $exclude['rubrics']));
        }
    }

    /**
     * Opções dos multi-selects da tela, com a quantidade de cada uma (cache de 1 hora; notes é grande).
     *
     * @return array{rubrics:array<int,array<string,mixed>>,prefixes:array<int,array<string,mixed>>,next_stats:array<int,array<string,mixed>>}
     */
    public static function options(): array
    {
        return Cache::remember('project_deadline:exclusion_options:v1', self::OPTIONS_TTL, function () {
            $rubrics = Note::query()->where('type_note', 2)->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->selectRaw('rubrica as value, COUNT(*) as count')->groupBy('rubrica')->orderByDesc('count')->get()
                ->map(fn ($r) => ['value' => $r->value, 'count' => (int) $r->count])->all();

            $prefixes = Note::query()->where('type_note', 2)
                ->selectRaw('LEFT(note, 3) as value, COUNT(*) as count')->groupBy(DB::raw('LEFT(note, 3)'))->orderBy('value')->get()
                ->map(fn ($r) => ['value' => $r->value, 'count' => (int) $r->count])->all();

            $stats = DB::table('ov_phase_durations')->whereNotNull('next_stat')
                ->selectRaw('next_stat as value, COUNT(*) as count')->groupBy('next_stat')->orderBy('value')->get();

            $names = $stats->isEmpty() ? collect() : DB::table('historic_notes')->whereIn('num_stat', $stats->pluck('value'))
                ->selectRaw('num_stat, MAX(status) as name')->groupBy('num_stat')->pluck('name', 'num_stat');

            $nextStats = $stats->map(fn ($r) => [
                'value' => (string) $r->value,
                'label' => $r->value . (($names[$r->value] ?? null) ? ' · ' . $names[$r->value] : ''),
                'count' => (int) $r->count,
            ])->all();

            return ['rubrics' => $rubrics, 'prefixes' => $prefixes, 'next_stats' => $nextStats];
        });
    }
}
