<?php

namespace App\Http\Livewire\Components\D5;

use App\Models\EvidenceFile;
use App\Models\Note;
use App\Services\Files\EvidenceFileService;
use Livewire\Component;

class D5details extends Component
{
    public $five;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openD5Details',
    ];

    public function openD5Details(Note $note)
    {
        $this->five = $note->load([
            'FiveNote.note:id,note',
            'FiveNote.company:id,name',
            'FiveNote.EvidenceFiles',
            'FiveNote.Comments',
            'FiveNote.Productions:id,note_id,service_id,user_id,company_id,created_at,completed,completed_at,status',
            'FiveNote.Productions.User:id,name',
            'FiveNote.Productions.Service:uuid,service',
            'FiveNote.Productions.Company:id,name',
            'FiveNote.Productions.Analise:id,production_id,conclusion,info',
        ])?->fiveNote;

        if ($this->five) {
            $this->dispatchBrowserEvent('showModal', [
            'id' => 'fiveNoteModal',
        ]);
        }
    }

    public function downloadFile(int $fileId, EvidenceFileService $service)
    {
        abort_unless($this->five && $this->five->EvidenceFiles->contains('id', $fileId), 403);

        $file = EvidenceFile::findOrFail($fileId);

        if (!$service->exists($file)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO INDISPONÍVEL',
                'text'     => 'O arquivo não foi localizado no storage.',
                'timer'    => 5000,
            ]);

            return;
        }

        return $service->download($file);
    }

    public function render()
    {
        return view('livewire.components.d5.d5details');
    }
}
