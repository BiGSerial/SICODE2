<?php

namespace App\Services\Reclaims;

use App\Custom\Notestatus;
use App\Models\Reclaim;
use App\Support\Ops\{RowState, Tone};
use Carbon\CarbonInterface;

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

    /** Tom semântico (lista operacional) de cada origem. */
    public const ORIGIN_TONES = [
        self::ORIGIN_CONTRACT  => 'muted',
        self::ORIGIN_PROJECT   => 'amber',
        self::ORIGIN_VIABILITY => 'teal',
        self::ORIGIN_EXTERNAL  => 'blue',
    ];

    /** Horas sem andamento a partir das quais o retorno aparece em vermelho na coluna "Em atividade". */
    public const STALE_AFTER_HOURS = 24;

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

    /**
     * Linha da lista de Retorno Interno (Despacho) já pronta para desenhar: só dados e tons semânticos (`green|blue|amber|red|teal|muted`),
     * nunca classe Bootstrap. A situação da linha usa as cinco de `RowState`: sem atividade = livre; com usuário = azul; com atividade
     * mas sem usuário (pilha da empresa) = âmbar. Retorno aberto nunca está concluído, então não há verde nem vermelho de linha.
     *
     * @return array<string, mixed>
     */
    public static function listRow(Reclaim $reclaim, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $source     = self::source($reclaim);
        $production = $reclaim->Production;
        $note       = $reclaim->Note;
        $stale      = $reclaim->updated_at && $reclaim->updated_at->lt($now->copy()->subHours(self::STALE_AFTER_HOURS));

        return [
            'id'          => $reclaim->id,
            'note'        => $note?->note,
            'express'     => $note?->pze == '25',
            'expressDays' => $note?->pze,
            'files'       => $note?->Files ?? collect(),
            'origin'      => $source['origin'],
            'originTone'  => self::ORIGIN_TONES[$source['origin']] ?? 'muted',
            'requester'   => $source['requester']?->name,
            'rubrica'     => $note?->rubrica,
            'city'        => $note?->lexp,
            'group5'      => $note?->group5,
            'material'    => $note?->material,
            'category'    => $reclaim->category,
            'sentAt'      => $reclaim->created_at,
            'activeFor'   => $reclaim->created_at?->diffForHumans($now, ['locale' => 'pt_BR', 'syntax' => CarbonInterface::DIFF_ABSOLUTE]),
            'staleTone'   => $stale ? 'red' : 'muted',
            'hasActivity' => $production !== null,
            'status'      => self::status($reclaim),
            'statusTone'  => $production ? Tone::forProductionStatus($production->status) : 'muted',
            'responsible' => $production ? ($production->User?->name ?? 'Desconhecido') : null,
            'company'     => $production ? ($production->Company?->name ?? 'Desconhecido') : null,
            'state'       => match (true) {
                $production === null        => RowState::FREE,
                (bool) $production->user_id => RowState::ASSIGNED,
                default                     => RowState::STACK,
            },
        ];
    }
}
