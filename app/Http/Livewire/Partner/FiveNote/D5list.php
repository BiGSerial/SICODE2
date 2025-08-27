<?php

namespace App\Http\Livewire\Partner\FiveNote;

use App\Helpers\TextFormatter;
use App\Models\EvidenceFile;
use App\Models\FiveNote;
use App\Traits\WildcardFormmater;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class D5list extends Component
{
    use TextFormatter;
    use WildcardFormmater;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';


    public $paginate = 15;
    public $multiSearch = '';
    public $multipleSearch = [];
    public $search = '';
    public $month;
    public $startDate;
    public $endDate;
    public $charged = null;

    public $archives;

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
        return FiveNote::where('visible_partner', true)
            ->where('is_completed', false)
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
            ->orderBy('created_at', 'desc');
    }

    public function downloadFile(EvidenceFile $file)
    {
        // dd(Storage::fileExists('public/'.$file->path));

        if (Storage::fileExists('public/'.$file->path)) {
            return Storage::download('public/'.$file->path);
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

    public function deleteFile(EvidenceFile $file)
    {
        if ($file) {
            $file->delete();
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Arquivo removido com sucesso!',
            ]);
            $this->emit('refreshComponent');
        }
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

    public function chargeFiles(FiveNote $five)
    {
        $this->charged = $five->load('EvidenceFiles');
        $this->emitSelf('refresh_component');
    }



    public function multiSearch()
    {
        $this->resetPage();
        $this->search = '';
        $this->multipleSearch = $this->formatTextToArray($this->multiSearch);
    }

    public function render()
    {
        return view('livewire.partner.five-note.d5list', [
            'fives' => $this->fives->paginate($this->paginate),
        ]);
    }
}
