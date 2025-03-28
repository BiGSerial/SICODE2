<?php

namespace App\Http\Livewire\Partner;

use App\Models\File;
use App\Models\Partial;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class PartialList extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search;
    public $perPage = 50;

    public $dt_in;
    public $dt_out;

    protected $queryString = [
        'search' => ['except' => ''],
        'dt_in' => ['except' => '', 'as' => 'in'],
        'dt_out' => ['except' => '', 'as' => 'out'],
    ];

    public function pesquisar()
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
        $query = Partial::query();

        if (!auth()->user()->superadm) {
            $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
            ->orWhere('company_id', Auth()->user()->Company->id);
        }

        if ($this->search) {
            $query->whereRelation('Note', 'note', 'like', '%' . trim($this->search) . '%')
                    ->orWhereRelation('Note.Orders', 'ordem', 'like', '%' . trim($this->search) . '%');
        }


        if ($this->dt_in && !$this->dt_out) {
            $query->whereDate('created_at', '>=', $this->dt_in);
        } elseif ($this->dt_out && !$this->dt_in) {
            $query->whereDate('created_at', '<=', $this->dt_out);
        } elseif ($this->dt_in && $this->dt_out) {
            $query->whereBetween('created_at', [$this->dt_in, $this->dt_out]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function partialStatus(Partial $partial): array
    {
        $status = [
            'status' => '',
            'color' => '',
        ];

        if ($partial) {
            if ($partial->deny) {
                $status = [
                    'status' => 'REJEITADO',
                    'color' => 'text-bg-danger',
                ];
            } elseif ($partial->payment && $partial->allow) {
                $status = [
                    'status' => 'PAGO',
                    'color' => 'text-bg-success',
                ];
            } elseif ($partial->supervision && !$partial->payment) {
                $status = [
                    'status' => 'EM PAGAMENTO',
                    'color' => 'text-bg-info',
                ];
            } elseif ($partial->allow && !$partial->supervision) {
                $status = [
                    'status' => 'EM FISCALIZAÇÃO',
                    'color' => 'text-bg-info',
                ];
            } else {
                $status = [
                    'status' => 'AVALIAÇÃO',
                    'color' => 'text-bg-warning',
                ];
            }
        }

        return $status;
    }


    public function render()
    {
        return view('livewire.partner.partial-list', [
            'lists' => $this->lists
        ]);
    }
}
