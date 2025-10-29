<?php

namespace App\Enum;

enum ProtestJobStatus: string
{
    case OPENED = 'opened';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case WAITING = 'waiting';
    case DONE = 'done';
    case CANCELED = 'canceled';
    case REOPENED = 'reopened';


    public function label(): string
    {
        return match($this) {
            self::OPENED      => 'Aberto',
            self::ASSIGNED    => 'Aceito',
            self::IN_PROGRESS => 'Em execução',
            self::WAITING     => 'Em espera',
            self::DONE        => 'Concluído',
            self::CANCELED    => 'Cancelado',
            self::REOPENED    => 'Reaberto',
        };
    }

    // Bootstrap 5: text-bg-* + badge
    public function badgeClass(): string
    {
        return match($this) {
            self::OPENED      => 'badge text-bg-secondary',
            self::ASSIGNED    => 'badge text-bg-primary',
            self::IN_PROGRESS => 'badge text-bg-warning',
            self::WAITING     => 'badge text-bg-dark',
            self::DONE        => 'badge text-bg-success',
            self::CANCELED    => 'badge text-bg-danger',
            self::REOPENED    => 'badge text-bg-info',
        };
    }
}
