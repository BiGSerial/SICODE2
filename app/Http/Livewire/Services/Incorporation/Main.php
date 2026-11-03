<?php

namespace App\Http\Livewire\Services\Incorporation;

use App\Http\Livewire\Services\Concerns\IsActivityNoteList;
use App\Models\Note;
use App\Support\Ops\RowState;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use IsActivityNoteList;

    protected $paginationTheme = 'bootstrap';

    protected function searchColumns(): array
    {
        return ['note', 'material', 'numPedido', 'group2'];
    }

    /** Linha da tela: com Produção aberta para o mesmo status e data da nota, a linha fica "Com usuário". */
    protected function rowFor(Note $note): array
    {
        $block = $note->Productions
            ->where('status_note', $note->nstats)
            ->where('dt_note', $note->dt_status)
            ->first();

        return [
            'id'        => $note->id,
            'note'      => $note->note,
            'createdAt' => $note->dt_created,
            'order'     => mb_strtoupper((string) $note->numPedido),
            'rubrica'   => $note->rubrica,
            'city'      => $note->lexp,
            'group1'    => $note->group1,
            'group2'    => $note->group2,
            'material'  => $note->material,
            'status'    => $note->nstats,
            'statusAt'  => $note->dt_status,
            'age'       => $this->createdAge($note),
            'parecer'   => $this->parecerBadge($note->pze_parecer),
            'state'     => $block ? RowState::ASSIGNED : RowState::FREE,
            'assignee'  => $block ? $this->shortAssigneeName($block) : null,
        ];
    }

    public function render()
    {
        return view('livewire.services.incorporation.main', $this->listViewData());
    }
}
