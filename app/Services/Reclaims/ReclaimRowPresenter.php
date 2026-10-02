<?php

namespace App\Services\Reclaims;

use App\Custom\Notestatus;
use App\Models\Reclaim;

/**
 * Fonte única da leitura de um Reclaim para tela (Retorno Interno), export Excel
 * e log SQL Server. Qualquer regra de exibição deve ser alterada somente aqui.
 */
class ReclaimRowPresenter
{
    public const ORIGIN_CONTRACT  = 'Contratação';
    public const ORIGIN_PROJECT   = 'Análise de Projeto';
    public const ORIGIN_VIABILITY = 'Viabilidade';
    public const ORIGIN_EXTERNAL  = 'Órgão Externo';
    public const WAITING_ASSIGN   = 'Aguardando Atribuição';

    public const RELATIONS = [
        'Note',
        'Service',
        'Comments',
        'Production.User.Company',
        'Production.Company',
        'Approvals.User.Company',
        'Viabilities.Engineer.Company',
        'Waiting.User.Company',
        'Externals.User.Company',
    ];

    /**
     * Mesma precedência da tela: Contratação (padrão) < Análise < Contratação (waiting)
     * < Viabilidade < Órgão Externo.
     *
     * @return array{origin:string,color:string,requester:?\App\Models\User}
     */
    public static function source(Reclaim $reclaim): array
    {
        $origin    = self::ORIGIN_CONTRACT;
        $color     = 'text-bg-secondary';
        $requester = null;

        if ($reclaim->Approvals->isNotEmpty()) {
            $origin    = self::ORIGIN_PROJECT;
            $color     = 'text-bg-warning';
            $requester = $reclaim->Approvals->last()->User;
        }

        if ($reclaim->Waiting) {
            $origin    = self::ORIGIN_CONTRACT;
            $color     = 'text-bg-secondary';
            $requester = $reclaim->Waiting->User;
        }

        if ($reclaim->Viabilities->isNotEmpty()) {
            $origin    = self::ORIGIN_VIABILITY;
            $color     = 'text-bg-info';
            $requester = $reclaim->Viabilities->last()->Engineer;
        }

        if ($reclaim->Externals->isNotEmpty()) {
            $origin    = self::ORIGIN_EXTERNAL;
            $color     = 'text-bg-primary';
            $requester = $reclaim->Externals->last()->User;
        }

        return compact('origin', 'color', 'requester');
    }

    public static function origin(Reclaim $reclaim): string
    {
        return self::source($reclaim)['origin'];
    }

    public static function status(Reclaim $reclaim): string
    {
        return $reclaim->Production
            ? Notestatus::status($reclaim->Production->status)->status
            : self::WAITING_ASSIGN;
    }

    public static function row(Reclaim $reclaim): array
    {
        $source     = self::source($reclaim);
        $production = $reclaim->Production;

        return [
            'origin'              => $source['origin'],
            'note'                => $reclaim->Note?->note,
            'rubrica'             => $reclaim->Note?->rubrica,
            'municipality'        => $reclaim->Note?->lexp,
            'group5'              => $reclaim->Note?->group5,
            'material'            => $reclaim->Note?->material,
            'requester'           => $source['requester']?->name ?? '',
            'requester_company'   => $source['requester']?->Company?->name ?? '',
            'category'            => $reclaim->category,
            'text'                => $reclaim->Comments->last()?->message ?? '',
            'received_at'         => $reclaim->created_at,
            'status'              => self::status($reclaim),
            'responsible'         => $production ? ($production->User?->name ?? 'Desconhecido') : '',
            'responsible_company' => $production ? ($production->Company?->name ?? 'Desconhecido') : '',
            'att_at'              => $production?->att_at,
            'completed_at'        => $reclaim->completed_at,
        ];
    }
}
