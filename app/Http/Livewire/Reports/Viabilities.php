<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportViabilityReportJob;
use App\Services\Reports\ViabilityReportQueryService;
use Livewire\Component;
use Livewire\WithPagination;

class Viabilities extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $multi_search_input = '';
    public $multi_search_terms = [];

    public $column = 'sended_at';
    public $dt_init;
    public $dt_end;

    public $all = false;

    public function updated($name)
    {
        if (in_array($name, ['search', 'column', 'dt_init', 'dt_end'], true)) {
            $this->resetPage();
        }
    }

    public function applyMultiSearch()
    {
        $terms = preg_split('/[\s,;\n\r\t]+/', (string) $this->multi_search_input);
        $terms = collect($terms)->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();

        $this->multi_search_terms = $terms;
        if (count($terms) > 0) {
            $this->search = implode(', ', $terms);
        }
        $this->resetPage();
    }

    public function clearMultiSearch()
    {
        $this->multi_search_input = '';
        $this->multi_search_terms = [];
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->multi_search_input = '';
        $this->multi_search_terms = [];
        $this->column = 'sended_at';
        $this->dt_init = null;
        $this->dt_end = null;
        $this->resetPage();
    }

    public function Export()
    {
        ExportViabilityReportJob::dispatch($this->reportParams(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'EXPORTACAO EM ANDAMENTO',
            'html' => "<div class='card'><div class='card-body'><p>Seu arquivo esta sendo gerado.</p><p class='fw-bold'>Voce sera notificado quando estiver pronto.</p></div></div>",
            'timer' => 5000,
        ]);
    }

    public function getListsProperty()
    {
        return app(ViabilityReportQueryService::class)->query($this->reportParams());

    }

    private function reportParams(): array
    {
        return [
            'search' => $this->search,
            'multi_search_terms' => $this->multi_search_terms,
            'column' => $this->column,
            'dt_init' => $this->dt_init,
            'dt_end' => $this->dt_end,
        ];
    }

    public function render()
    {
        return view('livewire.reports.viabilities', [
            'lists' => $this->lists->paginate(100),
        ]);
    }
}
