<?php

namespace App\Http\Livewire\Services\Supervision;

use App\Jobs\Services\ExportSupervisionProductionListJob;
use App\Models\{File, Production, Service, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;           // Service (objeto)
    public $perPage = 30;
    public $search;

    public $rubrica_s = [];
    public $limit_pause = 50;

    public $user_l;            // lista de usuários p/ filtro
    public $user_s;            // usuário selecionado
    public $user_search;       // busca do usuário

    public $analise;
    private string $filter_group = 'supervision';
    private array $filter = [];

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'refresh_list'       => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
        'checkOpen',
    ];

    public function mount($service)
    {
        // Carrega o serviço e traz apenas os status úteis (sem exclusão)
        $this->service = Service::where('uuid', $service)
            ->with(['Status' => function ($q) {
                $q->where('exclusion', false)->select('service_id', 'value');
            }])
            ->firstOrFail();

        $this->loadFilters();
    }

    private function loadFilters(): void
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        $filters = session("filter.{$this->filter_group}", []);
        if ((!is_array($filters) || $filters === []) && isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            $filters = $_SESSION['filter'][$this->filter_group];
        }

        $this->filter = is_array($filters) ? $filters : [];
    }

    public function exportToExcel()
    {
        ExportSupervisionProductionListJob::dispatch([
            'service_uuid'    => $this->service->uuid,
            'request_user_id' => auth()->id(),
            'target_user_id'  => $this->user_s ?: auth()->id(),
            'search'          => $this->search,
        ]);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'info',
            'title'    => 'Exportação iniciada!',
            'text'     => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    public function export_excel()
    {
        return $this->exportToExcel();
    }

    public function goTransferProd($prod_id)
    {
        $this->emit('transfer_production_lev', $prod_id);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function checkOpen()
    {
        $check = Production::where('service_id', $this->service->uuid)
            ->where('user_id', auth()->id())
            ->where('status', 3) // em atividade
            ->select('id', 'note_id', 'status')
            ->first();

        if ($check) {
            $this->emitTo('services.supervision.forms.jobform', 'showProduction', $check);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA.
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação adequada.
                    </p>",
            ]);
        }
    }

    public function go_to_analise()
    { /* opcional */
    }

    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        $pausedCount = Production::where('status', 4)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', auth()->id())
            ->count();

        $isThisPaused = optional(Production::select('status')->find($production))->status === 4;

        if ($pausedCount >= $this->limit_pause && !$isThisPaused) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você já atingiu o limite de pausa neste serviço. Ao iniciar esta nota, você não poderá colocá-la em espera. Continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação cancelada.',
            ]);
        } else {
            $this->emit('open_analise_lev', $this->analise);
            $this->dispatchBrowserEvent('showModal', ['id' => 'analise_form']);
        }
    }

    public function blockWaiting($status)
    {
        // Sem if (!session()->isStarted()) { session()->start(); } use helper nativo
        $waitingForm = session('waitingForm');
        return $waitingForm && (int)$status !== 27;
    }

    public function filter_save()
    {
        $this->emit('refresh_service');
    }

    public function filter_clean()
    {
        $this->rubrica_s = [];
        $this->emit('refresh_service');
    }

    public function downloadFile($id)
    {
        if (!$file = File::select('path', 'file_name')->find($id)) {
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
     * Query base (MariaDB).
     * - Calcula dias no SQL com DATEDIFF(CURDATE(), ...).
     * - Faz LEFT JOIN em work_forms para acessar informed_at.
     */
    protected function baseQuery(): Builder
    {
        $this->loadFilters();
        $cityFilter = collect((array) ($this->filter['city'] ?? []))
            ->filter(fn ($city) => filled($city))
            ->values()
            ->all();

        // Cálculos (MariaDB)
        $daysAssignedExpr = "DATEDIFF(CURDATE(), productions.att_at)";
        $daysLeftExpr     = "IFNULL(DATEDIFF(CURDATE(), work_reports.informed_at), 0)";

        return Production::query()
                    ->with([
                'Note:id,note,material,mmgd,rubrica,lexp,postes,dt_status',
                // WorkForm é o nome da relação no modelo Note que aponta para WorkReport (tabela work_reports)
                'Note.WorkForm:id,note_id,informed_at,rejected',
                // belongsToMany via order_work_report: NÃO existe work_form_id em orders
                'Note.WorkForm.Orders' => fn ($q) => $q->select('orders.id', 'orders.ordem'),
                'Note.OldAds:id,note_id',
                'Note.Adsform:id,note_id',
                'Wpas:id,production_id,dd,created_at',
                'Note.Files:id,service_id,note_id,file_name,path,ext',
            ])

            // JOIN necessário para created_at e days_left
            ->leftJoin('work_reports', 'work_reports.note_id', '=', 'productions.note_id')

            ->where('productions.service_id', $this->service->uuid)
            ->when(
                $this->user_s,
                fn ($q) => $q->where('productions.user_id', $this->user_s),
                fn ($q) => $q->where('productions.user_id', auth()->id())
            )
            ->where('productions.completed', false)

            ->when($this->search, function (Builder $q, $search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->whereRelation('Note', 'note', 'like', "%{$search}%")
                        ->orWhereRelation('Note', 'material', 'like', "%{$search}%");
                });
            })
            ->when(!empty($cityFilter), function (Builder $q) use ($cityFilter) {
                $q->whereHas('Note', function (Builder $noteQuery) use ($cityFilter) {
                    $noteQuery->whereIn('nexp', $cityFilter);
                });
            })

            ->orderByDesc('priority')
            ->orderByDesc('partial')
            ->orderBy('work_dt_created', 'ASC')
            ->orderBy('att_at', 'DESC')
            ->orderBy('status', 'ASC')
            ->orderBy('productions.id', 'DESC')

            ->select([
                'productions.id',
                'productions.service_id',
                'productions.user_id',
                'productions.note_id',
                'productions.status',
                'productions.priority',
                'productions.partial',
                'productions.dfive',
                'productions.block',
                'productions.block_wpa',
                'productions.completed',
                'productions.att_at',
                'productions.transferred',
                'work_reports.created_at as work_dt_created',
            ])
            ->selectRaw("$daysAssignedExpr as days_assigned")
            ->selectRaw("$daysLeftExpr as days_left");
    }



    public function getListsProperty()
    {
        // Lista de usuários p/ filtro (somente o que é necessário)
        $this->user_l = User::select('id', 'name')
            ->when($this->user_search, fn ($q) =>
                $q->where('name', 'like', '%' . $this->user_search . '%'))
            ->orderBy('name')
            ->get();

        // Retorna a query base (sem paginate)
        return $this->baseQuery();
    }

    public function render()
    {
        return view('livewire.services.supervision.main', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
