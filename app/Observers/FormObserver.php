<?php

namespace App\Observers;

use App\Models\Form;

class FormObserver
{
    /**
     * Handle the Form "created" event.
     */
    public function created(Form $form): void
    {
        //
    }

    /**
     * Handle the Form "updated" event.
     */
    public function updated(Form $form): void
    {
        //
    }

    public function updating(Form $form)
    {
        $original = $form->getOriginal();

        // Criar um novo array apenas com os campos desejados
        $historicData = [
            'id'           => $original['id'],
            'viability_id' => $original['viability_id'],
            'user_id'      => $original['user_id'],
            'description'  => $original['description'],
            'changes'      => $original['changes'],
            'responsible'  => $original['responsible'],
            'rejected'     => $original['rejected'],
            'approved'     => $original['approved'],
            'created_at'   => $original['created_at'],
            'updated_at'   => $original['updated_at'],
        ];

        $historic = json_decode($form->historic, true) ?? [];

        if (count($historic) >= 10) {
            // Remover o registro mais antigo (primeiro registro)
            array_shift($historic);
        }

        $historic[]     = $historicData;
        $form->historic = json_encode($historic);
    }

    /**
     * Handle the Form "deleted" event.
     */
    public function deleted(Form $form): void
    {
        //
    }

    /**
     * Handle the Form "restored" event.
     */
    public function restored(Form $form): void
    {
        //
    }

    /**
     * Handle the Form "force deleted" event.
     */
    public function forceDeleted(Form $form): void
    {
        //
    }
}
