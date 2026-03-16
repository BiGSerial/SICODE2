<?php

namespace App\Helpers;

use App\Support\Notifications\UserNotificationData;

class NotifyStatus
{
    public static function getStatus($status)
    {
        switch (UserNotificationData::normalizeStatus($status)) {
            case 0:
                return (object) [
                    'status' => 'Erro',
                    'icon' => 'bi bi-x-circle',
                    'color' => 'text-danger',
                    'bgcolor' => 'text-bg-danger'
                ];

            case 1:
                return (object) [
                    'status' => 'Sucesso',
                    'icon' => 'bi bi-check-circle',
                    'color' => 'text-success',
                    'bgcolor' => 'text-bg-success'
                ];

            case 2:
                return (object) [
                    'status' => 'Atenção',
                    'icon' => 'bi bi-exclamation-circle',
                    'color' => 'text-warning',
                    'bgcolor' => 'text-bg-warning'
                ];

            case 3:
                return (object) [
                    'status' => 'Pergunta',
                    'icon' => 'bi bi-info-circle text-primary',
                    'color' => 'text-primary',
                    'bgcolor' => 'text-bg-primary'
                ];

            case 4:
                return (object) [
                    'status' => 'Download',
                    'icon' => 'ri-file-download-fill',
                    'color' => 'text-success',
                    'bgcolor' => 'text-bg-success'
                ];

            case 5:
                return (object) [
                    'status' => 'Falha',
                    'icon' => 'bi bi-x-octagon',
                    'color' => 'text-danger',
                    'bgcolor' => 'text-bg-danger'
                ];

            case 6:
                return (object) [
                    'status' => 'Mensagem',
                    'icon' => 'bi bi-chat-dots',
                    'color' => 'text-primary',
                    'bgcolor' => 'text-bg-primary'
                ];

            case 7:
                return (object) [
                    'status' => 'Atribuição de Atividade',
                    'icon' => 'bi bi-person-check',
                    'color' => 'text-info',
                    'bgcolor' => 'text-bg-info'
                ];

            case 8:
                return (object) [
                    'status' => 'Aviso de Vencimento',
                    'icon' => 'bi bi-exclamation-circle',
                    'color' => 'text-warning',
                    'bgcolor' => 'text-bg-warning'
                ];

            default:
                return (object) [
                    'status' => 'Sem Status',
                    'icon' => 'bi bi-info-circle',
                    'color' => 'text-secondary',
                    'bgcolor' => 'text-bg-secondary'
                ];
        }
    }
}
