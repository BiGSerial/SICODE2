<?php

namespace App\Services\HiringStatus;

use App\Models\Note;

interface RuleInterface
{
    /**
     * Retorna true se essa regra deve ser aplicada a essa nota
     *
     * @param Note $note
     * @return boolean
     */
    public function supports(Note $note): bool;


    /**
     * Monta o array de atributos para upsert no HiringStatus.
     *
     * @param Note $note
     * @return array
     */
    public function handle(Note $note): array;

}
