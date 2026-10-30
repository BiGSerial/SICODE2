<?php

namespace App\Http\Livewire\Engineers;

use App\Services\Engineers\PartialApprovalQueue;
use Carbon\Carbon;
use Livewire\{Component, WithPagination};

/**
 * Informes parciais aguardando aprovação da Engenharia. A tela só orquestra filtros; consulta, indicadores e saldo de PEP
 * vêm de PartialApprovalQueue.
 */
class PartialList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search;

    public $perPage = 25;

    public $dt_in;

    public $dt_out;

    public $month;

    // Filters
    private $filter_group = 'partial';

    protected $queryString = [
        'search' => ['except' => ''],
        'dt_in'  => ['except' => '', 'as' => 'in'],
        'dt_out' => ['except' => '', 'as' => 'out'],
    ];

    protected $listeners = [
        'refresh'      => '$refresh',
        'refresh_list' => '$refresh',
    ];

    public function pesquisar()
    {
        $this->resetPage();
    }

    public function updatedMonth()
    {
        if ($this->month) {
            $this->dt_in  = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->dt_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        }

        $this->resetPage();
    }

    public function clearLocalFilters()
    {
        $this->reset(['search', 'month', 'dt_in', 'dt_out']);
        $this->resetPage();
    }

    private function queue(): PartialApprovalQueue
    {
        return app(PartialApprovalQueue::class);
    }

    private function baseQuery()
    {
        $filters = session('filter.' . $this->filter_group, []);

        return $this->queue()->query(auth()->user(), [
            'search'  => $this->search,
            'from'    => $this->dt_in,
            'to'      => $this->dt_out,
            'rubrica' => $filters['rubrica'] ?? null,
        ]);
    }

    public function render()
    {
        $query = $this->baseQuery();
        $lists = (clone $query)->with(['Note', 'Company', 'Orders', 'user', 'engineer', 'supervisor', 'payer'])->orderBy('created_at')->paginate($this->perPage);

        return view('livewire.engineers.partial-list', [
            'lists'      => $lists,
            'summary'    => $this->queue()->summary($query),
            'shortCount' => $this->queue()->shortCount($query),
            'pep'        => $this->queue()->pepStates($lists->getCollection()),
        ]);
    }
}
