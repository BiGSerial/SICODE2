<?php

namespace App\Custom;

class Viabilitiesstatus
{
    public $badge;

    public static function status($sts)
    {
        $sts    = intval($sts);
        $status = [
            // 0
            [
                'status'  => 'Sem Status',
                'icon'    => 'bx bxs-ghost',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'secondary',
            ],
            // 1
            [
                'status'  => 'Em Viabilidade',
                'icon'    => 'bx bx-show-alt',
                'colorbg' => 'text-bg-primary',
                'color'   => 'primary',
            ],
            // 2
            [
                'status'  => 'Em Execução',
                'icon'    => 'bx bx-bolt-circle',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 3
            [
                'status'  => 'Em Pausa',
                'icon'    => 'ri-run-fill',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 4
            [
                'status'  => 'Aguardando Contratante',
                'icon'    => 'bx bxs-user-voice',
                'colorbg' => 'text-bg-info',
                'color'   => 'info',
            ],
            // 5
            [
                'status'  => 'Aguardando Parceira',
                'icon'    => 'bx bxs-user-voice',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 6
            [
                'status'  => 'Viabilidade Aprovada',
                'icon'    => 'bx bxs-check-circle',
                'colorbg' => 'text-bg-success',
                'color'   => 'success',
            ],
            // 7
            [
                'status'  => 'Viabilidade Rejeitada',
                'icon'    => 'bx bxs-meh-alt',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 8
            [
                'status'  => 'Cancelado',
                'icon'    => 'bx bx-message-square-x',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'secondary',
            ],
            // 9
            [
                'status'  => 'Contratado',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-success',
                'color'   => 'success',
            ],
            // 10
            [
                'status'  => 'Em Resolução Contratante',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 11
            [
                'status'  => 'Criado RI',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-primary',
                'color'   => 'warning',
            ],
            // 12
            [
                'status'  => 'Em Resolução RI',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-primary',
                'color'   => 'warning',
            ],
            // 13
            [
                'status'  => 'Concluído RI',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-primary',
                'color'   => 'warning',
            ],
            // 14
            [
                'status'  => 'Liberado Contratação',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-primary',
                'color'   => 'warning',
            ],
            // 15
            [
                'status'  => 'Liberado Tácitamente',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 16
            [
                'status'  => 'Aguardando Despacho',
                'icon'    => 'bx bxs-badge-dollar',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],

        ];

        return (object) $status[$sts];
    }
}
