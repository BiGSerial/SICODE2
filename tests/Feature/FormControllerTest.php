<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Models\Viability;
use Tests\TestCase;

it('creates a form record', function () {
    // Cria um registro de usuário para o teste
    $user = User::find(4);

    // Define os dados do formulário
    $formData = [
        'viability_id' => 1,
        'user_id' => '9b986856-8397-4893-940b-f8b2604a40e7',
        'description' => 'Test description 12',
        'changes' => 1,
        'responsible' => 'Test responsible',
        'rejected' => false,
        'approved' => false,
    ];



    // Cria o registro do formulário
    $form = Form::updateOrCreate(['viability_id' => $formData['viability_id']], $formData)->Files()->syncWithoutDetaching([1, 2]);

    dd($form, $formData);
    // Verifica se o registro foi criado corretamente
    $this->assertDatabaseHas('forms', $formData);

    // Verifica se o registro pode ser recuperado
    $retrievedForm = Form::find($form->id);
    $this->assertNotNull($retrievedForm);
});
