<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Models\Bancoupdate;
use App\Models\Note;
use App\Models\Service;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Historic extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $service;
    public $search;
    public $typeNote;
    public $dtIn;
    public $dtOut;

    // Filters
    private $filter_group = 'oexterno';
    private $filter;


    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        // $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

    }

    public function getNotesProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Note::Query();

        $query->has('External')->whereRelation('External', 'user_id', Auth()->User()->id);

        if (trim($this->search)) {
            $query->where(function ($q) {
                $q->where('note', 'like', "%" . $this->search . "%")
                    ->orWhereRelation('External.Protocols', 'protocol', 'like', "%" . $this->search . "%");
            });
        }

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        if (isset($this->filter['rubrica'])) {

            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['city'])) {

            $query->whereIn('lexp', $this->filter['city']);
        }

        if ($this->dtIn || $this->dtOut) {
            if ($this->dtIn && !$this->dtOut) {
                $startOfDay = Carbon::parse($this->dtIn)->startOfDay();
                $query->whereRelation('External', function ($q) use ($startOfDay) {
                    $q->whereDate('updated_at', '>=', $startOfDay);
                });
            }

            if (!$this->dtIn && $this->dtOut) {
                $endOfDay = Carbon::parse($this->dtOut)->endOfDay();
                $query->whereRelation('External', function ($q) use ($endOfDay) {
                    $q->whereDate('updated_at', '<=', $endOfDay);
                });
            }

            if (!$this->dtIn && $this->dtOut) {
                $startOfDay = Carbon::parse($this->dtIn)->startOfDay();
                $endOfDay = Carbon::parse($this->dtOut)->endOfDay();
                $query->whereRelation('External', function ($q) use ($endOfDay, $startOfDay) {
                    $q->whereBetween('updated_at', [$startOfDay, $endOfDay]);
                });
            }
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.services.oexterno.historic', [
            'lists' => $this->notes->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
