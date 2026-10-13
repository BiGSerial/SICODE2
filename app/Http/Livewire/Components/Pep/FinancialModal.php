<?php

namespace App\Http\Livewire\Components\Pep;

use App\Exports\PepExport;
use App\Models\Pep;
use Livewire\{Component, WithPagination};
use Maatwebsite\Excel\Concerns\Exportable;

class FinancialModal extends Component
{
    use Exportable;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $region = '';

    public string $year = '';

    public string $availability = '';

    protected $listeners = ['openPepFinancialModal' => 'open'];

    public function open(?string $pep = null): void
    {
        $this->resetPage();
        $this->search = $pep ?? '';
        $this->dispatchBrowserEvent('showModal', ['id' => 'pepFinancialModal']);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'region', 'year', 'availability'], true)) {
            $this->resetPage();
        }
    }

    public function export(): mixed
    {
        return (new PepExport($this->search, $this->region, $this->year, $this->availability))
            ->download('peps-' . now()->format('YmdHis') . '.xlsx');
    }

    public function render()
    {
        $query = PepExport::filteredQuery($this->search, $this->region, $this->year, $this->availability);

        return view('livewire.components.pep.financial-modal', [
            'peps'    => $query->paginate(25),
            'regions' => Pep::query()->whereNotNull('regiao')->distinct()->orderBy('regiao')->pluck('regiao'),
            'years'   => Pep::query()->whereNotNull('ano')->distinct()->orderByDesc('ano')->pluck('ano'),
        ]);
    }
}
