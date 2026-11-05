<?php

namespace App\Http\Livewire\Services\Payment\Accompany;

use App\Exports\Dispatchs\DispatchPaymentStack;
use App\Exports\Services\Payment\D5tolistExport;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\{BuildsLegalNoteTags, FinishesCanceledInforme};
use App\Models\{City, File, Production, Service};
use App\Models\WorkReportFlowProduction;
use App\Services\Files\FileStorageService;
use App\Services\Payment\PaymentAccompanyRowPresenter;
use App\Services\Production\CanceledInformeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

/**
 * Acompanhamento de Pagamento (Serviços): a fila de trabalho do usuário. Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 * Filtros pelo `WithNoteListFilters` (na URL); a linha vem pronta do `PaymentAccompanyRowPresenter`, que também guarda a regra do
 * prazo de medição (8 dias úteis: Parcial da aprovação do engenheiro, Final da entrega da ADS).
 */
class Main extends Component
{
    use WithPagination;
    use BuildsLegalNoteTags;
    use FinishesCanceledInforme;
    use TextFormatter;
    use WithNoteListFilters {
        clearAllCriteria as private clearListCriteria;
    }

    public const PAGE_SIZES = [25, 50, 100, 250];

    public const TABS = ['producao', 'transferencias'];

    /** Atalho de prazo => tom do selo de vencimento (`Deadline`). */
    public const PRAZOS = ['vencido' => 'red', 'vencendo' => 'amber'];

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 50;

    public $search = '';

    public $typeNote = '';

    public $tab = 'producao';

    /** Atalho de prazo: '' (todos), 'vencido' ou 'vencendo' (últimos dias úteis). */
    public $prazo = '';

    public $advanceSearch;

    /** @var array<int, string> notas ou ordens da busca em lote */
    public $multiSearch = [];

    public $limit_pause = 3;

    public $analise;

    /** Método (e não propriedade): não redeclara `$queryString` do Livewire\Component. */
    protected function queryString(): array
    {
        return [
            'search'   => ['except' => '', 'as' => 'buscar'],
            'typeNote' => ['except' => '', 'as' => 'tipo'],
            'filters'  => ['except' => [], 'as' => 'f'],
            'perPage'  => ['except' => 50, 'as' => 'pp'],
            'tab'      => ['except' => 'producao', 'as' => 'aba'],
            'prazo'    => ['except' => '', 'as' => 'prazo'],
        ];
    }

