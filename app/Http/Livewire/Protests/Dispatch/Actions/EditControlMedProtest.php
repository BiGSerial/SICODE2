<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Models\ProtestJob;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class EditControlMedProtest extends Component
{
    public ?ProtestJob $job = null;

    // form editável
    public ?string $owner_id = null;
    public string $priority = '';
    public bool $is_advance = false;
    public bool $need_evidence = false;
    public ?string $sla_due_at = null;
    public string $notes = ''; // pode ser "instrução / atualização"

    // UI aux
    public string $userSearch = '';
    public $userList = [];

    protected $listeners = [
        'openJobEditor', // recebe o ID do job
        'refreshJobEditor' => '$refresh',
    ];

    public function mount()
    {
        $this->userList = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    public function openJobEditor(ProtestJob $job)
    {
        $this->job = $job->load('owner', 'creator', 'medProtest.protest', 'events');

        $this->owner_id      = $this->job->owner_id;
        $this->priority      = $this->job->priority->value;
        $this->is_advance    = (bool)$this->job->is_advance;
        $this->need_evidence = (bool)$this->job->need_evidence;
        $this->sla_due_at    = optional($this->job->sla_due_at)?->format('Y-m-d H:i');
        $this->notes         = $this->job->notes ?? '';

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'editProtestJobModal',
        ]);
    }

    public function updatedUserSearch()
    {
        $needle = trim($this->userSearch);

        $this->userList = User::query()
            ->whereNull('deleted_at')
            ->when(
                $needle,
                fn ($q) =>
                $q->where('name', 'like', '%' . $needle . '%')
            )
            ->orderBy('name')
            ->get();
    }

    protected function validateJobEdit(): array
    {
        return $this->validate([
            'owner_id'      => 'required|exists:users,id',
            'priority'      => 'required|in:' .
                implode(',', array_map(fn ($e) => $e->value, ProtestJobPriority::cases())),
            'is_advance'    => 'boolean',
            'need_evidence' => 'boolean',
            'sla_due_at'    => 'nullable|date',
            'notes'         => 'nullable|string|max:5000',
        ]);
    }

    /* ===================== AÇÕES PRINCIPAIS ===================== */

    // salvar alterações normais (responsável, prioridade, flags, SLA, notes)
    public function saveJob()
    {
        if (!$this->job) {
            return;
        }

        $data = $this->validateJobEdit();

        DB::transaction(function () use ($data) {

            // se mudou o responsável
            if ($this->job->owner_id !== $data['owner_id']) {
                $this->job->reassignTo($data['owner_id'], auth()->id());
            }

            $this->job->update([
                'priority'      => ProtestJobPriority::from($data['priority']),
                'is_advance'    => $data['is_advance'] ?? false,
                'need_evidence' => $data['need_evidence'] ?? false,
                'sla_due_at'    => $data['sla_due_at'] ?? null,
                'notes'         => $data['notes'] ?? null,
            ]);

            $this->job->events()->create([
                'type'        => 'updated',
                'actor_id'    => auth()->id(),
                'meta'        => [
                    'changes' => 'priority/flags/sla/notes updated',
                ],
                'occurred_at' => now(),
            ]);
        });

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade atualizada!',
        ]);

        $this->emit('refreshJobEditor');
        $this->emitUp('refreshComponent');

    }

    // reabrir (caso DONE ou CANCELED → REOPENED → ASSIGNED)
    public function reopenJob()
    {
        if (!$this->job) {
            return;
        }

        try {
            $this->job->reopen('Reaberta manualmente');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Transição inválida para REOPENED.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade reaberta!',
        ]);

        $this->emit('refreshJobEditor');
    }

    // finalizar (DONE)
    public function finishJob()
    {
        if (!$this->job) {
            return;
        }

        try {
            $this->job->finish([
                'finished_by' => auth()->id(),
                'reason'      => 'Concluído manualmente',
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Não foi possível finalizar a atividade.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade marcada como concluída!',
        ]);

        $this->emit('refreshJobEditor');
    }

    // cancelar
    public function cancelJob()
    {
        if (!$this->job) {
            return;
        }

        try {
            $this->job->cancel('Cancelado manualmente');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Não foi possível cancelar.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade cancelada!',
        ]);

        $this->emit('refreshJobEditor');
    }

    public function closeEditor()
    {
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'editProtestJobModal',
        ]);

        $this->resetEditor();
    }

    protected function resetEditor(): void
    {
        $this->reset([
            'job',
            'owner_id',
            'priority',
            'is_advance',
            'need_evidence',
            'sla_due_at',
            'notes',
            'userSearch',
        ]);

        $this->userList = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.protests.dispatch.actions.edit-control-med-protest', [
            'priorityOptions' => ProtestJobPriority::cases(),
            'status'          => $this->job?->status,
        ]);
    }
}
