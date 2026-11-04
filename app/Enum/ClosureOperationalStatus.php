<?php

namespace App\Enum;

enum ClosureOperationalStatus: string
{
    case AVAILABLE        = 'AVAILABLE';
    case ASSIGNED         = 'ASSIGNED';
    case IN_PROGRESS      = 'IN_PROGRESS';
    case WAITING_PARTNER  = 'WAITING_PARTNER';
    case DEPOSIT_BLOCKED  = 'DEPOSIT_BLOCKED';
    case DEPOSIT_RELEASED = 'DEPOSIT_RELEASED';
    case PAUSED           = 'PAUSED';
    case FINISHED         = 'FINISHED';
    case WAITING          = 'WAITING';
    case CLOSED           = 'CLOSED';
    case CANCELLED        = 'CANCELLED';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public static function labelFor(?string $value): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        return $case?->label() ?? ($value ?? '—');
    }

    public static function badgeClassFor(?string $value): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        return $case?->badgeClass() ?? 'bg-secondary';
    }

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE        => 'Disponível',
            self::ASSIGNED         => 'Atribuída',
            self::IN_PROGRESS      => 'Em andamento',
            self::WAITING_PARTNER  => 'Aguardando Parceira',
            self::DEPOSIT_BLOCKED  => 'Depósito Bloqueado',
            self::DEPOSIT_RELEASED => 'Depósito Liberado',
            self::PAUSED           => 'Pausa',
            self::FINISHED         => 'Finalizado · aguardando SAP',
            self::WAITING          => 'Aguardando',
            self::CLOSED           => 'Encerrada',
            self::CANCELLED        => 'Cancelada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE        => 'bg-secondary',
            self::ASSIGNED         => 'bg-info',
            self::IN_PROGRESS      => 'bg-primary',
            self::WAITING_PARTNER  => 'bg-warning text-dark',
            self::DEPOSIT_BLOCKED  => 'bg-danger',
            self::DEPOSIT_RELEASED => 'bg-success',
            self::PAUSED           => 'bg-dark',
            self::FINISHED         => 'bg-success',
            self::WAITING          => 'bg-info text-dark',
            self::CLOSED           => 'bg-success',
            self::CANCELLED        => 'bg-secondary',
        };
    }

    /** Tom semântico dos selos `<x-ops.badge>` (green|blue|amber|red|teal|muted). */
    public function tone(): string
    {
        return match ($this) {
            self::AVAILABLE, self::CANCELLED => 'muted',
            self::ASSIGNED, self::WAITING => 'teal',
            self::IN_PROGRESS     => 'blue',
            self::WAITING_PARTNER => 'amber',
            self::DEPOSIT_BLOCKED => 'red',
            self::PAUSED          => 'amber',
            self::DEPOSIT_RELEASED, self::FINISHED, self::CLOSED => 'green',
        };
    }

    public static function toneFor(?string $value): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        return $case?->tone() ?? 'muted';
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::CLOSED, self::CANCELLED], true);
    }

    /** Ordem ainda em tratativa pelo usuário (não terminal e não concluída aguardando SAP/meta). */
    public function isActive(): bool
    {
        return !$this->isTerminal() && !in_array($this, [self::FINISHED, self::WAITING], true);
    }
}
