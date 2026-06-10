<?php

namespace App\Http\Livewire\Reports\Concerns;

use App\Custom\Notestatus;
use App\Models\File;
use App\Models\Reclaim;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

trait LoadsReturnInternDetails
{
    public array $returnDetails = [];

    public string $fileServiceFilter = '';

    protected function loadReturnInternDetails(int $reclaimId, bool $onlyActive = false): void
    {
        $this->fileServiceFilter = '';

        $query = Reclaim::query()
            ->when($onlyActive, fn (Builder $builder) => $builder->active())
            ->with([
                'Note.Orders',
                'Note.Files.Service',
                'Service',
                'Comments' => fn ($builder) => $builder->orderBy('comments.created_at'),
                'Comments.User',
                'Viabilities.Form',
                'Viabilities.Orders',
                'Waiting',
                'Approvals',
                'Externals',
                'Subcategory.Category',
                'Production.User',
                'Production.Company',
                'Production.Dispatcher',
            ]);

        $reclaim = $query->findOrFail($reclaimId);
        $note = $reclaim->Note;
        $viability = $reclaim->Viabilities->sortByDesc('created_at')->first();
        $form = $viability?->Form;
        $production = $reclaim->Production;
        $productionStatus = $production && $production->status !== null
            ? Notestatus::status($production->status)
            : null;

        $orders = collect($viability?->Orders ?? [])
            ->merge($note?->Orders ?? [])
            ->pluck('ordem')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->returnDetails = [
            'reclaim_id' => $reclaim->id,
            'note_id' => $note?->id,
            'note' => $note?->note ?? 'Não informada',
            'orders' => $orders,
            'note_status_code' => $note?->nstats ?: '—',
            'note_status' => $note?->status ?: '—',
            'municipality' => $note?->lexp ?: '—',
            'rubric' => $note?->rubrica ?: '—',
            'material' => $note?->material ?: '—',
            'service' => $reclaim->Service?->service ?? 'Não informado',
            'origin' => $this->returnInternDetailOrigin($reclaim),
            'category' => $reclaim->Subcategory?->Category?->name ?: 'Sem categoria',
            'reason' => $reclaim->Subcategory?->name ?: ($reclaim->category ?: 'Sem motivo informado'),
            'created_at' => $reclaim->created_at?->format('d/m/Y H:i') ?? 'Não informada',
            'completed_at' => $reclaim->completed_at?->format('d/m/Y H:i'),
            'viability_return' => $form ? [
                'reason' => $form->reason ?: '—',
                'impact' => $form->changes !== null ? ((int) $form->changes * 10).'%' : '—',
                'responsible' => $form->responsible ?: '—',
                'description' => $form->description ?: '—',
            ] : null,
            'production' => $production ? [
                'id' => $production->id,
                'responsible' => $production->User?->name ?: 'Sem responsável',
                'dispatcher' => $production->Dispatcher?->name ?: 'Não informado',
                'company' => $production->Company?->name ?: 'Não informada',
                'status' => $productionStatus?->status ?: 'Sem status',
                'status_class' => $productionStatus?->colorbg ?: 'text-bg-secondary',
                'dispatch_at' => $production->dispatch_at?->format('d/m/Y H:i') ?? '—',
                'att_at' => $production->att_at?->format('d/m/Y H:i') ?? '—',
                'completed_at' => $production->completed_at?->format('d/m/Y H:i') ?? '—',
            ] : null,
            'files' => $note?->Files
                ?->sortBy('file_name')
                ->map(fn ($file) => [
                    'id' => $file->id,
                    'service_id' => $file->service_id ?: '__none__',
                    'service' => $file->Service?->service ?: 'Sem serviço',
                    'name' => $file->file_name,
                    'extension' => strtoupper($file->ext ?: pathinfo($file->file_name, PATHINFO_EXTENSION)),
                    'exists' => filled($file->path) && Storage::fileExists($file->path),
                ])
                ->values()
                ->all() ?? [],
            'comments' => $reclaim->Comments
                ->map(fn ($comment) => [
                    'author' => $comment->User?->name ?: 'Usuário não informado',
                    'email' => $comment->User?->email,
                    'message' => $comment->message,
                    'created_at' => $comment->created_at?->format('d/m/Y H:i:s') ?? '—',
                ])
                ->values()
                ->all(),
        ];

        $this->dispatchBrowserEvent('show-ri-reason-modal');
    }

    public function downloadReturnFile(int $fileId)
    {
        $noteId = $this->returnDetails['note_id'] ?? null;

        if (!$noteId) {
            abort(404);
        }

        $file = File::query()
            ->where('note_id', $noteId)
            ->findOrFail($fileId);

        if (blank($file->path) || !Storage::fileExists($file->path)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'ARQUIVO INDISPONÍVEL',
                'html' => 'O registro existe, mas o arquivo físico não foi localizado.',
                'timer' => 4000,
            ]);

            return null;
        }

        $extension = $file->ext ?: pathinfo($file->file_name, PATHINFO_EXTENSION);
        $downloadName = pathinfo($file->file_name, PATHINFO_EXTENSION)
            ? $file->file_name
            : $file->file_name.($extension ? '.'.$extension : '');

        return Storage::download($file->path, $downloadName);
    }

    protected function returnInternDetailOrigin(Reclaim $reclaim): string
    {
        if ($reclaim->Viabilities->isNotEmpty()) {
            return 'Viabilidade';
        }

        if ($reclaim->Waiting) {
            return 'Contratação';
        }

        if ($reclaim->Approvals->isNotEmpty()) {
            return 'Aprovação';
        }

        if ($reclaim->Externals->isNotEmpty()) {
            return 'Órgão Externo';
        }

        return 'Não identificada';
    }
}
