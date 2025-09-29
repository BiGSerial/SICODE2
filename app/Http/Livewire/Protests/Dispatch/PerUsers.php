<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\MedProtest;
use App\Models\UserAssignment;
use App\Traits\WildcardFormmater;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Component;
use Livewire\WithPagination;

class PerUsers extends Component
{
    use WildcardFormmater;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search = '';
    public $dt_start;
    public $dt_end;
    public $month;

    // TODO: Terminar de Acertar a lista de Usuarios.
    public function getListQueryBase()
    {
        return UserAssignment::where('completed', false)
                        ->with([
                'user:id,name',
                'assignable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        MedProtest::class => [
                            'Protest:id,nota,txtGrpCodificacao',  // sem protest_id
                            'Notes:id,note,material',
                        ],
                    ]);
                },
            ]);

    }

    public function getListProperty()
    {
        return $this->getListQueryBase()->orderBy('started_at', 'desc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.protests.dispatch.per-users', [
            'list' => $this->list,
        ]);
    }
}
