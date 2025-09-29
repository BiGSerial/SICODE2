<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\MedProtest;
use App\Traits\WildcardFormmater;
use Livewire\Component;
use Livewire\WithPagination;

class Accompany extends Component
{
    use WildcardFormmater;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search = '';
    public $dt_start;
    public $dt_end;
    public $month;

    protected $queryString = [
        'page' => ['except' => 1],
    ];

    public function getListProperty()
    {
        return MedProtest::query()
                    // garante que há assignment do usuário, em monitoramento e não concluída
                    ->whereHas('Assignments', function ($q) {
                        $q->where('user_id', auth()->id())
                        ->where('monitoring', true)
                        ->where('completed', false);
                    })
                    // join para permitir ordenar pelo due_at da assignment do usuário
                    ->join('user_assignments as ua', function ($join) {
                        $join->on('ua.assignable_id', '=', 'med_protests.id')
                            ->where('ua.assignable_type', '=', (new \App\Models\MedProtest())->getMorphClass())
                            ->where('ua.user_id', '=', auth()->id())
                            ->where('ua.monitoring', '=', true)
                            ->where('ua.completed', '=', false);
                    })
                    ->with([
                        'Protest',
                        'Assignments.user',    // se quiser, pode restringir para o usuário atual via with + constraint
                        'Comments.user',
                        'Notes',
                    ])
                    ->when($this->search, function ($q) {

                        $term = $this->formatWithWildcard($this->search);
                        $q->where(function ($q) use ($term) {
                            $q->whereHas('Protest', function ($q) use ($term) {
                                $q->where('nota', $term->type, $term->search)
                                  ->orWhere('txtGrpCodificacao', $term->type, $term->search);
                            })->whereHas('Protest.Notes', function ($q) use ($term) {
                                $q->where('note', $term->type, $term->search)
                                  ->orWhere('material', $term->type, $term->search);
                            });
                        });
                    })
                    ->when($this->dt_start, function ($q) {
                        $q->whereDate('ua.started_at', '>=', $this->dt_start);
                    })
                    ->when($this->dt_end, function ($q) {
                        $q->whereDate('ua.started_at', '<=', $this->dt_end);
                    })
                    ->when($this->month, function ($q) {
                        $q->whereMonth('ua.started_at', $this->month)
                          ->whereYear('ua.started_at', now()->year);
                    })
                    ->select('med_protests.*')
                    ->distinct()              // evita duplicatas caso haja mais de uma assignment que case
                    ->orderByDesc('ua.started_at')
                    ->paginate($this->perPage);
    }

    public function clearFilters()
    {
        $this->reset(['dt_start', 'dt_end', 'month']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.protests.services.accompany', [
            'list' => $this->list,
        ]);
    }
}
