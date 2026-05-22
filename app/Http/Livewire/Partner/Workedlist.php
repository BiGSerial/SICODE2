<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\WorkInformsExport;
use App\Models\Edp_depc\City;
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
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
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
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = WorkReport::query();


        // $query->where('rejected', false);


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

        if (isset($this->filter['city'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        if (isset($this->filter['rubrica'])) {
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
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
