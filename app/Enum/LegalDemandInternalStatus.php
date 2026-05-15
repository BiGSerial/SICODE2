<?php

namespace App\Enum;

enum LegalDemandInternalStatus: string
{
    case NEW_IMPORTED = 'new_imported';
    case TRIAGE = 'triage';
    case WAITING_CONTROLLER_ACTION = 'waiting_controller_action';
    case SENT_TO_FIELD = 'sent_to_field';
    case FIELD_RECEIVED = 'field_received';
    case WAITING_FIELD_RESPONSE = 'waiting_field_response';
    case RETURNED_BY_FIELD = 'returned_by_field';
    case UNDER_CONTROLLER_REVIEW = 'under_controller_review';
    case RETURNED_FOR_CORRECTION = 'returned_for_correction';
    case READY_TO_CLOSE_EXTERNAL = 'ready_to_close_external';
    case CLOSED_INTERNAL = 'closed_internal';
    case CLOSED_EXTERNAL = 'closed_external';
    case CANCELLED = 'cancelled';
    case REOPENED = 'reopened';
    case IGNORED = 'ignored';

    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
