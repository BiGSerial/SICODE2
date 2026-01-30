<?php

namespace App\Http\Livewire\Admin\Control;

use App\Models\Note;
use Livewire\Component;

class NotesEdit extends Component
{
    public ?Note $note = null;

    protected $listeners = [
        'getInfoResponse',
        'resetForm' => 'resetForm',
    ];

    protected function rules(): array
    {
        return [
            'note.note' => ['nullable', 'string'],
            'note.created_by' => ['nullable', 'string'],
            'note.dt_created' => ['nullable'],
            'note.dt_status' => ['nullable'],
            'note.user' => ['nullable', 'string'],
            'note.value' => ['nullable', 'numeric'],
            'note.currency' => ['nullable', 'string'],
            'note.eq_venda' => ['nullable', 'string'],
            'note.numPedido' => ['nullable', 'string'],
            'note.client' => ['nullable', 'string'],
            'note.group1' => ['nullable', 'string'],
            'note.group2' => ['nullable', 'string'],
            'note.group3' => ['nullable', 'string'],
            'note.group4' => ['nullable', 'string'],
            'note.group5' => ['nullable', 'string'],
            'note.pze' => ['nullable', 'integer'],
            'note.num_material' => ['nullable', 'integer'],
            'note.material' => ['nullable', 'string'],
            'note.nexp' => ['nullable', 'string'],
            'note.lexp' => ['nullable', 'string'],
            'note.pep' => ['nullable', 'string'],
            'note.nstats' => ['nullable', 'string'],
            'note.status' => ['nullable', 'string'],
            'note.days' => ['nullable', 'integer'],
            'note.transaction' => ['nullable', 'string'],
            'note.validar_prazo' => ['nullable', 'string'],
            'note.rubrica' => ['nullable', 'string'],
            'note.pze_tratado' => ['nullable', 'integer'],
            'note.days_stat' => ['nullable', 'integer'],
            'note.pze_parecer' => ['nullable', 'string'],
            'note.days_left' => ['nullable', 'integer'],
            'note.mmgd' => ['boolean'],
            'note.type_note' => ['nullable', 'integer'],
            'note.centerjob' => ['nullable', 'string'],
            'note.doe' => ['boolean'],
            'note.postes' => ['nullable', 'integer'],
            'note.mesalization' => ['nullable', 'string'],
            'note.txpriority' => ['nullable', 'string'],
            'note.is45' => ['boolean'],
            'note.ma' => ['boolean'],
        ];
    }

    public function getInfoResponse(Note $note): void
    {
        $this->resetForm(false);
        $this->note = $note;

        $this->note->dt_created = $this->formatDateTimeLocal($this->note->dt_created);
        $this->note->dt_status = $this->formatDateTimeLocal($this->note->dt_status);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminNoteModal',
        ]);
    }

    public function save(): void
    {
        if (!$this->note) {
            return;
        }

        $this->validate();
        $this->note->save();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Nota atualizada com sucesso!',
            'timer'    => 2500,
        ]);

        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm(false);
        $this->emitUp('refresh_list');
    }

    public function resetForm(bool $refresh = true): void
    {
        $this->resetErrorBag();
        $this->note = null;

        if ($refresh) {
            $this->emitUp('refresh_list');
        }
    }

    private function formatDateTimeLocal($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d\TH:i')
                : \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.admin.control.notes-edit');
    }
}
