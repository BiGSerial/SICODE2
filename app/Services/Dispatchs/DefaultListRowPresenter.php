<?php

namespace App\Services\Dispatchs;

use App\Custom\Notestatus;
use App\Models\{Note, Production};
use App\Support\Ops\{RowState, Tone};
use Carbon\Carbon;

/**
 * Monta, para as listas do Despacho da atividade padrão (`Dispatchs\Default\*`), tudo o que uma linha mostra, já com tom
 * semântico (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; a regra de situação e de cor mora aqui.
 *
 * Lista operacional (`present`): a situação da linha vem da Production aberta da atividade (mesma regra da view antiga):
 *  - sem Production aberta => livre;
 *  - Production aberta e não confirmada, ou com a mesma data de status da nota => bloqueada, e a cor segue o status:
 *    1 (Não atribuído) = na pilha; 5 sem confirmação = concluída; 5 confirmada = SAP pendente; demais = com usuário;
 *  - exceção: nota tipo 1 finalizada e confirmada continua despachável (volta a ser atribuída).
 */
class DefaultListRowPresenter
{
    /** Regra de bloqueio da linha (a mesma que a view antiga aplicava), a partir da Production aberta. */
    public function evaluate(Note $note, string $serviceUuid): array
    {
        $open       = $note->Productions->where('service_id', $serviceUuid)->where('noinconsistency', false);
        $production = $open->first();
        $block      = $production && ($production->dt_note == $note->dt_status || !$production->confirmed);
        $exception  = $block && $note->type_note == 1 && $production->status == 5 && $production->confirmed;

        $state = RowState::FREE;

        if ($block) {
            $state = match (true) {
                $production->status == 1                            => RowState::STACK,
                $production->status == 5 && !$production->confirmed => RowState::DONE,
                $production->status == 5 && $production->confirmed  => RowState::LATE,
                default                                             => RowState::ASSIGNED,
            };
        }

        return [
            'production'  => $production,
            'state'       => $state,
            'canDispatch' => !$block || $exception,
        ];
    }

    /**
     * @param  array{production: ?Production, state: string, canDispatch: bool} $eval resultado de `evaluate()`
     * @return array<string, mixed>
     */
    public function present(Note $note, string $serviceUuid, array $eval, bool $partner = false): array
    {
        $production = $eval['production'];
        $done       = $note->Productions->where('service_id', $serviceUuid)->where('completed', true);
        $lastUser   = null;

        if ($done->count()) {
            $parts    = explode(' ', (string) ($done->last()->User?->name ?? ''));
            $lastUser = trim($parts[0] . ' ' . end($parts));
        }

        $company = null;

        if ($production) {
            $company = $production->Company?->name
                ? mb_strtoupper(explode(' ', $production->Company->name)[0])
                : 'Desconhecido';
        }

        // "Prazo real": dias de prazo já consumidos (30; 45 para MMGD aéreo primário MT) menos os dias restantes da nota.
        $limit = ($note->mmgd && $note->group2 == 'Aéreo - Primário MT') ? 45 : 30;

        $parecer = match ($note->pze_parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Parecer do prazo: vencido'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Parecer do prazo: dentro do prazo'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'Parecer do prazo ainda não informado'],
        };

        return [
            'key'         => (string) $note->id,
            'noteId'      => $note->id,
            'note'        => $note->note,
            'mmgd'        => (bool) $note->mmgd,
            'createdAt'   => $note->dt_created,
            'numPedido'   => mb_strtoupper((string) $note->numPedido),
            'rubrica'     => $note->rubrica,
            'city'        => $note->lexp,
            'group1'      => $note->group1,
            'group2'      => $note->group2,
            'group4'      => $note->group4,
            'group5'      => $note->group5,
            'returns'     => $done->count(),
            'lastUser'    => $lastUser,
            'status'      => $note->nstats,
            'realDays'    => $limit - (int) $note->days_left,
            'parecer'     => $parecer,
            'state'       => $eval['state'],
            'canDispatch' => $eval['canDispatch'],
            'company'     => $company,
            'dispatchAt'  => $partner ? $production?->dispatch_at : null,
            'attAt'       => $partner ? $production?->att_at : null,
            'completedAt' => $partner ? $production?->completed_at : null,
        ];
    }

    /**
     * Linha do Controle (Production já despachada). Sem cor de situação: o estado vive no selo de Status.
     *
     * @return array<string, mixed>
     */
    public function presentControl(Production $production): array
    {
        $note   = $production->Note;
        $status = (int) $production->status;
        $since  = fn ($date) => $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays(now()->startOfDay()) : null;

        return [
            'id'           => $production->id,
            'noteId'       => $production->note_id,
            'note'         => $note->note,
            'priority'     => (bool) $production->priority,
            'mmgd'         => (bool) $note->mmgd,
            'group2'       => $note->group2,
            'rubrica'      => $note->rubrica,
            'city'         => $note->lexp,
            'group1'       => $note->group1,
            'material'     => $note->material,
            'company'      => $production->Company ? explode(' ', $production->Company->name)[0] : null,
            'companyFull'  => $production->Company?->name,
            'user'         => $production->User,
            'dispatchAt'   => $production->dispatch_at,
            'dispatchDays' => $since($production->dispatch_at),
            'attAt'        => $production->att_at,
            'attDays'      => $since($production->att_at),
            'noteAt'       => $note->dt_status,
            'noteDays'     => $since($note->dt_status),
            'status'       => $status,
            'statusLabel'  => Notestatus::status($status)->status,
            'statusTone'   => Tone::forProductionStatus($status),
            'blocked'      => (bool) $production->block,
            'completed'    => (bool) $production->completed,
        ];
    }
}
