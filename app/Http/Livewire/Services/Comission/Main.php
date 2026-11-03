<?php

namespace App\Http\Livewire\Services\Comission;

use App\Exports\ProductionServiceExport;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{City, Production, Service};
use App\Services\Comission\ComissionListRowPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

/**
 * Serviços → Comissionamento: as atividades atribuídas ao próprio usuário (iniciar ou transferir).
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md. Filtros pelo trait `WithListFilters`, linhas apresentadas no componente.
 */
class Main extends Component
{
    use WithNoteListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note_type = '';

    public $limit_pause = 50;

    public $analise;

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'refresh_list'       => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
    ];

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'buscar'],
        'page'      => ['except' => 1, 'as' => 'p'],
        'perPage'   => ['as' => 'pp'],
        'note_type' => ['except' => '', 'as' => 'tipo'],
        'filters'   => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->with('Status')->first();
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid . auth()->id();
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => DB::table('productions')
                ->join('notes', 'notes.id', '=', 'productions.note_id')
                ->where('productions.service_id', $this->service->uuid)
                ->where('productions.user_id', auth()->id())
                ->where('productions.completed', false)
                ->whereNotNull('notes.rubrica')->where('notes.rubrica', '!=', '')
                ->distinct()->orderBy('notes.rubrica')->pluck('notes.rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** "Limpar tudo": filtros, busca e tipo de nota. */
    public function clearAllCriteria(): void
    {
        $this->filters   = [];
        $this->search    = '';
        $this->note_type = '';
        $this->afterListFiltersChanged();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    private function locationCodes(): array
    {
        $region = $this->filterValues('region');
        $local  = $this->filterValues('regional');
        $city   = $this->filterValues('city');

        if (!$region && !$local && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($local, fn ($q) => $q->whereIn('baseConstrucao', $local))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedNoteType()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function export_excel()
    {
        return (new ProductionServiceExport($this->listQuery()->get()))->download(date('YmdHis-') . 'production_services.xlsx');
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
        $check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first();

        if ($check) {
            $this->emit('open_analise_lev', ['productionId' => $check->id, 'noteId' => $check->note_id]);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);

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

    public function go_to_analise()
    {
        $this->emit('open_analise_lev', $this->analise);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_form',
        ]);
    }

    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        if ($this->limit_pause === Production::Where('status', 4)->Where('service_id', $this->service->uuid)->Where('user_id', Auth()->User()->id)->count() && (Production::find($production))->status != 4) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você ja atingiu o limite de pausa neste serviço, ao iniciar esta nota, você não poderá colocar esta NOTA/OV em espera. \n Tem certeza que deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',
            ]);
        } else {
            $this->emit('open_analise_lev', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    /** Atividades abertas do próprio usuário nesta atividade, com busca e filtros aplicados (a tela e o Excel leem a mesma). */
    private function listQuery(): Builder
    {
        $term    = trim((string) $this->search);
        $rubrica = $this->filterValues('rubrica');
        $base    = $this->locationCodes();

        return Production::query()
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.user_id', Auth()->user()->id)
            ->where('productions.completed', false)
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('notes.note', 'like', '%' . $term . '%')->orWhere('notes.material', 'like', '%' . $term . '%')))
            ->when($this->note_type !== '', fn ($q) => $q->where('notes.type_note', $this->note_type))
            ->when($rubrica, fn ($q) => $q->whereIn('notes.rubrica', $rubrica))
            ->when($base, fn ($q) => $q->whereIn('notes.nexp', $base))
            ->with(['Note', 'Wpas'])
            ->orderBy('productions.priority', 'DESC')
            ->orderBy('notes.dt_created', 'asc')
            ->select('productions.*', 'notes.dt_created as note_dt_created');
    }

    /** Linha pronta: o prazo real segue a regra da atividade (`ComissionListRowPresenter::dueTone()`). */
    private function presentRow(Production $production, ComissionListRowPresenter $presenter): array
    {
        $note     = $production->Note;
        $daysLeft = (int) $note->days_left;
        $wpa      = $production->Wpas->sortByDesc('created_at')->first();

        return [
            'id'          => (int) $production->id,
            'noteId'      => (int) $note->id,
            'note'        => $note->note,
            'priority'    => (bool) $production->priority,
            'dd'          => $wpa?->dd,
            'mmgd'        => (bool) $note->mmgd,
            'group2'      => $note->group2,
            'rubrica'     => $note->rubrica,
            'city'        => $note->lexp,
            'zone'        => $note->group1,
            'material'    => $note->material,
            'attDays'     => $production->att_at ? (int) Carbon::now()->diffInDays(Carbon::parse($production->att_at)->startOfDay()) : null,
            'dueAt'       => Carbon::today()->addDays($daysLeft),
            'dueTone'     => $presenter->dueTone($daysLeft),
            'dueSub'      => \App\Support\Ops\Deadline::remainingText($daysLeft),
            'elapsedDays' => ComissionListRowPresenter::TOTAL_DAYS - $daysLeft,
            'status'      => $production->status,
            'waiting'     => (bool) ($production->transferred && $production->block_wpa),
            'canAct'      => !$production->block && !$production->block_wpa && !$production->completed,
            'daysLeft'    => $daysLeft,
        ];
    }

    public function render()
    {
        $lists     = $this->listQuery()->paginate($this->perPage);
        $presenter = app(ComissionListRowPresenter::class);

        // Mesma ordenação de antes dentro da página: prioridade primeiro, depois o prazo mais curto.
        $rows = $lists->getCollection()
            ->sortBy([['priority', 'desc'], ['Note.days_left', 'asc']])
            ->map(fn (Production $production) => $this->presentRow($production, $presenter))
            ->values();

        return view('livewire.services.comission.main', [
            'lists' => $lists,
            'rows'  => $rows,
        ]);
    }
}
