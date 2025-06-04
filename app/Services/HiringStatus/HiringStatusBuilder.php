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
        // valores iniciais fixos (ajuste aqui as chaves que sempre deverão existir)
        $result = [
            'note_id'    => $note->id,
            'note'       => $note->note,
            'dt_status'  => $note->dt_status,
            'last_date'  => null,
            'position'   => null,
            'register'   => null,
            'responsible' => null,
            'tacit'      => 0,
            'local'      => null,
            'rubrica'   => $note->rubrica,
            'updated_at' => now(),
        ];

        $matched = false;

        foreach ($this->rules as $rule) {
            if ($rule->supports($note)) {
                $matched = true;
                // mescla o array retornado pela regra, sobrescrevendo as chaves necessárias
                $result  = array_merge($result, $rule->handle($note));
            }
        }

        if (! $matched) {
            throw new \RuntimeException("Nenhuma regra se aplicou à nota {$note->id}");
        }

        return $result;
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
