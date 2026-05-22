<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Models\Bancoupdate;
use App\Models\External;
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

    public function navigateTo($note)
    {
        return redirect()->to(
            route('services.protocolNote', [
                'service' => $this->service->uuid,
                'note'    => $note,
            ])
        );
    }


    public function getNotesProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = External::query()
            ->where('completed', true)
            ->with([
                'Note:id,note,rubrica,lexp,centerjob,type_note,nstats',
                'Entity:id,name,nick,entity_type_id',
                'Entity.Type:id,name',
                'User:id,name',
                'Protocols:id,external_id,protocol,created_at',
            ]);

        if ($term = trim((string) $this->search)) {
            $collection = collect(explode(' ', $term))->filter()->values();

            $query->where(function ($root) use ($collection) {
                foreach ($collection as $token) {
                    $wild = '%' . $token . '%';
                    $root->where(function ($q) use ($token, $wild) {
                        $q->where('externals.note_id', $token)
                            ->orWhereRelation('Note', 'note', 'like', $wild)
                            ->orWhereRelation('Protocols', 'protocol', 'like', $wild)
                            ->orWhereRelation('Entity', 'name', 'like', $wild)
                            ->orWhereRelation('Entity', 'nick', 'like', $wild);
                    });
                }
            });
        }

        if ($this->typeNote) {
            $query->whereHas('Note', function ($q) {
                $q->where('type_note', $this->typeNote);
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereHas('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city'])) {
            $query->whereHas('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        if ($this->dtIn || $this->dtOut) {
            $start = $this->dtIn ? Carbon::parse($this->dtIn)->startOfDay() : null;
            $end   = $this->dtOut ? Carbon::parse($this->dtOut)->endOfDay() : null;

            if ($start && $end) {
                $query->whereBetween('externals.updated_at', [$start, $end]);
            } elseif ($start) {
                $query->where('externals.updated_at', '>=', $start);
            } elseif ($end) {
                $query->where('externals.updated_at', '<=', $end);
            }
        }

        return $query->orderByDesc('externals.updated_at');
    }

    public function render()
    {
        return view('livewire.services.oexterno.historic', [
            'lists' => $this->notes->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
