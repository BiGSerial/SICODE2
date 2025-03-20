<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Repositories\Production\ProductionRepository;
use Livewire\Component;
use Livewire\WithPagination;

class Stack2 extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $last_update;

    private $filter_group = 'survey';
    private $filter;

    private $productionRepository;

    public function boot(ProductionRepository $productionRepository)
    {
        $this->productionRepository = $productionRepository;
    }

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
        $this->last_update = (Note::select('dt_status')->OrderBy('dt_status', 'DESC')->first())->dt_status;
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = $this->productionRepository->getBaseQuery();

        $query->where('service_id', $this->service->id);


        if ($this->filter) {
            if (isset($this->filter['city'])) {
                $query->whereRelation('note', function ($q) {
                    $q->whereIn('nexp', $this->filters['city']);
                });
            }

            if (isset($this->filter['company'])) {
                $query->whereIn('company_id', $this->filters['company']);
            }
        }


        return $query->with(['wpas' => function ($q) {
            $q->orderBy('created_at', 'DESC')->first();
        }, 'note', 'company', 'service', 'user', 'dispatcher', 'att', 'analise', 'reclaim', 'transfer', 'files', 'd5Return']);


    }

    public function render()
    {
        return view('livewire.dispatchs.survey.stack2', [
            'lists' => $this->lists->paginate(50)
        ]);
    }
}
