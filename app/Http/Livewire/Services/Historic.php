<?php

namespace App\Http\Livewire\Services;

use App\Models\{File, Production, Service, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\{Component, WithPagination};

class Historic extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 50;

    public $search;
    public $file_search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $limit_pause = 3;

    public $analise;

    public $production;

    public $note;

    public $user_l;

    public $user_s;

    public $user_search;

    public $date_prod_l;

    public $date_prod_s;
    public $date_from;
    public $date_to;
    public $date_field = 'completed_at';

    public $multi_search_input = '';
    public $multi_search_terms = [];

    public $meses = [
        1  => 'Janeiro',
        2  => 'Fevereiro',
        3  => 'Março',
        4  => 'Abril',
        5  => 'Maio',
        6  => 'Junho',
        7  => 'Julho',
        8  => 'Agosto',
        9  => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    protected $listeners = [
        'getCopy' => 'copy',
        'refreshHistoric' => '$refresh',
        'refreshLists' => '$refresh',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'file_search' => ['except' => ''],
        'date_prod_s' => ['except' => ''],
        'date_from' => ['except' => ''],
        'date_to' => ['except' => ''],
        'date_field' => ['except' => 'completed_at'],
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
        $this->user_l = collect();
        $this->date_prod_l = collect();

        // $this->date_prod_l = Production::Where('service_id', $this->service->uuid)
        //                     ->where('user_id', Auth()->User()->id)
        //                     ->where('completed', true)
        //                     ->where('rejected', false)
        //                     ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as mes_ano, COUNT(*) as total')
        //                     ->groupBy('mes_ano')
        //                     ->orderBy('mes_ano')
        //                     ->get();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function visualizar()
    {

    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function applyMultiSearch()
    {
        $terms = preg_split('/[\s,;\n\r\t]+/', (string) $this->multi_search_input);
        $terms = collect($terms)
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();

        $this->multi_search_terms = $terms;
        if (count($terms) > 0) {
            $this->search = implode(', ', $terms);
        }
        $this->resetPage();
    }

    public function clearMultiSearch()
    {
        $this->multi_search_input = '';
        $this->multi_search_terms = [];
        $this->resetPage();
    }

    public function clearDateFilters()
    {
        $this->date_prod_s = null;
        $this->date_from = null;
        $this->date_to = null;
        $this->date_field = 'completed_at';
        $this->resetPage();
    }

    public function updated($name)
    {
        if (in_array($name, [
            'search',
            'file_search',
            'date_prod_s',
            'date_from',
            'date_to',
            'date_field',
            'user_s',
            'user_search',
        ], true)) {
            $this->resetPage();
        }
    }

    public function getListsProperty()
    {
        $dateField = in_array($this->date_field, ['completed_at', 'att_at', 'dispatch_at'], true)
            ? $this->date_field
            : 'completed_at';

        $this->date_prod_l = $this->buildDatePeriods();
        $this->user_l = $this->buildUsersList();

        $searchTerms = $this->buildSearchTerms();

        return Production::query()
            ->where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', true)
            ->where('rejected', false)
            ->when(count($searchTerms) > 0, function ($q) use ($searchTerms) {
                $q->where(function (Builder $nested) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $nested->orWhereRelation('Note', 'note', 'like', '%' . $term . '%')
                            ->orWhereRelation('Note', 'material', 'like', '%' . $term . '%');
                    }
                });
            })
            ->when($this->file_search, function ($q, $s) {
                $q->whereHas('Note.Files', function ($fq) use ($s) {
                    $fq->where('file_name', 'like', '%' . $s . '%');
                });
            })
            ->when($this->date_prod_s, function ($q) {
                $q->whereRaw('DATE_FORMAT(completed_at, "%Y-%m") = ?', [$this->date_prod_s]);
            })
            ->when($this->date_from, function ($q) use ($dateField) {
                $q->whereDate($dateField, '>=', $this->date_from);
            })
            ->when($this->date_to, function ($q) use ($dateField) {
                $q->whereDate($dateField, '<=', $this->date_to);
            })
            ->with(['Note' => function ($query) {
                $query->select(['id', 'note', 'rubrica', 'lexp', 'group1', 'material', 'nstats']);
            }, 'Note.Files:id,note_id,service_id,file_name,path,ext', 'Note.Files.Service:uuid,service',
                'Analise:production_id,conclusion'])
            ->select([
                'productions.id',
                'productions.note_id',
                'productions.service_id',
                'productions.user_id',
                'productions.status_note',
                'productions.completed',
                'productions.completed_at',
                'productions.att_at',
                'productions.stopped',
                'productions.confirmed',
                'productions.transferred',
                'productions.d5',
            ])
            ->selectSub(function ($q) {
                $q->from('productions as p2')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('p2.note_id', 'productions.note_id')
                    ->where('p2.completed', true)
                    ->whereColumn('p2.status_note', '>', 'productions.status_note');
            }, 'higher_confirmed_count')
            ->orderBy('completed_at', 'DESC')
            ->paginate($this->perPage);
    }

    private function buildSearchTerms(): array
    {
        $inlineTerms = preg_split('/[\s,;\n\r\t]+/', (string) $this->search);
        $inlineTerms = collect($inlineTerms)->map(fn ($term) => trim((string) $term))->filter();

        return $inlineTerms
            ->merge(collect($this->multi_search_terms ?? [])->map(fn ($term) => trim((string) $term)))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();
    }

    private function buildDatePeriods()
    {
        return Production::query()
            ->where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', true)
            ->where('rejected', false)
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as mes_ano, COUNT(*) as total')
            ->groupBy('mes_ano')
            ->orderByDesc('mes_ano')
            ->get();
    }

    private function buildUsersList()
    {
        if (!auth()->user()?->can('superadm')) {
            return collect();
        }

        return User::query()
            ->when($this->user_search, function ($q) {
                return $q->where('name', 'like', '%' . $this->user_search . '%');
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit($this->user_search ? 300 : 80)
            ->get();
    }

    public function render()
    {
        return view('livewire.services.historic', [
            'lists' => $this->lists,
        ]);
    }
}
