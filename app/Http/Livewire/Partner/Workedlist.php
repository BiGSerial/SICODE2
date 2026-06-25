<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\WorkInformsExport;
use App\Models\Edp_depc\City;
use App\Models\Company;
use App\Models\File;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Workedlist extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $search;

    public $multiSearch;

    public $companyId = '';

    public $companyOptions = [];

    // search by date
    public $month;
    public $date_in;
    public $date_out;
    // public $dateBy = 'sended_at';

    // Filters
    private $filter_group = 'partner_forms';

    private $filter;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'month'   => ['except' => '', 'as' => 'mes_referencia'],
        'companyId' => ['except' => '', 'as' => 'empreiteira'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'refresh_filter' => 'refreshFilters',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
        $this->companyOptions = $this->loadCompanyOptions();
        // $this->month = !$this->month ? Carbon::now()->format('Y-m') : $this->month;
        // $this->date_in = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        // $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
    }

    public function exportToExcel()
    {
        return (new WorkInformsExport($this->lists))
            ->download(date('Ymd_his').'-ListaObrasInformadas.xlsx');
    }

    public function updatedMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->month);
        $this->date_in = $date->startOfMonth()->format('Y-m-d');
        $this->date_out = $date->endOfMonth()->format('Y-m-d');
    }

    public function updatedDateIn()
    {
        $this->month = Carbon::parse($this->date_in)->format('Y-m');
    }

    public function cleanAll()
    {
        $this->search = '';
        $this->multiSearch = '';
        $this->date_in = '';
        $this->date_out = '';
        $this->month = '';
        $this->companyId = '';
    }

    public function applyMultiSearch()
    {
        $terms = $this->parseSearchTerms($this->multiSearch);

        $this->search = implode(' ', $terms);
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedCompanyId()
    {
        $this->resetPage();
    }

    public function refreshFilters(...$payload): void
    {
        $this->resetPage();
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }
    }

    public function getListsProperty()
    {
        $this->filter = $this->loadFilters();

        $query = WorkReport::query();


        // $query->where('rejected', false);


        $visibleCompanyIds = $this->visibleCompanyIds();

        if (!auth()->user()->superadm) {
            if ($visibleCompanyIds->isNotEmpty()) {
                $query->whereIn('company_id', $visibleCompanyIds->all());
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($this->companyId) {
            if (auth()->user()->superadm || $visibleCompanyIds->contains($this->companyId)) {
                $query->where('company_id', $this->companyId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }


        if (($this->date_in || $this->date_out)) {

            if ($this->date_in && !$this->date_out) {
                $query->whereDate('informed_at', '>=', $this->date_in);
            }

            if (!$this->date_in && $this->date_out) {
                $query->whereDate('informed_at', '<=', $this->date_out);
            }

            if ($this->date_in && $this->date_out) {
                $query->whereDate('informed_at', '>=', $this->date_in)
                    ->whereDate('informed_at', '<=', $this->date_out);
            }
        }

        $searchTerms = $this->parseSearchTerms($this->search);

        if (!empty($searchTerms)) {
            $query->where(function ($q) {
                foreach ($this->parseSearchTerms($this->search) as $term) {
                    $q->orWhereRelation('Note', 'note', 'like', "%{$term}%")
                        ->orWhereRelation('Note', 'numPedido', 'like', "%{$term}%")
                        ->orWhereRelation('Orders', 'ordem', 'like', "%{$term}%");
                }
            });
        }

        if (!empty($this->filter['city'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        if (!empty($this->filter['region'])) {
            $query->whereRelation('Note.City', function ($q) {
                $q->whereIn('regiao', $this->filter['region']);
            });
        }

        if (!empty($this->filter['rubrica'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        $query->with(['Note.Files', 'Note.OldAds' => function ($q) {
            $q->orderBy('date', 'asc');
        }, 'Orders', 'Equipment', 'Company', 'Adsform']);

        $query->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id');

        return $query;
    }

    private function loadFilters(): array
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $sessionFilters = session('filter.' . $this->filter_group);
        if (is_array($sessionFilters)) {
            return $sessionFilters;
        }

        if (isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            return $_SESSION['filter'][$this->filter_group];
        }

        return [];
    }

    private function parseSearchTerms(?string $value): array
    {
        if (!filled($value)) {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', trim($value)))
            ->map(fn ($term) => trim($term))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.partner.workedlist', [
            'lists' => $this->lists->paginate($this->perPage),
            'companyOptions' => $this->companyOptions,
        ]);
    }

    private function loadCompanyOptions()
    {
        $query = Company::query();

        if (!auth()->user()->superadm) {
            $companyIds = $this->visibleCompanyIds();

            if ($companyIds->isEmpty()) {
                return collect();
            }

            $query->whereIn('id', $companyIds->all());
        }

        return $query
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name']);
    }

    private function visibleCompanyIds()
    {
        $user = auth()->user();

        if (!$user) {
            return collect();
        }

        return collect()
            ->merge($user->Companies?->pluck('id') ?? [])
            ->push($user->company_id)
            ->push($user->Employee?->Contract?->company_id)
            ->filter()
            ->unique()
            ->values();
    }
}
