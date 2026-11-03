<?php

namespace App\Http\Livewire\Services\Cadastro;

use App\Http\Livewire\Services\Concerns\IsActivityNoteList;
use App\Models\Note;
use App\Support\Ops\RowState;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use IsActivityNoteList;

    protected $paginationTheme = 'bootstrap';

    /** O Cadastro nunca gravou a linha do tempo ao assumir. */
    protected function recordsTimeline(): bool
    {
        return false;
    }

    /**
     * Linha da tela. A Produção que vale é a última deste serviço sem inconsistência; ela bloqueia a nota se for da mesma data de status
     * ou ainda não confirmada. A cor mantém a regra antiga: status 1 amarelo (pilha), 2 azul (com usuário), 5 verde (concluída, ainda não
     * confirmada), 5 confirmada vermelho (SAP pendente), outros azul.
     */
    protected function rowFor(Note $note): array
    {
        $productions = $note->Productions
            ->where('service_id', $this->service->uuid)
            ->where('noinconsistency', false);

        $last    = $productions->last();
        $blocked = $last && ($last->dt_note == $note->dt_status || !$last->confirmed);

        $state = RowState::FREE;

        if ($blocked) {
            $state = match (true) {
                $last->status == 1                      => RowState::STACK,
                $last->status == 2                      => RowState::ASSIGNED,
                $last->status == 5 && !$last->confirmed => RowState::DONE,
                $last->status == 5 && $last->confirmed  => RowState::LATE,
                default                                 => RowState::ASSIGNED,
            };
        }

        $daysLeft = $note->days_left !== null ? (int) $note->days_left : null;

        $assignee = null;

        if ($blocked) {
            $assignee = 'DESCONHECIDO';

            if ($last->User?->name) {
                $parts    = explode(' ', $last->User->name);
                $assignee = $parts[0] . ' ' . end($parts);
            }
        }

        return [
            'id'        => $note->id,
            'note'      => $note->note,
            'createdAt' => $note->dt_created,
            'order'     => mb_strtoupper((string) $note->numPedido),
            'rubrica'   => $note->rubrica,
            'city'      => $note->lexp,
            'group1'    => $note->group1,
            'material'  => $note->material,
            'pze'       => $note->pze,
            'statusAt'  => $note->dt_status,
            'prazoReal' => $daysLeft === null ? null : 30 - $daysLeft,
            'prazoTone' => $this->prazoRealTone($daysLeft),
            'prazoHint' => $this->prazoRealHint($daysLeft),
            'parecer'   => $this->parecerBadge($note->pze_parecer),
            'state'     => $state,
            'assignee'  => $assignee,
        ];
    }

    public function render()
    {
        return view('livewire.services.cadastro.main', $this->listViewData());
    }
}
