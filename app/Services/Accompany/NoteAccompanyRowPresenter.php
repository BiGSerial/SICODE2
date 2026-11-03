<?php

namespace App\Services\Accompany;

use App\Custom\Notestatus;
use App\Models\Production;
use App\Support\Ops\{ControlAge, PrazoReal, RowState, Tone};
use Carbon\CarbonInterface;

/**
 * Linha do ACOMPANHAMENTO de Serviços das atividades "por nota" (Análises, Pré-análise, Cadastro, Incorporação, Reverso): a Produção
 * do usuário já com estado, tons e textos prontos, para a view só desenhar (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). O que muda
 * por atividade vem do `ActivityAccompanyProfile` (origem do prazo, colunas).
 *
 * Situação da linha (`RowState`): a fila é toda do usuário, então só se marca o que foge do normal: produção bloqueada (aguardando
 * outra ação) fica azul; as demais, brancas. Prioridade tem a marca própria `ops-row-priority`.
 */
class NoteAccompanyRowPresenter
{
    /** Status da produção (Notestatus): 3 em atividade, 4 pausada. */
    public const STATUS_IN_PROGRESS = 3;

    public const STATUS_PAUSED = 4;

    /**
     * @param array<string, mixed>             $profile  ActivityAccompanyProfile::for()
     * @param array<int, array<string, mixed>> $legalTags demandas jurídicas da nota (BuildsLegalNoteTags)
     *
     * @return array<string, mixed>
     */
    public function present(Production $production, array $profile, array $legalTags = [], ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $note   = $production->Note;
        $status = (int) $production->status;
        $left   = PrazoReal::daysLeft($note?->dt_created ? \Carbon\Carbon::parse($note->dt_created) : null, $note?->days_left, $profile['prazo'], $now);

        $started = (bool) $production->completed === false && !$production->block;

        return [
            'id'           => (int) $production->id,
            'noteId'       => (int) $production->note_id,
            'note'         => (string) $note?->note,
            'priority'     => (bool) $production->priority,
            'block'        => (bool) $production->block,
            'legal'        => $legalTags,
            'createdAt'    => $note?->dt_created,
            'order'        => $note?->numPedido,
            'rubrica'      => $note?->rubrica,
            'city'         => $note?->lexp,
            'zone'         => $note?->group1,
            'group2'       => $note?->group2,
            'material'     => $note?->material,
            'attAt'        => $production->att_at,
            'attDays'      => ControlAge::days($production->att_at),
            'createdDays'  => ControlAge::days($note?->dt_created ? \Carbon\Carbon::parse($note->dt_created) : null),
            'prazoLeft'    => $left,
            'prazoElapsed' => PrazoReal::elapsed($left),
            'prazoTone'    => PrazoReal::tone($left),
            'prazoHint'    => PrazoReal::hint($left, $profile['prazo']),
            'status'       => $status,
            'statusLabel'  => Notestatus::status($status)->status,
            'statusTone'   => Tone::forProductionStatus($status),
            'paused'       => $status === self::STATUS_PAUSED,
            'pausedFor'    => $status === self::STATUS_PAUSED ? $production->updated_at?->diffForHumans($now, ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'locale' => 'pt_BR']) : null,
            'canAct'       => $started,
            'actionLabel'  => match ($status) {
                self::STATUS_IN_PROGRESS => 'Continuar',
                self::STATUS_PAUSED      => 'Retomar',
                default                  => 'Iniciar',
            },
            'state' => $production->block ? RowState::ASSIGNED : RowState::FREE,
        ];
    }
}
