<?php

namespace App\Http\Livewire\Admin\Control;

use App\Models\{Company, EvidenceFile, FiveNote, Production};
use App\Services\Files\EvidenceFileService;
use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use Livewire\Component;

class D5Edit extends Component
{
    public ?FiveNote $five = null;

    public $note;

    public $lockCompleted = false;

    public $productionId;

    public $availableProductions = [];
    public $availableWorkReports = [];

    public $linkedProductions = [];

    public $pendingEvidenceSave = false;

    public $companies;

    protected $listeners = [
        'getInfoResponse',
        'evidenceSaved' => 'onEvidenceSaved',
        'resetForm'     => 'resetForm',
    ];

    protected function rules(): array
    {
        return [
            'five.note_d5'          => ['nullable', 'string', 'max:191'],
            'five.note_id'          => ['nullable', 'integer'],
            'five.loc_install'      => ['nullable', 'string', 'max:191'],
            'five.conjunto'         => ['nullable', 'string', 'max:191'],
            'five.pep'              => ['nullable', 'string', 'max:191'],
            'five.e_pep'            => ['nullable', 'string', 'max:191'],
            'five.codify'           => ['nullable', 'string', 'max:191'],
            'five.sintoms'          => ['nullable', 'string'],
            'five.reason'           => ['nullable', 'string', 'max:191'],
            'five.description'      => ['nullable', 'string'],
            'five.company_id'       => ['nullable', 'string', 'exists:companies,id'],
            'five.name'             => ['nullable', 'string', 'max:191'],
            'five.dispatch_at'      => ['nullable', 'date'],
            'five.visible_partner'  => ['boolean'],
            'five.is_completed'     => ['boolean'],
            'five.completed_at'     => ['nullable', 'date'],
            'five.is_supervisioned' => ['boolean'],
            'five.supervisioned_at' => ['nullable', 'date'],
            'five.is_payed'         => ['boolean'],
            'five.payed_at'         => ['nullable', 'date'],
            'five.is_archived'      => ['boolean'],
            'five.isPassive'        => ['boolean'],
            'five.work_report_id'  => ['nullable', 'integer', 'exists:work_reports,id'],
            'five.returned'         => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->companies = Company::orderBy('name')->get(['id', 'name']);
    }

    public function getInfoResponse(FiveNote $five): void
    {
        $this->resetForm(false);
        $this->five          = $five->load(['note.WorkForms', 'note', 'company', 'WorkReport', 'productions.user', 'productions.service', 'EvidenceFiles']);
        $this->note          = $this->five->note;
        $this->availableWorkReports = $this->five->note?->WorkForms?->sortByDesc("id")->map(fn ($report) => ["id" => $report->id, "selected_final_scopes" => $report->selected_final_scopes])->values()->all() ?? [];
        $this->lockCompleted = (bool) ($this->five->is_supervisioned || $this->five->supervisioned_at || $this->five->is_archived);

        $this->five->dispatch_at      = $this->formatDateTimeLocal($this->five->dispatch_at);
        $this->five->completed_at     = $this->formatDateTimeLocal($this->five->completed_at);
        $this->five->supervisioned_at = $this->formatDateTimeLocal($this->five->supervisioned_at);
        $this->five->payed_at         = $this->formatDateTimeLocal($this->five->payed_at);

        $this->productionId = null;
        $this->refreshProductionLists();
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminEditFiveModal',
        ]);
    }

    public function save(): void
    {
        if (!$this->five) {
            return;
        }

        $this->validate();

        if ($this->lockCompleted) {
            $this->five->is_completed = false;
            $this->five->completed_at = null;
        }

        $validWorkReportIds = collect($this->availableWorkReports)->pluck("id")->map(fn ($id) => (int) $id);
        if ($this->five->work_report_id && !$validWorkReportIds->contains((int) $this->five->work_report_id)) {
            $this->addError("five.work_report_id", "O informe selecionado nao pertence a esta nota.");
            return;
        }
        $previousWorkReportId = (int) ($this->five->getOriginal("work_report_id") ?? 0);
        $this->five->save();
        $workReportIdsToRefresh = collect([$previousWorkReportId, (int) ($this->five->work_report_id ?? 0)])->filter()->unique();
        foreach ($workReportIdsToRefresh as $workReportId) {
            app(WorkReportCurrentStatusRefresher::class)->refresh((int) $workReportId);
        }
        $this->pendingEvidenceSave = true;
        $this->emitTo('files.evidence.upload-evidence', 'saveEvidences', $this->five->id);
    }

    public function addProduction(?int $productionId = null): void
    {
        $productionId = $productionId ?: $this->productionId;

        if (!$this->five || !$productionId) {
            return;
        }

        $production = Production::with(['note', 'service', 'user'])
            ->find($productionId);

        if (!$production) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Producao nao encontrada',
                'timer'    => 2000,
            ]);

            return;
        }

        if ($production->note_id !== $this->five->note_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Producao nao pertence a mesma nota',
                'timer'    => 2500,
            ]);

            return;
        }

        $production->dfive = true;
        $production->save();

        $this->five->productions()->syncWithoutDetaching([$production->id]);
        $this->five->load('productions.user', 'productions.service');
        $this->refreshProductionLists();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Producao vinculada a D5',
            'timer'    => 2000,
        ]);

        $this->productionId = null;
    }

    public function removeProduction(int $productionId): void
    {
        if (!$this->five) {
            return;
        }

        $production = Production::find($productionId);

        if (!$production) {
            return;
        }

        $production->dfive = false;
        $production->save();

        $this->five->productions()->detach($productionId);
        $this->five->load('productions.user', 'productions.service');
        $this->refreshProductionLists();
    }

    public function toggleProductionD5(int $productionId): void
    {
        $production = Production::find($productionId);

        if (!$production) {
            return;
        }

        $production->dfive = !$production->dfive;
        $production->save();

        $this->refreshProductionLists();
    }

    private function refreshProductionLists(): void
    {
        if (!$this->five) {
            $this->availableProductions = [];
            $this->linkedProductions    = [];

            return;
        }

        $all = Production::with(['service', 'user', 'WorkReportFlowProductions:id,work_report_id,production_id,stage,final_scope,is_current'])
            ->where('note_id', $this->five->note_id)
            ->orderByDesc('created_at')
            ->get();

        $all->each(function ($production): void {
            $production->setAttribute("inform_ids", $production->WorkReportFlowProductions
                ->where("is_current", true)
                ->pluck("work_report_id")
                ->filter()
                ->unique()
                ->values()
                ->all());
        });
        $linkedIds                  = $this->five->productions->pluck('id')->all();
        $this->linkedProductions    = $all->whereIn('id', $linkedIds)->values()->all();
        $this->availableProductions = $all->whereNotIn('id', $linkedIds)->values()->all();
    }

    public function downloadEvidence(EvidenceFile $file)
    {
        $service = app(EvidenceFileService::class);

        if ($service->exists($file)) {
            return $service->download($file);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Arquivo inexistente!',
            'timer'    => 2000,
        ]);
    }

    public function deleteEvidence(EvidenceFile $file): void
    {
        $file->delete();
        $this->five?->load('EvidenceFiles');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Arquivo removido com sucesso!',
        ]);
    }

    public function onEvidenceSaved(): void
    {
        $this->five?->load('EvidenceFiles');

        if (!$this->pendingEvidenceSave) {
            return;
        }

        $this->pendingEvidenceSave = false;

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'D5 atualizada com sucesso!',
            'timer'    => 2500,
        ]);

        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm(false);
        $this->emitUp('refresh_list');
    }

    public function resetForm(bool $refresh = true): void
    {
        $this->resetErrorBag();
        $this->five                 = null;
        $this->note                 = null;
        $this->lockCompleted        = false;
        $this->productionId         = null;
        $this->availableProductions = [];
        $this->linkedProductions    = [];
        $this->availableWorkReports = [];
        $this->pendingEvidenceSave  = false;
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');

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
        return view('livewire.admin.control.d5-edit');
    }
}
