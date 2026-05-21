<?php

namespace App\Enum;

enum LegalDemandAssignmentStatus: string
{
    case SENT = 'sent';
    case RECEIVED = 'received';
    case IN_PROGRESS = 'in_progress';
    case ANSWERED = 'answered';
    case RETURNED_TO_CONTROLLER = 'returned_to_controller';
    case RETURNED_FOR_CORRECTION = 'returned_for_correction';
    case CANCELLED = 'cancelled';
    case CLOSED = 'closed';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'Enviada',
            self::RECEIVED => 'Recebida',
            self::IN_PROGRESS => 'Em andamento',
            self::ANSWERED => 'Respondida',
            self::RETURNED_TO_CONTROLLER => 'Retornada ao controlador',
            self::RETURNED_FOR_CORRECTION => 'Devolvida para correção',
            self::CANCELLED => 'Cancelada',
            self::CLOSED => 'Encerrada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ANSWERED, self::RETURNED_TO_CONTROLLER, self::CLOSED => 'badge bg-success',
            self::RETURNED_FOR_CORRECTION, self::CANCELLED => 'badge bg-danger',
            self::IN_PROGRESS => 'badge bg-warning text-dark',
            self::RECEIVED => 'badge bg-info text-dark',
            self::SENT => 'badge bg-primary',
        };
    }
}
