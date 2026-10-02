<?php

namespace App\Http\Livewire\Components\D5;

use App\Models\EvidenceFile;
use App\Models\Note;
use App\Services\Files\EvidenceFileService;
use Livewire\Component;

class D5details extends Component
{
    public $five;

    public int $partnerEvidencePage = 1;

    public int $inspectionEvidencePage = 1;

    public int $legacyEvidencePage = 1;

    public int $evidencePerPage = 6;

    public string $evidenceTab = 'partner';

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openD5Details',
    ];

    public function openD5Details(Note $note, ?int $fiveNoteId = null)
    {
        $this->reset(['partnerEvidencePage', 'inspectionEvidencePage', 'legacyEvidencePage', 'evidenceTab']);

        $note->load([
            "FiveNotes" => fn ($q) => $q->with([
                "note:id,note,rubrica",
                "note.Productions:id,note_id,service_id,user_id,company_id,created_at,att_at,completed,completed_at,status",
                "note.Productions.User:id,name",
                "WorkReport:id,selected_final_scopes",
                "company:id,name",
                "answeredBy:id,name",
                "EvidenceFiles",
                "Comments",
                "productions:id,note_id,service_id,user_id,company_id,created_at,att_at,completed,completed_at,status",
                "productions.User:id,name",
                "productions.Service:uuid,service",
                "productions.Company:id,name",
                "productions.Analise:id,production_id,conclusion,info",
                "productions.WorkReportFlowProductions.WorkReport:id,selected_final_scopes",
            ]),
        ]);

        $fiveNotes = $note->FiveNotes ?? collect();
        $this->five = $fiveNoteId
            ? $fiveNotes->firstWhere("id", $fiveNoteId)
            : ($fiveNotes->firstWhere("work_report_id", null) ?? $fiveNotes->first());

        if ($this->five) {
            $this->dispatchBrowserEvent("showModal", [
                "id" => "fiveNoteModal",
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

    public function previousEvidencePage(string $group): void
    {
        $property = $this->evidencePageProperty($group);
        $this->{$property} = max(1, $this->{$property} - 1);
    }

    public function nextEvidencePage(string $group, int $lastPage): void
    {
        $property = $this->evidencePageProperty($group);
        $this->{$property} = min($lastPage, $this->{$property} + 1);
    }

    public function selectEvidenceTab(string $group): void
    {
        $this->evidencePageProperty($group);
        $this->evidenceTab = $group;
    }

    private function evidencePageProperty(string $group): string
    {
        return match ($group) {
            'partner'    => 'partnerEvidencePage',
            'inspection' => 'inspectionEvidencePage',
            'legacy'     => 'legacyEvidencePage',
            default      => throw new \InvalidArgumentException('Grupo de evidências inválido.'),
        };
    }

    public function render()
    {
        return view('livewire.components.d5.d5details');
    }
}
