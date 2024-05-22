<?php

namespace App\Helpers;

class SelectOptions
{
    public static function getReclaimsOptions()
    {
        return [
            (object)['info' => '(RI) ANEXAR PDF AO PROJETO', 'value' => 'ANEXAR PDF'],
            (object)['info' => '(RI) LIBERAR PROJETO NO EO', 'value' => 'LIBERAR EO'],
            (object)['info' => '(RI) ALTERAÇÃO NO PROJETO', 'value' => 'ALTERAR PROJETO'],
        ];
    }

    public static function getResponserOptions()
    {
        return [
            (object)['info' => 'Selecione Resposta', 'value' => ''],
            (object)['info' => 'CONCORDAR', 'value' => 'CONCORDAR'],
            (object)['info' => 'DISCORDAR', 'value' => 'DISCORDAR'],
        ];
    }

}
