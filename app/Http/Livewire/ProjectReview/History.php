<?php

namespace App\Http\Livewire\ProjectReview;

use App\Models\Company;
use App\Models\File;
use App\Models\ProjectReviewCycle;
use App\Models\Production;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $company_id = '';
    public ?string $from = null;
    public ?string $to = null;
    public ?ProjectReviewCycle $selectedCycle = null;
    public ?Production $selectedProduction = null;

    public function getRowsProperty()
    {
        return Production::query()
            ->with([
                'Note',
                'User',
                'Company',
                'ProjectReviewCycles' => function ($q) {
                    $q->with(['Orders', 'DecidedBy'])->latest('round_number');
                },
            ])
            ->withCount([
                'ProjectReviewCycles as rejected_cycles_count' => function ($q) {
                    $q->where('decision', 'REJECTED');
                },
                'Notetimelines as rejected_status_timeline_count' => function ($q) {
                    $q->where('status', Production::STATUS_REJECTED_PROJECT_REVIEW);
                },
            ])
            ->withMax('ProjectReviewCycles as latest_round_number', 'round_number')
            ->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW, Production::STATUS_RELEASED_TO_FINISH])
            ->whereHas('ProjectReviewCycles', function ($q) {
                $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED']);
            })
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                $q->whereHas('Note', function ($n) use ($s) {
                    $n->where('note', 'like', $s)
                        ->orWhere('numPedido', 'like', $s)
                        ->orWhere('material', 'like', $s);
                });
            })
            ->when($this->company_id !== '', fn($q) => $q->where('company_id', $this->company_id))
            ->when($this->from, function ($q) {
                $q->whereHas('ProjectReviewCycles', fn ($cq) => $cq->whereDate('submitted_at', '>=', $this->from));
            })
            ->when($this->to, function ($q) {
                $q->whereHas('ProjectReviewCycles', fn ($cq) => $cq->whereDate('submitted_at', '<=', $this->to));
            })
            ->orderByDesc('id')
            ->paginate(30);
    }

    public function getCompaniesProperty()
    {
        return Company::query()->orderBy('name')->get(['id', 'name']);
    }

    public function openProduction(int $productionId): void
    {
        $this->selectedProduction = Production::with([
            'Note.Files.Service',
            'User',
            'Company',
            'Service',
            'Files',
            'Analise',
            'ProjectReviewCycles' => function ($q) {
                $q->with([
                    'Orders',
                    'Findings.Subcategory.Category',
                    'Findings.Item',
                    'DecidedBy',
                    'Messages.User',
                ])->latest('round_number');
            },
        ])->findOrFail($productionId);

        $this->selectedCycle = $this->selectedProduction->ProjectReviewCycles
            ->firstWhere('decision', 'REJECTED')
            ?: $this->selectedProduction->ProjectReviewCycles->first();

        $this->dispatchBrowserEvent('showModal', ['id' => 'historyProjectReviewModal']);
    }

    public function downloadProductionFile(int $fileId)
    {
        if (!$this->selectedProduction) {
            return null;
        }

        $file = File::query()
            ->where('id', $fileId)
            ->where('note_id', $this->selectedProduction->note_id)
            ->first();

        if (!$file) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo não encontrado',
                'html' => 'O arquivo selecionado não está disponível para esta nota.',
                'timer' => 2600,
            ]);
            return null;
        }

        if (!$file->path || !Storage::exists($file->path)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo indisponível',
                'html' => 'Não foi possível localizar o arquivo no storage. Atualize a lista e tente novamente.',
                'timer' => 3200,
            ]);
            return null;
        }

        $downloadName = $file->original_name ?: ($file->file_name . ($file->ext ? '.' . $file->ext : ''));
        try {
            return Storage::download($file->path, $downloadName);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Erro ao baixar arquivo',
                'html' => 'O arquivo não pôde ser lido no storage.',
                'timer' => 3200,
            ]);
            return null;
        }
    }

    public function downloadFile(int $fileId)
    {
        return $this->downloadProductionFile($fileId);
    }

    public function render()
    {
        return view('livewire.project-review.history', [
            'rows' => $this->rows,
            'companies' => $this->companies,
        ]);
    }
}