    protected function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'refresh_accomany'        => '$refresh',
            'refresh_list'            => '$refresh',
            'refresh_service'         => '$refresh',
            'getCopy'                 => 'copy',
            'confirm_finish_canceled' => 'finishCanceled',
            'checkOpen',
        ]);
    }

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();

        $this->perPage = in_array((int) $this->perPage, self::PAGE_SIZES, true) ? (int) $this->perPage : 50;
        $this->tab     = in_array($this->tab, self::TABS, true) ? $this->tab : 'producao';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . $this->service->uuid . ':' . auth()->id();
    }

    // ---------- Estado da tela ----------

    public function updated($name): void
    {
        if ($name === 'page') {
            return;
        }

        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'producao';
    }

    public function toggleDeadline(string $prazo): void
    {
        $this->prazo = $this->prazo === $prazo || !array_key_exists($prazo, self::PRAZOS) ? '' : $prazo;
        $this->resetPage();
    }

    /** "Limpar tudo": filtros, busca (simples e em lote) e tipo. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search        = '';
        $this->advanceSearch = '';
        $this->multiSearch   = [];
        $this->typeNote      = '';
        $this->prazo         = '';
        $this->filters       = [];

        $this->afterListFiltersChanged();
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || filled($this->search) || filled($this->typeNote) || filled($this->prazo) || $this->multiSearch !== [];
    }

    public function buscarMulti(): void
    {
        $this->search = '';
        // Separa por linha, espaço, vírgula ou ponto e vírgula.
        $this->multiSearch = collect(preg_split('/[\s,;\r\n\t]+/', (string) $this->advanceSearch))
            ->map(fn ($term) => trim((string) $term))->filter()->unique()->take(300)->values()->all();

        $this->advanceSearch = '';
        $this->resetPage();
        $this->dispatchBrowserEvent('hideModal');
    }

    public function clearMultiSearch(): void
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Consulta (tela e Excel leem a mesma) ----------

    protected function listQuery(): Builder
    {
        $query = Production::query()
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->leftJoinSub(
                DB::table('operation_resps')
                    ->select('note_id', DB::raw('MAX(fimLancado) as latest_fimLancado'))
                    ->groupBy('note_id'),
                'latest_operation_resps',
                'notes.id',
                '=',
                'latest_operation_resps.note_id'
            )
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.user_id', auth()->id())
            ->where('productions.completed', false);

        $term = trim((string) $this->search);

        // Um só grupo: o OR da busca nunca pode escapar do escopo do usuário.
        $query->when($term !== '', function ($q) use ($term) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            return $q->where(fn ($w) => $w->where('notes.note', 'like', $like)->orWhere('notes.material', 'like', $like));
        });

        $query->when($this->multiSearch, fn ($q) => $q->where(fn ($w) => $w
            ->whereIn('notes.note', $this->multiSearch)
            ->orWhereExists(fn ($e) => $e->selectRaw('1')->from('orders')
                ->whereColumn('orders.note_id', 'notes.id')->whereIn('orders.ordem', $this->multiSearch))));

        $query->when($this->typeNote, fn ($q, $type) => $q->where('notes.type_note', $type));
        $query->when($this->filterValues('company'), fn ($q, $values) => $q->whereIn('productions.company_id', $values));
        $query->when($this->filterValues('rubrica'), fn ($q, $values) => $q->whereIn('notes.rubrica', $values));
        $query->when($this->locationCodes(), fn ($q, $codes) => $q->where(fn ($w) => $w->whereIn('notes.nexp', $codes)->orWhereIn('notes.lexp', $codes)));

        return $query
            ->select('productions.*', 'notes.dt_created as note_dt_created', 'latest_operation_resps.latest_fimLancado as fimLancado')
            ->orderByDesc('productions.priority')
            ->orderByDesc('productions.d5')
            ->orderByDesc('productions.partial')
            ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
            ->orderBy('fimLancado')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('productions.id');
    }

    /** Códigos de município escolhidos em Região / Regional / Município (mesma regra do `NoteFilter`). */
    private function locationCodes(): array
    {
        $cities    = $this->filterValues('city');
        $regions   = $this->filterValues('region');
        $regionals = $this->filterValues('regional');

        if (!$cities && !$regions && !$regionals) {
            return [];
        }

        return City::query()
            ->when($cities, fn ($q) => $q->whereIn('rdMunicipio', $cities))
            ->when($regions, fn ($q) => $q->whereIn('regiao', $regions))
            ->when($regionals, fn ($q) => $q->whereIn('baseConstrucao', $regionals))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    /** Relações da página (carregadas depois da paginação, para a consulta principal ficar leve). */
    private function loadPageRelations($collection): void
    {
        $collection->load([
            'User:id,name',
            'Note.Files',
            'Note.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
            'Note.WorkForm:id,note_id,company_id,informer,date,informed_at,created_at,rejected,selected_final_scopes',
            'Note.WorkForm.Company:id,name,deleted_at',
            'Note.WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
            'Note.WorkForm.Orders.Pep:objeto,disponivel',
            'Note.WorkForm.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'Note.WorkForm.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
            'fiveNotes:five_notes.id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,is_payed,completed_at',
            'measurementContext.fiveNote',
            'Note.Partials.Company:id,name,deleted_at',
            'Note.Partials.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
            'Note.Partials.Orders.Pep:objeto,disponivel',
            'Note.Partials.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'WorkReportFlowProductions'       => fn ($q) => $q
                ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                ->where('is_current', true)
                ->with([
                    'WorkReport:id,note_id,company_id,informer,date,informed_at,created_at,rejected,selected_final_scopes',
                    'WorkReport.Company:id,name,deleted_at',
                    'WorkReport.Orders' => fn ($orders) => $orders->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
                    'WorkReport.Orders.Pep:objeto,disponivel',
                    'WorkReport.Orders.Operations' => fn ($orders) => $orders->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
                    'WorkReport.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
                ]),
        ]);
    }

    // ---------- Ações ----------

    public function export_excel()
    {
        return (new DispatchPaymentStack($this->listQuery(), $this->service->uuid))->download(date('YmdHis-') . 'exportControlPayment.xlsx');
    }

    public function export_d5tolist()
    {
        return (new D5tolistExport($this->service, auth()->id()))->download(date('YmdHis-') . 'exportD5tolist.xlsx');
    }

    /** O formulário de outra nota em atividade bloqueia o início das demais (exceto status 27). */
    public function blockWaiting($status)
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) {
                session()->start();
            }
        }

        return isset($_SESSION['waitingForm']) && $status != 27;
    }

    public function goTransferProd($prod_id)
    {
        if ($this->ownsProduction($prod_id)) {
            $this->emit('transfer_production', $prod_id);
        }
    }

    private function ownsProduction($productionId): bool
    {
        return Production::query()
            ->whereKey($productionId)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', auth()->id())
            ->exists();
    }

    /** Ao abrir a tela: se o usuário ainda tem uma nota em atividade, abre o formulário dela. */
    public function checkOpen()
    {
        $check = Production::where('service_id', $this->service->uuid)->where('user_id', auth()->id())->where('status', 3)->first();

        if ($check) {
            $this->emitTo('services.payment.forms.jobform', 'showProduction', $check->id);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                        adequada.
                    </p>
                ",
            ]);
        }
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {
            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            }
        }
    }

    public function render()
    {
        $presenter = app(PaymentAccompanyRowPresenter::class);

        if ($this->prazo) {
            // O vencimento é calculado em PHP (dias úteis e feriados): filtra a fila do usuário e pagina depois.
            $all = $this->listQuery()->get();
            $this->loadPageRelations($all);
            $legal    = $this->buildLegalTagsByNoteIds($all->pluck('note_id')->all());
            $canceled = app(CanceledInformeService::class)->forProductions($all->pluck('id')->all());

            $rows = $all
                ->map(fn (Production $p) => $presenter->present($p, $legal[$p->note_id] ?? [], $this->blockWaiting($p->status), $canceled[$p->id] ?? null))
                ->filter(fn (array $row) => $row['dueTone'] === self::PRAZOS[$this->prazo])
                ->values();

            $lists = new LengthAwarePaginator(
                $rows->forPage($this->page, (int) $this->perPage)->values(),
                $rows->count(),
                (int) $this->perPage,
                $this->page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
            $rows = collect($lists->items());
        } else {
            $lists = $this->listQuery()->paginate((int) $this->perPage);
            $this->loadPageRelations($lists->getCollection());
            $legal    = $this->buildLegalTagsByNoteIds(collect($lists->items())->pluck('note_id')->all());
            $canceled = app(CanceledInformeService::class)->forProductions(collect($lists->items())->pluck('id')->all());
            $rows     = collect($lists->items())
                ->map(fn (Production $p) => $presenter->present($p, $legal[$p->note_id] ?? [], $this->blockWaiting($p->status), $canceled[$p->id] ?? null))
                ->values();
        }

        return view('livewire.services.payment.accompany.main', [
            'lists'       => $lists,
            'rows'        => $rows,
            'pageSizes'   => self::PAGE_SIZES,
            'hasCriteria' => $this->hasCriteria(),
        ]);
    }
}
