<?php

namespace App\Enum;

enum CancellationRequestStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case ASSIGNED = 'ASSIGNED';
    case PAUSED = 'PAUSED';
    case DONE = 'DONE';
    case REJECTED = 'REJECTED';
    case ABORTED = 'ABORTED';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::SUBMITTED => 'Enviado',
            self::ASSIGNED => 'Em execução',
            self::PAUSED => 'Pausado',
            self::DONE => 'Concluído',
            self::REJECTED => 'Rejeitado',
            self::ABORTED => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-secondary',
            self::SUBMITTED => 'bg-info',
            self::ASSIGNED => 'bg-primary',
            self::PAUSED => 'bg-warning text-dark',
            self::DONE => 'bg-success',
            self::REJECTED => 'bg-danger',
            self::ABORTED => 'bg-secondary',
        };
    }
}
