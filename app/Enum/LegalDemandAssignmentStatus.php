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
}
