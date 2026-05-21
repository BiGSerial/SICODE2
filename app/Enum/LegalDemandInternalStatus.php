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

    public function label(): string
    {
        return match ($this) {
            self::NEW_IMPORTED => 'Nova importação',
            self::TRIAGE => 'Triagem',
            self::WAITING_CONTROLLER_ACTION => 'Aguardando ação do controlador',
            self::SENT_TO_FIELD => 'Enviada ao campo',
            self::FIELD_RECEIVED => 'Recebida pelo campo',
            self::WAITING_FIELD_RESPONSE => 'Aguardando resposta do campo',
            self::RETURNED_BY_FIELD => 'Retornada pelo campo',
            self::UNDER_CONTROLLER_REVIEW => 'Em revisão do controlador',
            self::RETURNED_FOR_CORRECTION => 'Devolvida para correção',
            self::READY_TO_CLOSE_EXTERNAL => 'Pronta para encerramento externo',
            self::CLOSED_INTERNAL => 'Encerrada internamente',
            self::CLOSED_EXTERNAL => 'Encerrada externamente',
            self::CANCELLED => 'Cancelada',
            self::REOPENED => 'Reaberta',
            self::IGNORED => 'Ignorada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::RETURNED_FOR_CORRECTION, self::CANCELLED => 'badge bg-danger',
            self::CLOSED_INTERNAL, self::CLOSED_EXTERNAL => 'badge bg-success',
            self::REOPENED => 'badge bg-info text-dark',
            self::WAITING_CONTROLLER_ACTION, self::WAITING_FIELD_RESPONSE => 'badge bg-warning text-dark',
            self::SENT_TO_FIELD, self::FIELD_RECEIVED, self::UNDER_CONTROLLER_REVIEW => 'badge bg-primary',
            self::READY_TO_CLOSE_EXTERNAL => 'badge bg-dark',
            default => 'badge bg-secondary',
        };
    }
}
