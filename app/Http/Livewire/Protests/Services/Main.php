<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\MedProtest;
use App\Traits\WildcardFormmater;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WildcardFormmater;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search = '';
    public $dt_start;
    public $dt_end;
    public $month;

    public function getListProperty()
    {
        return MedProtest::WhereHas('assignments', function ($q) {
            $q->where('user_id', auth()->id())
                ->where('user', true)
                ->where('completed', false);
        })
                    // join para permitir ordenar pelo due_at da assignment do usuário
                    ->join('user_assignments as ua', function ($join) {
                        $join->on('ua.assignable_id', '=', 'med_protests.id')
                            ->where('ua.assignable_type', '=', (new \App\Models\MedProtest())->getMorphClass())
                            ->where('ua.user_id', '=', auth()->id())
                            ->where('ua.user', '=', true)
                            ->where('ua.completed', '=', false);
                    })
                    ->with([
                        'Protest',
                        'Assignments.user',    // se quiser, pode restringir para o usuário atual via with + constraint
                        'Comments.user',
                        'Notes',
                    ])
                    ->select('med_protests.*')
                    ->distinct()              // evita duplicatas caso haja mais de uma assignment que case
                    ->orderByDesc('ua.started_at')
                    ->paginate();
    }

    public function render()
    {
        return view('livewire.protests.services.main', [
            'list' => $this->list,
        ]);
    }
}
