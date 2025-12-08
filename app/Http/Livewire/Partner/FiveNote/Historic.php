<?php

namespace App\Http\Livewire\Partner\FiveNote;

use App\Helpers\TextFormatter;
use App\Jobs\ExportFiveNotesJob;
use App\Models\FiveNote;
use App\Traits\WildcardFormmater;
use Livewire\Component;
use Livewire\WithPagination;

class Historic extends Component
{
    use TextFormatter;
    use WildcardFormmater;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';


    public $perPage = 25;
    public $multiSearch = '';
    public $multipleSearch = [];
    public $search = '';
    public $month;
    public $startDate;
    public $endDate;
    public $passiveFilter = 'current';

    protected $updatesQueryString = [
        'search' => ['except' => ''],
        'month' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
    ];

    protected $queryString = [

        'multipleSearch' => ['except' => ''],
    ];

    protected $listeners = [
        'refresh_component' => '$refresh',
    ];

    public function getFivesProperty()
    {
        $query = FiveNote::query();

        if (!auth()->user()->superadm) {
            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                        ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }

        $query->where('visible_partner', true)
            ->where('is_completed', true)
            ->when($this->passiveFilter === 'current', fn ($q) => $q->where('isPassive', false))
            ->when($this->passiveFilter === 'passive', fn ($q) => $q->where('isPassive', true))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {

                    $search = $this->formatWithWildcard($this->search);

                    $q->where('note_d5', $search->type, $search->search)
                        ->orWhere('pep', $search->type, $search->search)
                        ->orWhere('loc_install', $search->type, $search->search)
                        ->orWhereRelation('Note', function ($q) use ($search) {
                            $q->where('note', $search->type, $search->search);
                        })
                        ->orWhereRelation('Note.Orders', function ($q) use ($search) {
                            $q->where('ordem', $search->type, $search->search);
                        });
                });
            })
            ->when($this->multipleSearch, function ($query) {
                $query->where(function ($q) {
                    foreach ($this->multipleSearch as $item) {
                        $search = $this->formatWithWildcard($item);
                        $q->orWhere('note_d5', $search->type, $search->search)
                        ->orWhere('pep', $search->type, $search->search)
                        ->orWhere('loc_install', $search->type, $search->search)
                        ->orWhereRelation('Note', function ($q) use ($search) {
                            $q->where('note', $search->type, $search->search);
                        })
                        ->orWhereRelation('Note.Orders', function ($q) use ($search) {
                            $q->where('ordem', $search->type, $search->search);
                        });
                    }
                });
            })
            ->when($this->startDate, function ($query) {
                $query->whereDate('dispatch_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query) {
                $query->whereDate('dispatch_at', '<=', $this->endDate);
            })
            ->when($this->month, function ($query) {
                $query->whereMonth('dispatch_at', $this->month);
            })
            ->orderBy('completed_at', 'desc')
            ->orderBy('id');

        return $query;
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->multipleSearch = [];
        $this->multiSearch = '';
    }

    public function toSearch()
    {
        $this->resetPage();
        $this->multipleSearch = [];
        $this->multiSearch = '';
    }


    public function toClean()
    {
        $this->resetPage();
        $this->multipleSearch = [];
        $this->multiSearch = '';
        $this->month = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->search = '';
    }

    public function updatedPassiveFilter()
    {
        $this->resetPage();
    }

    public function multiSearch()
    {
        $this->resetPage();
        $this->search = '';
        $this->multipleSearch = $this->formatTextToArray($this->multiSearch);
        $this->dispatchBrowserEvent('hideModal');
    }

    public function exportExcel(): void
    {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        ExportFiveNotesJob::dispatch($this->exportPayload(), $userId, 'historic');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO INICIADA',
            'text'     => 'Você receberá uma notificação com o link para download.',
            'timer'    => 5000,
        ]);
    }

    protected function exportPayload(): array
    {
        return [
            'search'         => $this->search,
            'multipleSearch' => $this->multipleSearch,
            'month'          => $this->month,
            'startDate'      => $this->startDate,
            'endDate'        => $this->endDate,
            'passiveFilter'  => $this->passiveFilter,
        ];
    }

    public function render()
    {
        return view('livewire.partner.five-note.historic', [
            'fives' => $this->fives->paginate($this->perPage),
        ]);
    }
}
