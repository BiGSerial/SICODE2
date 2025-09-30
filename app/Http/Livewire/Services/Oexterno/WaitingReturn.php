<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Helpers\TextFormatter;
use App\Models\Reclaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class WaitingReturn extends Component
{
    use WithPagination;
    use TextFormatter;

    public $perPage = 50;
    public $search = '';
    public $typeNote = '';
    public $service;


    private $filter_group = 'oexterno';
    private $filter;


    protected $paginationTheme = 'bootstrap';

    public function mount($service)
    {
        $this->service = $service;

    }

    protected $listeners = [
        'refresh_list' => '$refresh',
        'navigateTo',
    ];

    public function navigateTo($note)
    {
        if (!$this->service || !$note) {
            $this->dispatchBrowserEvent('toast', [
                'title' => 'Erro de navegação',
                'message' => 'Não foi possível navegar para a nota especificada.',
                'type' => 'error'
            ]);
            return;
        }


        return redirect()->to(
            route('services.protocolNote', [
                'service' => is_array($this->service) ? $this->service['uuid'] : $this->service,
                'note'    => $note,
            ])
        );
    }

    public function getListsProperty()
    {
        if (!session()->isStarted()) {
            session()->start();
        }
        $this->filter = session("filter.{$this->filter_group}", []);



        $query = Reclaim::query();

        $query->whereHas('Externals', function ($q) {
            $q->where('external_reclaim.completed', 0);
        });

        if (trim($this->search)) {
            $wildcard = str_contains($this->search, '*') || str_contains($this->search, '%')
                ? str_replace('*', '%', $this->search)
                : $this->search;

            $query->where(function ($q) use ($wildcard) {
                if (str_contains($wildcard, '%')) {
                    $q->whereRelation('externals.note', function ($q) use ($wildcard) {
                        $q->where('note', 'like', $wildcard);
                    });
                } else {
                    $q->whereRelation('externals.note', function ($q) use ($wildcard) {
                        $q->where('note', '=', $wildcard);
                    });
                }
            });
        }


        if (isset($this->filter['entities']) && count($this->filter['entities'])) {


            $query->whereRelation('externals', function ($q) {
                $q->whereIn('entity_id', $this->filter['entities']);
            });
        }


        if (isset($this->filter['rubrica']) && count($this->filter['rubrica'])) {
            $query->whereRelation('note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city']) && count($this->filter['city'])) {
            $query->whereRelation('note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }





        $query->with('externals.entity', 'note', 'service', 'production', 'comments', 'subcategory.category')
            ->orderBy('created_at', 'asc');

        return $query;
    }

    public function getColor($days)
    {
        if ($days > 9) {
            return 'text-bg-danger';
        } elseif ($days < 3) {
            return 'text-bg-success';
        } else {
            return 'text-bg-warning';
        }
    }



    public function render()
    {
        return view('livewire.services.oexterno.waiting-return', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);

    }
}
