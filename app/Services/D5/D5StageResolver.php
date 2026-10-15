<?php

namespace App\Services\D5;

use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Etapa (Fornecedor / Fiscalização / Medição / Finalizado) de uma D5 na lista de acompanhamento.
 *
 * Quando a D5 pertence a um informe, a etapa sai do status atual do informe
 * (WorkReportStatusResolver), para a lista bater com a consulta do informe. Sem informe
 * (ou com status fora do mapa), vale a regra legada pelos flags da própria D5.
 */
class D5StageResolver
{
    public const SUPPLIER      = 'aguardando_fornecedor';
    public const FISCALIZATION = 'aguardando_fiscalizacao';
    public const PAYMENT       = 'aguardando_pagamento';
    public const FINALIZED     = 'finalizado';

    /** @var array<string, string[]> */
    public const STATUS_KEYS = [
        self::SUPPLIER      => ['d5_returned', 'd5_with_partner', 'waiting_d5_resolution'],
        self::FISCALIZATION => ['waiting_d5_fiscalization', 'd5_fiscalization'],
        self::PAYMENT       => ['waiting_d5_payment', 'releasing_letter'],
        self::FINALIZED     => ['finalized'],
    ];

    public static function fromStatusKey(?string $statusKey): ?string
    {
        foreach (self::STATUS_KEYS as $stage => $keys) {
            if (in_array($statusKey, $keys, true)) {
                return $stage;
            }
        }

        return null;
    }

    public static function mappedStatusKeys(): array
    {
        return array_merge(...array_values(self::STATUS_KEYS));
    }

    /**
     * Filtro por etapa: status do informe quando mapeado; senão, flags legados da D5.
     */
    public static function applyFilter(Builder $query, string $stage): void
    {
        $keys = self::STATUS_KEYS[$stage] ?? null;

        if (!$keys) {
            return;
        }

        $query->where(function ($outer) use ($stage, $keys) {
            $outer->whereHas('WorkReport', fn ($w) => $w->whereIn('current_status_key', $keys))
                ->orWhere(function ($legacy) use ($stage) {
                    $legacy->whereDoesntHave(
                        'WorkReport',
                        fn ($w) => $w->whereIn('current_status_key', self::mappedStatusKeys())
                    );

                    self::applyLegacyFilter($legacy, $stage);
                });
        });
    }

    private static function applyLegacyFilter(Builder $query, string $stage): void
    {
        switch ($stage) {
            case self::SUPPLIER:
                $query->where('visible_partner', true)
                    ->where('is_completed', false)
                    ->where('is_archived', false);

                break;
            case self::FISCALIZATION:
                $query->where('is_completed', true)
                    ->where('is_supervisioned', false)
                    ->where('is_archived', false);

                break;
            case self::PAYMENT:
                $query->where(function ($main) {
                    $main->where(function ($q) {
                        $q->where('is_supervisioned', true)
                            ->where('is_archived', false);
                    })->orWhere(function ($q) {
                        $q->where('is_archived', false)
                            ->where('visible_partner', false)
                            ->where(function ($d5) {
                                $d5->whereNull('note_d5')
                                    ->orWhere('note_d5', '');
                            });
                    });
                });

                break;
            case self::FINALIZED:
                $query->where('is_archived', true);

                break;
        }
    }
}
