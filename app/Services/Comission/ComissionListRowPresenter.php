<?php

namespace App\Services\Comission;

use App\Helpers\DaysLeft;
use App\Models\Note;
use App\Support\Ops\{Deadline, RowState};
use Carbon\Carbon;

/**
 * Monta, para a lista operacional do Comissionamento (despacho e parceira), tudo o que uma linha mostra, já com tom
 * semântico (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; regra de prazo e de situação mora aqui.
 *
 * Usa só relações já carregadas (`Productions.User`, `Productions.Company`, `Wpas`): nenhuma consulta por linha.
 */
class ComissionListRowPresenter
{
    /** Prazo total, em dias, da atividade (o "Prazo Real" mostra quantos dias já passaram dele). */
    public const TOTAL_DAYS = 30;

    public function present(Note $note, string $serviceUuid): array
    {
        $productions = $note->Productions->where('service_id', $serviceUuid);
        $open        = $productions->where('noinconsistency', false);
        $completed   = $productions->where('completed', true);

        $production = $open->last();
        $blocked    = $production && ($production->dt_note == $note->dt_status || !$production->confirmed);

        $lastUser = null;

        if ($completed->count()) {
            $name     = $completed->last()->User?->name;
            $parts    = $name ? explode(' ', $name) : [];
            $lastUser = $parts ? $parts[0] . ' ' . end($parts) : null;
        }

        $company = null;

        if ($production) {
            $first   = $production->Company?->name ? explode(' ', $production->Company->name)[0] : null;
            $company = $first ? mb_strtoupper($first) : 'Desconhecido';
        }

        $daysLeft = (new DaysLeft($note))->getDaysLeft();
        $wpa      = $note->Wpas->last();

        return [
            'key'         => (string) $note->id,
            'noteId'      => (int) $note->id,
            'note'        => $note->note,
            'dd'          => $wpa && !$wpa->production_id ? $wpa->dd : null,
            'mmgd'        => (bool) $note->mmgd,
            'createdAt'   => $this->date($note->dt_created),
            'order'       => $note->numPedido ? mb_strtoupper($note->numPedido) : null,
            'rubrica'     => $note->rubrica,
            'city'        => $note->lexp ?: null,
            'group1'      => $note->group1,
            'group2'      => $note->group2,
            'group4'      => $note->group4,
            'group5'      => $note->group5,
            'surveyCount' => $completed->count(),
            'surveyUser'  => $lastUser,
            'status'      => $note->nstats,
            'center'      => $note->centerjob,
            'dueAt'       => Carbon::today()->addDays($daysLeft),
            'dueDays'     => $daysLeft,
            'dueTone'     => $this->dueTone($daysLeft),
            'dueSub'      => Deadline::remainingText($daysLeft),
            'elapsedDays' => self::TOTAL_DAYS - $daysLeft,
            'sap'         => $this->sap($note->pze_parecer),
            'state'       => $blocked ? $this->stateOf($production) : RowState::FREE,
            'canDispatch' => !$blocked,
            'company'     => $company,
        ];
    }

    /**
     * Cor do prazo real (regra da atividade, mesmos limites de antes): vencido e abaixo de 6 dias vermelho (o texto
     * "Venceu há…" distingue o vencido), de 6 a 9 dias âmbar, 10 dias ou mais verde.
     */
    public function dueTone(int $daysLeft): string
    {
        return match (true) {
            $daysLeft < 6  => 'red',
            $daysLeft < 10 => 'amber',
            default        => 'green',
        };
    }

    /** Situação da linha a partir da Production aberta (mesma leitura de cores de antes, nas 5 situações padrão). */
    private function stateOf($production): string
    {
        return match (true) {
            (int) $production->status === 1                            => RowState::STACK,
            (int) $production->status === 2                            => RowState::ASSIGNED,
            (int) $production->status === 5 && !$production->confirmed => RowState::DONE,
            (int) $production->status === 5                            => RowState::LATE,
            default                                                    => RowState::ASSIGNED,
        };
    }

    private function sap(?string $parecer): array
    {
        return match ($parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'O SAP marca o prazo desta nota como vencido.'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'O SAP marca o prazo desta nota como em dia.'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'O SAP não informou o parecer de prazo.'],
        };
    }

    private function date($value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
