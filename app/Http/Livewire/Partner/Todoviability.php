<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\ExportViabilityToExcel;
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
use Maatwebsite\Excel\Facades\Excel;

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

    /**
     * Grupo chave dos filtros (componentes filhos salvam na sessão com essa chave)
     */
    private string $filter_group = 'partner';

    /** @var array<string,mixed>|null */
    private $filter = null;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1,  'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount(): void
    {
        // só o necessário pra view de filtros
        $this->cities = City::query()
            ->select(['rdMunicipio', 'regiao', 'cidade'])
            ->orderBy('cidade')
            ->get();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Exporta o EXCEL da listagem atual (sem paginação)
     */
    public function export_excel()
    {
        $rows = $this->listsQuery()->get();

        $fileName = now()->format('YmdHis') . '-exportViabilityPartner.xlsx';

        return Excel::download(new ExportViabilityToExcel($rows), $fileName);
    }

    /**
     * Download individual de arquivo
     */
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

    /**
     * Abre checklist FTVEO
     */
    public function openForms(int $id)
    {
        if ($id) {
            return redirect()->route('forms.viability', ['id' => Crypt::encrypt($id)]);
        }
    }

    /**
     * Download ZIP com arquivos selecionados
     */
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
                // usa nome.ext pra ficar igual à visualização
                $zip->addFromString($file->file_name . '.' . $file->ext, $content);
            }
        }

        $zip->close();
        $this->files_selected = [];

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    /**
     * Atualiza os IDs de notas em atividade (checkbox "em atividade")
     */
    public function inActivityUpdate(): array
    {
        $user = Auth::user();

        $this->inActivity = Note::query()
            ->whereHas('Viabilities', function ($q) use ($user) {
                $q->where('canceled', false)
                  ->where('inActivity', true)
                  ->where('completed', false)
                  ->where('tacit', false);

                // escopo por empresa se não for superadm
                if (!$user->superadm) {
                    $companyId = optional($user->Employee->Contract)->Company->id
                              ?? optional($user->Company)->id;
                    $q->when($companyId, fn ($qq) => $qq->where('company_id', $companyId));
                }
            })
            ->pluck('id')
            ->toArray();

        return $this->inActivity;
    }

    /**
     * Alterna inActivity direto na viabilidade
     */
    public function putInActivity(int $id): void
    {
        if ($viab = Viability::find($id)) {
            $viab->inActivity = !$viab->inActivity;
            $viab->save();
        }
    }

    /**
     * Helper pra checkbox
     */
    public function checkInActivity($item): bool
    {
        return (bool)($item->inActivity ?? false);
    }

    /**
     * Lê filtros salvos pelos componentes de filtro (na sessão nativa PHP)
     */
    protected function getFilterFromSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        } else {
            $this->filter = null;
        }
    }

    /**
     * Monta a query base SEM paginação e SEM orderBy/select final.
     * Aqui vai:
     *  - regras de visibilidade
     *  - eager loading mínimo
     *  - busca textual
     *  - filtros avançados
     */
    protected function baseQuery()
    {
        $this->getFilterFromSession();

        $user = Auth::user();

        $query = Viability::query()
            ->where('viabilities.canceled', false)
            ->where('viabilities.completed', false)
            ->where('viabilities.tacit', false)
            ->where('viabilities.rejected', false)
            ->where('viabilities.visible_partner', false); // regra original mantida

        // Se não é superadm, restringe por empresa:
        if (!$user->superadm) {
            $companyIds = $user->Companies?->pluck('id')->all() ?? [];
            $ownCompany = $user->Company?->id;

            $query->where(function ($q) use ($companyIds, $ownCompany) {
                if (!empty($companyIds)) {
                    $q->whereIn('viabilities.company_id', $companyIds);
                }
                if ($ownCompany) {
                    $q->orWhere('viabilities.company_id', $ownCompany);
                }
            });
        }

        // Carrega só o que a view usa
        $query->with([
            'Note:id,client,material,rubrica,txpriority,lexp,note,is45,days_left,mesalization,type_note',
            'Note.Orders:id,note_id,ordem,statusSist',
            'Note.Files:id,note_id,service_id,file_name,ext,path', // só pra contar arquivos via relacionamento de Note->Files
            'Company:id,name',
            'Files:id,note_id,file_name,ext,path',
            'comments:id,user_id,message,created_at',
            'comments.User:id,name,email',
            'Form:id,viability_id,reason,changes,responsible,description',
            'Engineer:id,name,email',
            'Note.City:id,regiao,cidade', // pra região/município
        ]);

        // Busca rápida nota / OV (ordem)
        if (filled($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->whereHas('Note', fn ($qq) => $qq->where('note', 'like', "%{$s}%"))
                  ->orWhereHas('Note.Orders', fn ($qq) => $qq->where('ordem', 'like', "%{$s}%"));
            });
        }

        // Filtro Criticidade (txpriority)
        if (!empty($this->filter['txpriority']) && is_array($this->filter['txpriority'])) {
            $txpriorities = $this->filter['txpriority'];
            $query->whereHas('Note', fn ($qq) => $qq->whereIn('txpriority', $txpriorities));
        }

        // Filtro Rubrica
        if (!empty($this->filter['rubrica']) && is_array($this->filter['rubrica'])) {
            $rubricas = $this->filter['rubrica'];
            $query->whereHas('Note', fn ($qq) => $qq->whereIn('rubrica', $rubricas));
        }

        // Filtro Cidade (lexp)
        if (!empty($this->filter['city']) && is_array($this->filter['city'])) {
            $cities = $this->filter['city'];
            $query->whereHas('Note', fn ($qq) => $qq->whereIn('lexp', $cities));
        }

        return $query;
    }

    /**
     * Aplica ordenação e select final (espelha a tabela Blade)
     * NÃO pagina aqui.
     */
    protected function listsQuery()
    {
        return $this->baseQuery()
            ->leftJoin('notes', 'notes.id', '=', 'viabilities.note_id')
            ->orderByDesc('notes.is45')
            ->orderBy('viabilities.sended_at', 'asc')
            ->orderBy('viabilities.id', 'asc')
            ->select('viabilities.*');
    }

    public function render()
    {
        // atualiza quem está "em atividade"
        $this->inActivityUpdate();

        $paginated = $this->listsQuery()->paginate($this->perPage);

        return view('livewire.partner.todoviability', [
            'lists'  => $paginated,
            'cities' => $this->cities,
        ]);
    }
}
