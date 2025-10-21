<?php

namespace App\Http\Livewire\Partner;

use App\Exports\parner\exportExcel;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use App\Models\Viability;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use ZipArchive;

class Todoviability extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** @var int */
    public $perPage = 50;

    /** @var \Illuminate\Support\Collection */
    public $cities;

    /** @var array<int> */
    public $files_selected = [];

    /** @var array<int> */
    public $inActivity = [];

    /** @var string|null */
    public $search = '';

    // Grupo de filtros usado pelos componentes de filtro
    private string $filter_group = 'partner';

    /** @var array<string, mixed>|null */
    private $filter = null;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount(): void
    {
        // Só o que a view realmente usa
        $this->cities = City::query()
            ->select(['rdMunicipio', 'regiao', 'cidade'])
            ->orderBy('cidade')
            ->get();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function export_excel()
    {
        // Evita paginação: pega tudo da query atual
        $rows = $this->lists()->get();
        return (new exportExcel($rows))->download(now()->format('YmdHis') . '-exportViabilityParner.xlsx');
    }

    public function downloadFile(int $id)
    {
        $file = File::find($id);
        if (!$file) {
            return;
        }

        if (Storage::disk('local')->exists($file->path)) {
            return Storage::download($file->path, $file->file_name);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO INEXISTENTE!',
            'timer'    => 5000,
        ]);
    }

    public function openForms(int $id)
    {
        if ($id) {
            return redirect()->route('forms.viability', ['id' => Crypt::encrypt($id)]);
        }
    }

    public function downloadZip()
    {
        if (!count($this->files_selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum Arquivo foi selecionado para Download',
                'timer'    => 5000,
            ]);
            return;
        }

        $files = File::find($this->files_selected);
        if (!$files || !$files->count()) {
            return;
        }

        $zipFile = 'Arquivos-Lote-' . hash('crc32', microtime(true)) . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            if (Storage::exists($file->path)) {
                $content = Storage::get($file->path);
                $zip->addFromString($file->file_name . '.' . $file->ext, $content);
            }
        }

        $zip->close();
        $this->files_selected = [];

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    /** Atualiza o array de IDs de notas “em atividade” do usuário atual */
    public function inActivityUpdate(): array
    {
        $this->inActivity = Note::query()
            ->whereHas('Viabilities', function ($q) {
                $q->where('canceled', false)
                  ->where('inActivity', true)
                  ->where('completed', false)
                  ->where('tacit', false);

                if (!Auth::user()->superadm) {
                    $companyId = optional(Auth::user()->Employee->Contract)->Company->id
                              ?? optional(Auth::user()->Company)->id;
                    $q->when($companyId, fn ($qq) => $qq->where('company_id', $companyId));
                }
            })
            ->pluck('id')
            ->toArray();

        return $this->inActivity;
    }

    public function putInActivity(int $id): void
    {
        if ($viab = Viability::find($id)) {
            $viab->inActivity = !$viab->inActivity;
            $viab->save();
        }
    }

    public function checkInActivity($item): bool
    {
        return (bool)($item->inActivity ?? false);
    }

    /**
     * Retorna a query base (NÃO pagina).
     * Use $this->lists()->paginate($this->perPage) no render.
     */
    protected function lists()
    {
        // filtros vindos dos components de filtro (guardados em sessão)
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        } if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }




        $user = Auth::user();

        $query = Viability::query()
            ->where('canceled', false)
            ->where('completed', false)
            ->where('tacit', false)
            ->where('rejected', false)
            ->where('visible_partner', false); // mantive sua regra original

        // Escopo por empresa (não-superadm)
        if (!$user->superadm) {
            $companyIds = $user->Companies?->pluck('id')->all() ?? [];
            $ownCompany = $user->Company?->id;

            $query->where(function ($q) use ($companyIds, $ownCompany) {
                if (!empty($companyIds)) {
                    $q->whereIn('company_id', $companyIds);
                }
                if ($ownCompany) {
                    $q->orWhere('company_id', $ownCompany);
                }
            });
        }

        // Eager loading enxuto (somente colunas usadas na view)
        $query->with([
            'Note:id,client,material,rubrica,txpriority,lexp,note,is45',
            'Note.Orders:id,note_id,ordem,statusSist',
            'Company:id,name',
            'Files:id,note_id,file_name,ext,path',
            'comments:id,user_id,message,created_at',
            'comments.User:id,name,email',
            'Form:id,viability_id,reason,changes,responsible,description',
            'Engineer:id,name,email',
        ]);

        // Busca textual
        if (filled($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->whereHas('Note', fn ($qq) => $qq->where('note', 'like', "%{$s}%"))
                  ->orWhereHas('Note.Orders', fn ($qq) => $qq->where('ordem', 'like', "%{$s}%"));
            });
        }

        // Filtros por criticidade
        if (isset($this->filter['txpriority']) && is_array($this->filter['txpriority']) && count($this->filter['txpriority'])) {
            $txpriorities = $this->filter['txpriority'];
            $query->whereHas('Note', fn ($qq) => $qq->whereIn('txpriority', $txpriorities));
        }

        // Filtros por rubrica
        if (isset($this->filter['rubrica']) && is_array($this->filter['rubrica']) && count($this->filter['rubrica'])) {
            $rubricas = $this->filter['rubrica'];
            $query->whereHas('Note', fn ($qq) => $qq->whereIn('rubrica', $rubricas));
        }

        // Filtro por cidade (lexp)
        if (isset($this->filter['city']) && is_array($this->filter['city']) && count($this->filter['city'])) {
            $cities = $this->filter['city'];
            $query->whereHas('Note', fn ($qq) => $qq->whereIn('lexp', $cities));
        }

        // Ordenação: is45 desc (nota expressa), depois sended_at asc
        $query->leftJoin('notes', 'notes.id', '=', 'viabilities.note_id')
              ->orderByDesc('notes.is45')
              ->orderBy('viabilities.sended_at', 'asc')
              ->orderBy('id', 'asc')
              ->select('viabilities.*');


        return $query;
    }

    public function render()
    {
        $this->inActivityUpdate();

        return view('livewire.partner.todoviability', [
            'lists'  => $this->lists()->paginate($this->perPage),
            'cities' => $this->cities,
        ]);
    }
}
