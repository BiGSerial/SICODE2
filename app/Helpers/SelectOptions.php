<?php

namespace App\Helpers;

class SelectOptions
{
    public static function getReclaimsOptions()
    {
        return [
            (object)['info' => '(RI) ANEXAR PDF AO PROJETO', 'value' => 'ANEXAR PDF', 'needFile' => true],
            (object)['info' => '(RI) LIBERAR PROJETO NO EO', 'value' => 'LIBERAR EO', 'needFile' => false],
            (object)['info' => '(RI) ALTERAÇÃO NO PROJETO', 'value' => 'ALTERAR PROJETO', 'needFile' => true],
        ];
    }

    public static function verifyNeedFilesReclaims($item)
    {


        foreach (static::getReclaimsOptions() as $option) {



            if ($option->value == $item) {

                if ($option->needFile) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        return false;
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
