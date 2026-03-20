<?php

namespace App\Custom;

class Notestatus
{
    public $badge;

    public static function status($sts)
    {
        $sts    = intval($sts);
        $status = [
            // 0
            [
                'status'  => 'Desativado',
                'icon'    => 'ri-user-unfollow-line',
                'colorbg' => 'text-bg-info',
                'color'   => 'info',
            ],
            // 1
            [
                'status'  => 'Nao Atribuido',
                'icon'    => 'ri-user-unfollow-fill',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'secondary',
            ],
            // 2
            [
                'status'  => 'Atribuido',
                'icon'    => 'ri-user-received-fill',
                'colorbg' => 'text-bg-primary',
                'color'   => 'primary',
            ],
            // 3
            [
                'status'  => 'Em Andamento',
                'icon'    => 'ri-run-fill',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 4
            [
                'status'  => 'Em Pausa',
                'icon'    => 'ri-pause-mini-line',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 5
            [
                'status'  => 'Finalizado',
                'icon'    => 'ri-user-follow-fill',
                'colorbg' => 'text-bg-success',
                'color'   => 'success',
            ],
            // 6
            [
                'status'  => 'Confirmado',
                'icon'    => 'ri-check-double-line',
                'colorbg' => 'text-bg-success',
                'color'   => 'success',
            ],
            // 7
            [
                'status'  => 'Nota Desatribuida',
                'icon'    => 'ri-user-unfollow-fill',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 8
            [
                'status'  => 'Devolvido',
                'icon'    => 'ri-arrow-go-back-fill',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 9
            [
                'status'  => 'Retorno para Andamento',
                'icon'    => 'ri-arrow-go-back-fill',
                'colorbg' => 'text-bg-info',
                'color'   => 'info',
            ],
            // 10
            [
                'status'  => 'Retorno',
                'icon'    => 'ri-arrow-left-circle-line',
                'colorbg' => 'text-bg-primary',
                'color'   => 'primary',
            ],
            // 11
            [
                'status'  => 'Inserido no Sistema',
                'icon'    => 'ri-install-line',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'secondary',
            ],
            // 12
            [
                'status'  => 'Despachado',
                'icon'    => 'ri-send-plane-fill',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'primary',
            ],
            // 13
            [
                'status'  => 'Nota Atualizada',
                'icon'    => 'ri-exchange-funds-fill',
                'colorbg' => 'text-bg-primary',
                'color'   => 'primary',
            ],
            // 14
            [
                'status'  => 'Nota Iniciada',
                'icon'    => 'ri-play-fill',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 15
            [
                'status'  => 'Nota Controlada',
                'icon'    => 'ri-user-heart-fill',
                'colorbg' => 'text-bg-dark',
                'color'   => 'dark',
            ],
            // 16
            [
                'status'  => 'Nao Despachado',
                'icon'    => 'ri-user-unfollow-line',
                'colorbg' => 'text-bg-info',
                'color'   => 'info',
            ],
            // 17
            [
                'status'  => 'Nota Devolvida',
                'icon'    => 'ri-arrow-go-back-fill',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 18
            [
                'status'  => 'Atribuida Automaticamente',
                'icon'    => 'ri-user-received-fill',
                'colorbg' => 'text-bg-primary',
                'color'   => 'primary',
            ],
            // 19
            [
                'status'  => 'Em Transferência',
                'icon'    => 'ri-folder-transfer-fill',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'secondary',
            ],
            // 20
            [
                'status'  => 'Rejeitado',
                'icon'    => 'ri-folder-forbid-line',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 21
            [
                'status'  => 'Transferido',
                'icon'    => 'ri-folder-transfer-fill',
                'colorbg' => 'text-bg-success',
                'color'   => 'success',
            ],
            // 22
            [
                'status'  => 'Inconsistência',
                'icon'    => 'ri-alert-line',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 23
            [
                'status'  => 'Removido Produçao',
                'icon'    => 'ri-alert-line',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 24
            [
                'status'  => 'Priorizada',
                'icon'    => 'ri-alert-line',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 25
            [
                'status'  => 'Prioridade Removida',
                'icon'    => 'ri-alert-line',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 26
            [
                'status'  => 'Reatribuído',
                'icon'    => 'ri-user-received-fill',
                'colorbg' => 'text-bg-primary',
                'color'   => 'primary',
            ],
            // 27
            [
                'status'  => 'Iniciada',
                'icon'    => 'ri-run-fill',
                'colorbg' => 'text-bg-info',
                'color'   => 'info',
            ],
            // 28
            [
                'status'  => 'Pub Parcial',
                'icon'    => 'ri-map-pin-time-line',
                'colorbg' => 'text-bg-warning',
                'color'   => 'warning',
            ],
            // 29
            [
                'status'  => 'Cancelada',
                'icon'    => 'ri-close-circle-line',
                'colorbg' => 'text-bg-secondary',
                'color'   => 'secondary',
            ],
            // 30
            [
                'status'  => 'Em Análise de Projeto',
                'icon'    => 'ri-search-eye-line',
                'colorbg' => 'text-bg-dark',
                'color'   => 'dark',
            ],
            // 31
            [
                'status'  => 'Reprovado na Análise',
                'icon'    => 'ri-close-circle-line',
                'colorbg' => 'text-bg-danger',
                'color'   => 'danger',
            ],
            // 32
            [
                'status'  => 'Liberado Pra Finalizar',
                'icon'    => 'ri-checkbox-circle-line',
                'colorbg' => 'text-bg-success',
                'color'   => 'success',
            ],
        ];

        $fallback = [
            'status' => 'Status Desconhecido',
            'icon' => 'ri-question-line',
            'colorbg' => 'text-bg-secondary',
            'color' => 'secondary',
        ];

        return (object) ($status[$sts] ?? $fallback);
    }
}
