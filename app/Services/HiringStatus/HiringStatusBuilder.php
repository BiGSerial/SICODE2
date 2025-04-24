<?php

namespace App\Services\HiringStatus;

use App\Models\Note;
use Illuminate\Support\Collection;

/**
 * Builder que aplica um pipeline de regras para montar o status de contratação.
 */
class HiringStatusBuilder
{
    /** @var RuleInterface[] */
    private array $rules;

    /**
     * @param RuleInterface[]|iterable $rules
     */
    public function __construct(iterable $rules)
    {
        // Converte iterable em array
        $this->rules = is_array($rules) ? $rules : iterator_to_array($rules);
    }

    /**
     * Aplica as regras à nota e retorna o array pronto para upsert.
     *
     * @param Note $note
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function build(Note $note): array
    {
        foreach ($this->rules as $rule) {
            if ($rule->supports($note)) {
                return array_merge([
                    'note_id'   => $note->id,
                    'note'      => $note->note,
                    'dt_status' => $note->dt_status,
                ], $rule->handle($note));
            }
        }

        throw new \RuntimeException("Nenhuma regra se aplicou à nota {$note->id}");
    }

    /**
     * Processa um lote de notas e retorna um array de arrays,
     * pronto para bulk upsert.
     *
     * @param Collection<int, Note> $notes
     * @return array<int, array<string, mixed>>
     */
    public function batchBuild(Collection $notes): array
    {
        return $notes->map(fn (Note $note) => $this->build($note))->all();
    }
}
