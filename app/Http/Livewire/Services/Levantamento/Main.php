<?php

namespace App\Http\Livewire\Services\Levantamento;

use App\Jobs\Services\ExportLevantamentoProductionListJob;
use App\Models\{File, Production, Service};
use Illuminate\Support\Facades\{Storage, Auth, DB};
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 30;
    public $search;
    public $analise;
    protected $limit_pause = 50;
    private string $filter_group = 'survey';
    private array $filter = [];

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'page'   => ['except' => 1],
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->firstOrFail();
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

    /** =====================
     * 🔹 EXPORTAÇÃO
     * ===================== */
    public function exportToExcel()
    {
        ExportLevantamentoProductionListJob::dispatch([
            'service_uuid' => $this->service->uuid,
            'user_id'      => Auth::id(),
            'search'       => $this->search,
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

    /** =====================
     * 🔹 EVENTOS DE INTERFACE
     * ===================== */
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

    /** =====================
     * 🔹 VERIFICA SE HÁ NOTA EM ATIVIDADE
     * ===================== */
    public function checkOpen()
    {
        $check = Production::where('service_id', $this->service->uuid)
            ->where('user_id', Auth::id())
            ->where('status', 3)
            ->first();

        if ($check) {
            $this->emit('open_analise_lev', [
                'productionId' => $check->id,
                'noteId'       => $check->note_id,
            ]);

            $this->dispatchBrowserEvent('showModal', ['id' => 'analise_form']);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "
                    Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA.
                    <p class='text-bg-light mt-2 p-2'>
                        Existe um limite para interromper notas. Ao atingi-lo, será necessário tratá-las adequadamente.
                    </p>
                ",
            ]);
        }
    }

    /** =====================
     * 🔹 ABERTURA DE ANÁLISE
     * ===================== */
    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        $pausedCount = Production::where('status', 4)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', Auth::id())
            ->count();

        $isPaused = Production::find($production)?->status === 4;

        if ($pausedCount >= $this->limit_pause && !$isPaused) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'LIMITE DE PAUSA ATINGIDO',
                'msg'           => "Você já atingiu o limite de pausas neste serviço. Ao iniciar esta nota, não será possível colocá-la em espera. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, continuar',
                'btnCanceltxt'  => 'Não, cancelar',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação cancelada.',
            ]);
        } else {
            $this->go_to_analise();
        }
    }

    public function go_to_analise()
    {
        if ($this->analise) {
            $this->emit('open_analise_lev', $this->analise);
            $this->dispatchBrowserEvent('showModal', ['id' => 'analise_form']);
        }
    }

    /** =====================
     * 🔹 DOWNLOAD DE ARQUIVOS
     * ===================== */
    public function downloadFile($id)
    {
        $file = File::find($id);

        if (!$file || !Storage::disk('local')->exists($file->path)) {
            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'error',
                'title' => 'Arquivo inexistente!',
                'timer' => 4000,
            ]);
            return;
        }

        return Storage::download($file->path, $file->file_name);
    }

    /** =====================
     * 🔹 CONSULTA PRINCIPAL
     * ===================== */
    public function getListsProperty()
    {
        $this->loadFilters();
        $cityFilter = collect((array) ($this->filter['city'] ?? []))
            ->filter(fn ($city) => filled($city))
            ->values()
            ->all();

        $pzoExpr = "
        CASE
            WHEN n.type_note = 1
            AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$' THEN
                CASE
                    WHEN CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) BETWEEN 1 AND 12 THEN
                        DATE_ADD(
                            DATE_ADD(
                                MAKEDATE(
                                    CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED),
                                    1
                                ),
                                INTERVAL (CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) - 1) MONTH
                            ),
                            INTERVAL 27 DAY
                        )
                    ELSE NULL
                END
            WHEN n.type_note = 2 THEN
                DATE_ADD(CURDATE(), INTERVAL COALESCE(n.days_left, 0) DAY)
            ELSE NULL
        END
    ";

        return Production::query()
            ->select([
                'productions.id',
                'productions.note_id',
                'productions.status',
                'productions.priority',
                'productions.att_at',
                'productions.completed',
                'productions.block',
                'productions.block_wpa',
                'productions.transferred',
                'n.note',
                'n.material',
                'n.group1',
                'n.group2',
                'n.rubrica',
                'n.lexp',
                'n.days_left',
                'n.mmgd',
                'n.dt_created',
                DB::raw("$pzoExpr AS pzo"),
            ])
            ->join('notes as n', 'n.id', '=', 'productions.note_id')
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.user_id', Auth::id())
            ->where('productions.completed', false)
            ->when($this->search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('n.note', 'like', "%{$s}%")
                        ->orWhere('n.material', 'like', "%{$s}%");
                });
            })
            ->when(!empty($cityFilter), fn ($q) => $q->whereIn('n.nexp', $cityFilter))
            ->addSelect('n.dt_created as dt_created')
            ->orderByDesc('productions.priority')
            ->orderBy('n.dt_created')
            ->orderBy('productions.id', 'DESC')
            ->with([
                'Wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'Service:id,uuid,service',
                'User:id,name',
                'Note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,dt_created,material,group2',
            ])
            ->paginate($this->perPage);
    }


    /** =====================
     * 🔹 RENDERIZAÇÃO
     * ===================== */
    public function render()
    {
        return view('livewire.services.levantamento.main', [
            'lists' => $this->lists,
        ]);
    }
}
