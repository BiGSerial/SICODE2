<?php

namespace App\Http\Livewire\Protests\Partner;

use App\Exports\Protests\OpenProtestJobsExport;
use App\Models\ProtestJob;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Filtros */
    public int $perPage = 50;
    public string $search = '';

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Query base: jobs do usuário logado como owner,
     * em status "aberto" (OPENED / ASSIGNED / IN_PROGRESS / WAITING / REOPENED)
     */
    protected function baseQuery()
    {
        return ProtestJob::query()
            ->open() // scopeOpen do modelo
            ->where('owner_id', auth()->id())
            ->with([
                'protest',
                'medProtest',
                'Comments' => function ($q) {
                    $q->latest(); // última mensagem primeiro
                },
                'creator:id,name',
            ])
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($qq) use ($term) {
                    $qq->where('id', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('protest', function ($sub) use ($term) {
                            $sub->where('nota', 'like', $term)
                                ->orWhere('cidade', 'like', $term)
                                ->orWhere('txtGrpCodificacao', 'like', $term);
                        });
                });
            })
            ->orderByDesc('priority')
            ->orderBy('sla_due_at')
            ->orderByDesc('sent_at');
    }

    /** Lista paginada */
    public function getListProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /**
     * Aceitar o job:
     * - garante que o job pertence ao usuário
     * - chama $job->accept()
     * - abre o modal de visualização
     */
    public function accept(int $jobId): void
    {
        $job = ProtestJob::where('owner_id', auth()->id())->findOrFail($jobId);

        $job->accept();

        // abre o mesmo componente de view utilizado em Monitoring
        $this->emitTo('protests.dispatch.actions.view-protest-job', 'open', $job->id);
    }

    /**
     * Apenas abrir para visualizar (já aceito / em progresso etc.)
     */
    public function open(int $jobId): void
    {
        $job = ProtestJob::where('owner_id', auth()->id())->findOrFail($jobId);

        $this->emitTo('protests.dispatch.actions.view-protest-job', 'open', $job->id);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'perPage']);
        $this->resetPage();
    }

    public function exportToExcel()
    {
        $file = 'protestos_partner_' . now()->format('YmdHis') . '.xlsx';

        return Excel::download(
            new OpenProtestJobsExport(clone $this->baseQuery(), includeOwner: false),
            $file
        );
    }

    public function render()
    {
        return view('livewire.protests.partner.main', [
            'list' => $this->list,
        ]);
    }
}
