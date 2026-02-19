<?php

namespace App\Enum;

enum CancellationEngineerApprovalStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando Engenheiro',
            self::APPROVED => 'Aprovado pelo Engenheiro',
            self::REJECTED => 'Rejeitado pelo Engenheiro',
            self::CANCELED => 'Solicitação ao Engenheiro Cancelada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-warning text-dark',
            self::APPROVED => 'bg-success',
            self::REJECTED => 'bg-danger',
            self::CANCELED => 'bg-secondary',
        };
    }
}
