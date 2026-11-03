<?php

namespace App\Http\Livewire\Dispatchs\Default\Concerns;

use App\Custom\RuleBuilder;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Bancoupdate, City, Company, Note, Notetimeline, Production, Service, User};
use App\Services\Dispatchs\DefaultListRowPresenter;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Lista operacional do Despacho da atividade padrão (`Default\Main` e `Default\PartnerMain`): estado, filtros, consulta,
 * seleção e o modal de despacho. As duas telas só diferem no Excel (`export_excel`) e na variante `$partner`.
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 */
trait HasDefaultMainList
{
    use TextFormatter;
    use WithNoteListFilters;

    public $service;

    public $perPage = 100;

    public $search;

    public $note;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    public $company_s;

    public $user_s;

    public $type;

    public $additionalData = [];

    public $notes;

    public $enter_dd;

    public $note_type = '';

    /** @var array<int, array> Situação de despacho por nota, calculada uma vez por requisição. */
    private array $evalMemo = [];

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'buscar'],
        'page'      => ['except' => 1, 'as' => 'p'],
        'perPage'   => ['as' => 'pp'],
        'note_type' => ['except' => '', 'as' => 'tipo'],
        'filters'   => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'getCopy'          => 'copy',
        'confirm_dispatch' => 'confirmed_att',
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('default-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
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
        return static::class . ':' . $this->service->uuid;
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->where('nstats', $this->service->status)
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** "Limpar tudo": filtros, busca (simples e em massa) e tipo. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->note_type     = '';
        $this->afterListFiltersChanged();
    }

    public function updatedSearch(): void
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedNoteType(): void
    {
        $this->resetPage();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    public function getBaseProperty(): array
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

    // ---------- Consulta ----------

    public function getListsProperty()
    {
        $uuid  = $this->service->uuid;
        $query = Note::query()->excludeCanceledFullDone();

        if (count($this->multiSearch)) {
            // Busca em massa: procura as notas informadas, sem as regras de status da atividade.
            $query->whereIn('note', $this->multiSearch);
        } else {
            RuleBuilder::applyRules($query, $this->service->Status);

            $rubrica = $this->filterValues('rubrica');
            $base    = $this->base;
            $term    = $this->search;

            $query->when($term, fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('note', 'like', '%' . $term . '%')
                    ->orWhere('material', 'like', '%' . $term . '%')
                    ->orWhere('numPedido', 'like', '%' . $term . '%');
            }))
                ->when($rubrica, fn ($q) => $q->where(fn ($w) => $w->whereIn('rubrica', $rubrica)->orWhereNull('rubrica')))
                ->when($this->note_type, fn ($q) => $q->where(fn ($w) => $w->where('type_note', $this->note_type)->orWhereNull('type_note')))
                ->when($base, fn ($q) => $q->where(fn ($w) => $w->whereIn('nexp', $base)->orWhere('nexp', '')->orWhereNull('nexp')));
        }

        return $query
            ->with(['Productions' => fn ($q) => $q->where('service_id', $uuid)->with(['User', 'Company:id,name'])])
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left')
            ->orderBy('dt_status')
            ->paginate($this->perPage);
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
    private function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    // ---------- Seleção ----------

    private function rowEval(Note $note): array
    {
        return $this->evalMemo[$note->id] ??= app(DefaultListRowPresenter::class)->evaluate($note, $this->service->uuid);
    }

    private function canDispatchNote(Note $note): bool
    {
        return $this->rowEval($note)['canDispatch'];
    }

    /** Marca/desmarca todas as linhas despacháveis da página. */
    public function setSelectAll(): void
    {
        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);

        foreach ($this->lists->items() as $note) {
            $id = (string) $note->id;

            if ($this->selectall) {
                if ($this->canDispatchNote($note)) {
                    $selectedSet[$id] = true;
                }
            } else {
                unset($selectedSet[$id]);
            }
        }

        $this->selected = array_map(strval(...), array_keys($selectedSet));
    }

    /** O "selecionar todos" fica marcado quando todas as linhas despacháveis da página estão selecionadas. */
    public function checkAllSelect($items): bool
    {
        $this->selectall = $this->allEligibleSelected($items);

        return $this->selectall;
    }

    private function allEligibleSelected($items): bool
    {
        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);
        $eligible    = 0;

        foreach ($items as $note) {
            if (!$this->canDispatchNote($note)) {
                continue;
            }

            $eligible++;

            if (!isset($selectedSet[(string) $note->id])) {
                return false;
            }
        }

        return $eligible > 0;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Busca em massa ----------

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search      = '';
            $this->multiSearch = array_values(array_map('trim', $this->formatTextToArray($this->advanceSearch)));
        } else {
            $this->multiSearch = [];
        }

        if (count($this->multiSearch)) {
            $this->resetPage();
            $this->selected  = [];
            $this->selectall = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    // ---------- Despacho ----------

    public function get_single_note($note)
    {
        $this->selected = [$note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {
        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->notes = Note::find($this->selected);

        if ($this->notes->count()) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'add_mass_notes',
            ]);
        }
    }

    public function confirm_att()
    {
        if ($this->type === '2') {
            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . ' da ' . (Company::find($this->company_s))->name;
        } else {
            if (!$this->company_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = (Company::find($this->company_s))->name;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Despachar',
            'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nota/ov foi despachada.',
        ]);
    }

    public function confirmed_att()
    {
        if ($this->type == '2') {
            foreach ($this->notes as $note) {
                $production = Production::create([
                    'note_id'     => $note->id,
                    'service_id'  => $this->service->uuid,
                    'user_id'     => $this->user_s,
                    'company_id'  => $this->company_s,
                    'dispatch_by' => Auth()->User()->id,
                    'att_by'      => Auth()->User()->id,
                    'dt_note'     => $note->dt_status,
                    'status_note' => $note->nstats,
                    'centroTrab'  => $note->centerjob,
                    'dispatch_at' => date('Y-m-d H:i:s'),
                    'att_at'      => date('Y-m-d H:i:s'),
                    'status'      => 2,
                ]);

                if (trim($this->user_s)) {
                    $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                } else {
                    $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                }

                if ($production) {
                    Notetimeline::Create([
                        'note_id'       => $production->note_id,
                        'service_id'    => $production->service_id,
                        'user_id'       => Auth()->User()->id,
                        'info'          => "Usuário {$user_info}",
                        'status'        => 2,
                        'production_id' => $production->id,
                    ]);
                }
            }
        } else {
            foreach ($this->notes as $note) {
                Production::create([
                    'note_id'     => $note->id,
                    'service_id'  => $this->service->uuid,
                    'company_id'  => $this->company_s,
                    'dispatch_by' => Auth()->User()->id,
                    'dt_note'     => $note->dt_status,
                    'status_note' => $note->nstats,
                    'centroTrab'  => $note->centerjob,
                    'dispatch_at' => date('Y-m-d H:i:s'),
                    'status'      => 1,
                ]);
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas Despachadas com sucesso!',
            'timer'    => 2500,
        ]);

        $this->closeall();
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->selectall      = false;
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->advanceSearch  = '';
        $this->resetPage();

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {
        $this->company_s      = '';
        $this->enter_dd       = '';
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
    }

    // ---------- Render ----------

    /** Empresas e usuários do modal de despacho (só buscados com o modal montado). */
    private function dispatchModalOptions(): array
    {
        if (!$this->notes || !$this->notes->count()) {
            return ['companies' => collect(), 'users' => collect()];
        }

        $user      = Auth()->User();
        $companies = $user->usesContractDispatch()
            ? Company::whereIn('id', \App\Support\SicodeRules::visibleCompanyIdsFor($user))->orderBy('name')->get(['id', 'name'])
            : Company::orderBy('name')->get(['id', 'name']);

        return [
            'companies' => $companies,
            'users'     => $this->company_s ? User::where('company_id', $this->company_s)->orderBy('name')->get(['id', 'name']) : collect(),
        ];
    }

    protected function renderMainList(string $view, bool $partner)
    {
        $this->evalMemo = [];

        $lists     = $this->lists;
        $presenter = app(DefaultListRowPresenter::class);
        $uuid      = $this->service->uuid;
        $rows      = $lists->getCollection()->map(fn (Note $note) => $presenter->present($note, $uuid, $this->rowEval($note), $partner));

        return view($view, [
            'lists'        => $lists,
            'rows'         => $rows,
            'legalNoteIds' => $this->legalLinkedNoteIds($rows->pluck('noteId')->all()),
            'update'       => Cache::remember('default-dispatch:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
            'partner'      => $partner,
        ] + $this->dispatchModalOptions());
    }
}
