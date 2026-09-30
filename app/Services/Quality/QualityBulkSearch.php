<?php

namespace App\Services\Quality;

use App\Models\{Note, QualityProcess, User};
use Illuminate\Support\Collection;

/**
 * "Buscar em massa" (mesmo padrão das demais atividades): o usuário cola várias Notas (linha, espaço, vírgula ou
 * ponto e vírgula) e a lista é filtrada por elas. A lista fica na sessão (não cabe em URL) por escopo
 * (`pool`, `n1.despachar`, ...), até ser limpa.
 */
class QualityBulkSearch
{
    public const LIMIT = 2000;

    /** @return array<int, string> */
    public function parse(?string $text): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $text))->map(fn ($term) => trim((string) $term))->filter()->unique()->take(self::LIMIT)->values()->all();
    }

    public function key(string $scope): string
    {
        return 'quality.bulk.' . $scope;
    }

    /** @return array<int, string> */
    public function get(string $scope): array
    {
        return (array) session($this->key($scope), []);
    }

    public function put(string $scope, array $notes): void
    {
        $notes ? session([$this->key($scope) => array_values($notes)]) : session()->forget($this->key($scope));
    }

    /**
     * Explica por que cada Nota colada NÃO apareceu na lista atual.
     *
     * @param  array<int, string>  $notes
     * @param  Collection<int, string>  $foundNotes  Notas (texto) presentes no resultado
     * @return array<int, array{note:string, reason:string}>
     */
    public function missingReport(array $notes, Collection $foundNotes, ?User $user = null, bool $pool = false): array
    {
        $found   = $foundNotes->map(fn ($n) => (string) $n)->flip();
        $missing = collect($notes)->reject(fn ($note) => $found->has($note))->values();

        if ($missing->isEmpty()) {
            return [];
        }

        $known = Note::query()->whereIn('note', $missing->all())->orWhereIn('numPedido', $missing->all())->get(['id', 'note', 'numPedido']);
        // (sem flatMap/collapse: chaves numéricas seriam renumeradas por array_merge)
        $byKey = [];

        foreach ($known as $n) {
            $byKey[(string) $n->note]      = $n;
            $byKey[(string) $n->numPedido] = $n;
        }

        $processes  = QualityProcess::query()->whereIn('note_id', $known->pluck('id'))->when($user, fn ($q) => $q->visibleTo($user))->get()->keyBy('note_id');
        $anyProcess = QualityProcess::query()->whereIn('note_id', $known->pluck('id'))->pluck('note_id')->flip();

        return $missing->map(function ($term) use ($byKey, $processes, $anyProcess, $pool, $user) {
            $note = $byKey[$term] ?? null;

            if (!$note) {
                return ['note' => $term, 'reason' => 'Nota não encontrada'];
            }

            if ($pool) {
                return ['note' => $term, 'reason' => $anyProcess->has($note->id) ? 'Já está na Qualidade' : 'Fora dos critérios do Pool'];
            }

            $process = $processes->get($note->id);

            return ['note' => $term, 'reason' => $process ? 'Está em outra etapa: ' . $process->state->labelFor((bool) $user?->can('quality.n2')) : 'Não está nas suas obras'];
        })->all();
    }
}
