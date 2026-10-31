<?php

namespace App\Http\Livewire\Engineers;

use App\Helpers\TextFormatter;
use App\Jobs\Engineers\WorkedListExportJob;
use App\Services\Engineers\WorkedListQuery;
use Carbon\Carbon;
use Livewire\{Component, WithPagination};

/**
 * Lista de informes finais (Engenharia): do mais recente para o mais antigo, com busca, filtros na URL, resumo e Excel.
 * A tela só orquestra filtros; a consulta (e o resumo) vêm de WorkedListQuery, a mesma do Excel.
 */
class Workedlist extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public string $search = '';

    public ?string $company = null;

    public ?string $rubrica = null;

    public ?string $city = null;

    public string $ads = '';

    public string $changes = '';

    public string $situacao = 'informados';

    public string $preset = 'all';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    /** @var array<int, string> notas/ordens da busca em lote */
    public array $multiSearch = [];

    public ?string $advanceSearch = null;

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'company'  => ['except' => '', 'as' => 'empresa'],
        'rubrica'  => ['except' => '', 'as' => 'rubrica'],
        'city'     => ['except' => '', 'as' => 'municipio'],
        'ads'      => ['except' => '', 'as' => 'ads'],
        'changes'  => ['except' => '', 'as' => 'alteracao'],
        'situacao' => ['except' => 'informados', 'as' => 'situacao'],
        'preset'   => ['except' => 'all', 'as' => 'periodo'],
        'dt_in'    => ['except' => '', 'as' => 'dtin'],
        'dt_out'   => ['except' => '', 'as' => 'dtout'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount(): void
    {
        Carbon::setLocale('pt_BR');

        if ($this->preset !== 'custom' && $this->preset !== 'all') {
            $this->applyPreset($this->preset);
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
    }

    public function updated($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250], true) ? (int) $this->perPage : 50;
    }

    public function updatedDtIn(): void
    {
        $this->preset = 'custom';
    }

    public function updatedDtOut(): void
    {
        $this->preset = 'custom';
    }

    public function setPreset(string $preset): void
    {
        $this->applyPreset($preset);
        $this->resetPage();
    }

    public function buscarMulti(): void
    {
        $this->search        = '';
        $this->multiSearch   = $this->formatTextToArray($this->advanceSearch);
        $this->advanceSearch = '';
        $this->dispatchBrowserEvent('hideModal');
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'company', 'rubrica', 'city', 'ads', 'changes', 'dt_in', 'dt_out', 'multiSearch', 'advanceSearch']);
        $this->situacao = 'informados';
        $this->preset   = 'all';
        $this->resetPage();
    }

    public function exportToExcel(): void
    {
        WorkedListExportJob::dispatch($this->filters(), auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>A lista de informes está sendo gerada em Excel, com todos os registros dos filtros atuais. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function render()
    {
        $service = app(WorkedListQuery::class);
        $f       = $service->normalize($this->filters());
        $user    = auth()->user();

        return view('livewire.engineers.workedlist', [
            'lists'   => $service->withRelations($service->query($user, $f))->paginate((int) $this->perPage),
            'summary' => $service->summary($user, $f),
            'options' => $service->options($user),
            'service' => $service,
            'filters' => $f,
            'active'  => $this->hasFilters(),
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        return [
            'from'     => $this->dt_in,
            'to'       => $this->dt_out,
            'search'   => $this->search,
            'multi'    => $this->multiSearch,
            'company'  => $this->company,
            'rubrica'  => $this->rubrica,
            'city'     => $this->city,
            'ads'      => $this->ads,
            'changes'  => $this->changes,
            'situacao' => $this->situacao,
        ];
    }

    private function hasFilters(): bool
    {
        return $this->search !== '' || $this->multiSearch || $this->company || $this->rubrica || $this->city
            || $this->ads !== '' || $this->changes !== '' || $this->situacao !== 'informados' || $this->dt_in || $this->dt_out;
    }

    private function applyPreset(string $preset): void
    {
        $this->preset = array_key_exists($preset, WorkedListQuery::PRESETS) ? $preset : 'all';

        [$this->dt_in, $this->dt_out] = app(WorkedListQuery::class)->presetRange($this->preset);
    }
}
