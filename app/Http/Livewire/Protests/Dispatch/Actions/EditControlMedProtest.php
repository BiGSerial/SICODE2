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
    public string $reason_close = '';

    // UI aux
    public string $userSearch = '';
    public $userList = [];
    public bool $showReasonClose = false;

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

    protected function resolveSlaDefault(): ?string
    {
        $current = $this->job?->sla_due_at;

        if ($current) {
            return $current->copy()->setMinute(0)->setSecond(0)->format('Y-m-d H:i');
        }

        $medProtest = $this->job?->medProtest;
        $protest = $medProtest?->protest;

        if (!$medProtest || !$protest) {
            return null;
        }

        if ($protest->tipoNota === 'NA') {
            $date = $protest->dtConclusaoDesej;
        } else {
            $date = $medProtest->dtFimMedidaDesej;
        }

        if (!$date) {
            return null;
        }

        if ($date->lt(now())) {
            $date = now();
        }

        $allowedHours = [0, 8, 12, 18];
        $hour = $date->hour;

        $closestHour = collect($allowedHours)->first(function ($value) use ($hour) {
            return $value >= $hour;
        });

        if ($closestHour === null) {
            $closestHour = $allowedHours[array_key_first($allowedHours)];
            $date = $date->addDay();
        }

        $date = $date->copy()->setHour($closestHour)->setMinute(0)->setSecond(0);

        return $date->format('Y-m-d H:i');
    }

    public function openJobEditor(ProtestJob $job)
    {
        $this->job = $job->load('owner', 'creator', 'medProtest.protest', 'events');

        $this->owner_id      = $this->job->owner_id;
        $this->priority      = $this->job->priority->value;
        $this->is_advance    = (bool)$this->job->is_advance;
        $this->need_evidence = (bool)$this->job->need_evidence;
        $this->sla_due_at    = $this->resolveSlaDefault();
        $this->notes         = $this->job->notes ?? '';
        $this->reason_close  = '';
        $this->showReasonClose = false;

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
        $this->closeModalAndReset();

    }

    // reabrir (caso DONE ou CANCELED → REOPENED → ASSIGNED)
    public function reopenJob()
    {
        if (!$this->job) {
            return;
        }

        try {
            $this->job->reopen('Reaberta manualmente por ' . auth()->user()->name);
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
        $this->closeModalAndReset();

    }

    // finalizar (DONE)
    public function promptFinishReason(): void
    {
        if (!$this->job || $this->job->status?->value === ProtestJobStatus::DONE->value) {
            return;
        }

        $this->showReasonClose = true;
    }

    public function cancelFinishReason(): void
    {
        $this->showReasonClose = false;
        $this->reason_close = '';
        $this->resetErrorBag('reason_close');
    }

    public function finishJob()
    {
        if (!$this->job) {
            return;
        }

        $this->validate([
            'reason_close' => 'required|string|max:5000',
        ]);

        try {
            $this->prepareJobForFinish();

            $this->job->finish([
                'finished_by' => auth()->id(),
                'reason'      => 'Concluído manualmente por ' . auth()->user()->name,

            ], $this->reason_close);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Não foi possível finalizar a atividade.',
            ]);
            return;
        }

        $this->job->confirmJob();

        $this->cancelFinishReason();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade marcada como concluída!',
        ]);

        $this->emit('refreshJobEditor');
        $this->closeModalAndReset();
    }

    protected function prepareJobForFinish(): void
    {
        if (!$this->job || !$this->job->status) {
            return;
        }

        $currentStatus = $this->job->status;

        if ($currentStatus === ProtestJobStatus::DONE) {
            return;
        }

        if ($currentStatus === ProtestJobStatus::CANCELED) {
            $this->job->reopen('Reaberta automaticamente para conclusão por ' . auth()->user()->name);
            $currentStatus = $this->job->status;
        }

        if (in_array($currentStatus->value, [
            ProtestJobStatus::OPENED->value,
            ProtestJobStatus::ASSIGNED->value,
            ProtestJobStatus::WAITING->value,
            ProtestJobStatus::REOPENED->value,
        ], true)) {
            $this->job->start();
        }
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
        $this->emitUp('refreshComponent');
    }

    public function closeEditor()
    {
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'editProtestJobModal',
        ]);

        $this->resetEditor();
    }


    protected function closeModalAndReset(): void
    {
        $this->emit('refreshComponent');

        $this->resetEditor();

        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'editProtestJobModal',
        ]);


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
            'reason_close',
            'showReasonClose',
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
