<?php

namespace App\Custom;

use Carbon\Carbon;

class WpaStatus
{
    public static function status($snota, $sexec, $time = null)
    {
        if ($sexec == 'Desconhecido') {
            if ($snota == 'Atribuída') {
                return (object) [
                    'info'      => $snota,
                    'icon'      => 'ri-user-fill',
                    'color'     => 'text-primary',
                    'bg_color'  => 'text-bg-primary',
                    'wpa_color' => 'blue
                ',
                    'wpa_icon' => 'ban-outline',
                ];
            } else {
                return (object) [
                    'info'      => $snota,
                    'icon'      => 'ri-user-fill',
                    'color'     => 'text-primary',
                    'bg_color'  => 'text-bg-primary',
                    'wpa_color' => 'lightgray
                ',
                    'wpa_icon' => 'ban-outline',
                ];
            }
        } elseif ($sexec == 'Baixada') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-download-2-fill',
                'color'     => 'text-secondary',
                'bg_color'  => 'text-bg-light',
                'wpa_color' => 'gray',
                'wpa_icon'  => 'arrow-down-circle-outline',
            ];
        } elseif ($sexec == 'Em Deslocamento') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-car-line',
                'color'     => 'text-warning',
                'bg_color'  => 'text-bg-warning',
                'wpa_color' => 'orange',
                'wpa_icon'  => 'car-outline',
            ];
        } elseif ($sexec == 'Em Execução') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-car-washing-fill',
                'color'     => 'text-danger',
                'bg_color'  => 'text-bg-danger',
                'wpa_color' => 'red',
                'wpa_icon'  => 'car-outline',
            ];
        } elseif ($sexec == 'Executada') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . Carbon::parse($time)->format('d/m/Y H:i:s') . '</p>',
                'icon'      => 'ri-checkbox-circle-fill',
                'color'     => 'text-success',
                'bg_color'  => 'text-bg-success',
                'wpa_color' => 'green',
                'wpa_icon'  => 'checkmark-circle-outline',
            ];
        } elseif ($sexec == 'Interrompida') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-pause-circle-line',
                'color'     => 'text-info',
                'bg_color'  => 'text-bg-info',
                'wpa_color' => 'lightblue',
                'wpa_icon'  => 'stop-circle-outline',
            ];
        } elseif ($sexec == 'Rejeitada') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-pause-circle-line',
                'color'     => 'text-info',
                'bg_color'  => 'text-bg-info',
                'wpa_color' => 'lightblue',
                'wpa_icon'  => 'play-skip-forward-circle-outline',
            ];
        } elseif ($sexec == 'Processando Execução') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-chat-settings-line',
                'color'     => 'text-danger',
                'bg_color'  => 'text-bg-danger',
                'wpa_color' => 'darkred',
                'wpa_icon'  => '',
            ];
        } elseif ($sexec == 'Processando Rejeição') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-chat-delete-line',
                'color'     => 'text-danger',
                'bg_color'  => 'text-bg-danger',
                'wpa_color' => 'darkred',
                'wpa_icon'  => '',
            ];
        } elseif ($sexec == 'Processando Vistoria') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-search-eye-line',
                'color'     => 'text-danger',
                'bg_color'  => 'text-bg-danger',
                'wpa_color' => 'darkred',
                'wpa_icon'  => '',
            ];
        } elseif ($sexec == 'Nota Vistoriada') {
            return (object) [
                'info'      => '<p class="my-0 py-0">' . $snota . '</p><p class="my-0 py-0">' . $sexec . '</p>',
                'icon'      => 'ri-search-eye-line',
                'color'     => 'text-success',
                'bg_color'  => 'text-bg-success',
                'wpa_color' => 'darkgreen',
                'wpa_icon'  => '',
            ];
        } else {
            return (object) [
                'info'      => $sexec ?? $snota,
                'icon'      => 'ri-question-line',
                'color'     => 'text-dark',
                'bg_color'  => 'text-bg-secondary',
                'wpa_color' => 'purple',
                'wpa_icon'  => '',
            ];
        }
    }
}
