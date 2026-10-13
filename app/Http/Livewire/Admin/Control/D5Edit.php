<?php

namespace App\Http\Livewire\Admin\Control;

use App\Models\{Company, EvidenceFile, FiveNote, Production, WorkReport, WorkReportFlowProduction};
use App\Services\Files\EvidenceFileService;
use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class D5Edit extends Component
{
    public ?FiveNote $five = null;

    public $note;

    public $productionId;

    public $deleteProductionId;

    public $dateInputs = [];

    public $productionWorkReportIds = [];

    public $availableProductions = [];

    public $availableWorkReports = [];

    public $linkedProductions = [];

    public $pendingEvidenceSave = false;

    public $companies;

    protected $listeners = [
        'getInfoResponse',
        'evidenceSaved'           => 'onEvidenceSaved',
        'resetForm'               => 'resetForm',
        'confirmDeleteProduction' => 'deleteProduction',
    ];

    protected function rules(): array
    {
        return [
            'five.note_d5'                => ['nullable', 'string', 'max:191'],
            'five.note_id'                => ['nullable', 'integer'],
            'five.loc_install'            => ['nullable', 'string', 'max:191'],
            'five.conjunto'               => ['nullable', 'string', 'max:191'],
            'five.pep'                    => ['nullable', 'string', 'max:191'],
            'five.e_pep'                  => ['nullable', 'string', 'max:191'],
            'five.codify'                 => ['nullable', 'string', 'max:191'],
            'five.sintoms'                => ['nullable', 'string'],
            'five.reason'                 => ['nullable', 'string', 'max:191'],
            'five.description'            => ['nullable', 'string'],
            'five.company_id'             => ['nullable', 'string', 'exists:companies,id'],
            'five.name'                   => ['nullable', 'string', 'max:191'],
            'dateInputs.dispatch_at'      => ['nullable', 'date'],
            'five.visible_partner'        => ['boolean'],
            'five.is_completed'           => ['boolean'],
            'dateInputs.completed_at'     => ['nullable', 'date'],
            'five.is_supervisioned'       => ['boolean'],
            'dateInputs.supervisioned_at' => ['nullable', 'date'],
            'five.is_payed'               => ['boolean'],
            'dateInputs.payed_at'         => ['nullable', 'date'],
            'five.is_archived'            => ['boolean'],
            'five.isPassive'              => ['boolean'],
            'five.work_report_id'         => ['nullable', 'integer', 'exists:work_reports,id'],
            'five.returned'               => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->companies = Company::orderBy('name')->get(['id', 'name']);
    }

    public function getInfoResponse(FiveNote $five): void
    {
        $this->resetForm(false);
        $this->five                 = $five->load(['note.WorkForms', 'note', 'company', 'WorkReport', 'productions.user', 'productions.service', 'EvidenceFiles']);
        $this->note                 = $this->five->note;
        $this->availableWorkReports = $this->five->note?->WorkForms?->sortByDesc("id")->map(fn ($report) => ["id" => $report->id, "selected_final_scopes" => $report->selected_final_scopes])->values()->all() ?? [];

        foreach ($this->dateAttributes() as $attribute) {
            $this->dateInputs[$attribute] = $this->formatDateTimeLocal($this->five->getRawOriginal($attribute));
        }

        foreach (['visible_partner', 'is_completed', 'is_supervisioned', 'is_payed', 'is_archived', 'isPassive', 'returned'] as $attribute) {
            $this->five->{$attribute} = (bool) $this->five->{$attribute};
        }

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

        $validWorkReportIds = collect($this->availableWorkReports)->pluck("id")->map(fn ($id) => (int) $id);

        if ($this->five->work_report_id && !$validWorkReportIds->contains((int) $this->five->work_report_id)) {
            $this->addError("five.work_report_id", "O informe selecionado nao pertence a esta nota.");

            return;
        }
        $previousWorkReportId = (int) ($this->five->getOriginal("work_report_id") ?? 0);
        $dateValues           = $this->dateValuesForStorage();
        $this->five->save();
        DB::table('five_notes')->where('id', $this->five->id)->update($dateValues);
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

        $this->five->productions()->detach($productionId);

        if (!$production->fiveNotes()->exists()) {
            $production->dfive = false;
            $production->save();
        }

        $this->five->load('productions.user', 'productions.service');
        $this->refreshProductionLists();
    }

    public function associateWorkReport(int $productionId): void
    {
        if (!$this->five) {
            return;
        }

        $production = Production::query()
            ->with('service')
            ->whereKey($productionId)
            ->where('note_id', $this->five->note_id)
            ->whereHas('fiveNotes', fn ($fiveNotes) => $fiveNotes->whereKey($this->five->id))
            ->first();

        $workReportId       = (int) ($this->productionWorkReportIds[$productionId] ?? 0);
        $validWorkReportIds = collect($this->availableWorkReports)->pluck('id')->map(fn ($id) => (int) $id);

        if (!$production || !$validWorkReportIds->contains($workReportId)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione um informe valido desta nota',
            ]);

            return;
        }

        $workReport = WorkReport::query()->whereKey($workReportId)->where('note_id', $this->five->note_id)->where('canceled', false)->first();

        if (!$workReport) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informe indisponivel',
            ]);

            return;
        }

        $stage = $this->stageForProduction($production);
        DB::transaction(function () use ($production, $workReport, $stage): void {
            $previousWorkReportIds = WorkReportFlowProduction::query()
                ->where('production_id', $production->id)
                ->where('stage', $stage)
                ->where('is_current', true)
                ->pluck('work_report_id');

            WorkReportFlowProduction::query()
                ->where('production_id', $production->id)
                ->where('stage', $stage)
                ->where('is_current', true)
                ->where('work_report_id', '!=', $workReport->id)
                ->update(['is_current' => false]);

            // Um informe pode concentrar mais de uma Production na mesma etapa.
            // O vínculo desta D5 não deve desassociar as demais Productions.
            WorkReportFlowProduction::query()->updateOrCreate(
                [
                    'work_report_id' => $workReport->id,
                    'production_id'  => $production->id,
                    'stage'          => $stage,
                    'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
                ],
                [
                    'is_current' => true,
                    'linked_at'  => now(),
                    'linked_by'  => auth()->id(),
                    'source'     => 'admin_d5_control',
                    'metadata'   => ['five_note_id' => $this->five->id, 'note_id' => $production->note_id],
                ],
            );

            foreach ($previousWorkReportIds->push($workReport->id)->unique() as $id) {
                app(WorkReportCurrentStatusRefresher::class)->refresh((int) $id);
            }
        });

        $this->refreshProductionLists();
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "Informe #{$workReport->id} associado a Production #{$production->id}",
            'timer'    => 2000,
        ]);
    }

    public function requestDeleteProduction(int $productionId): void
    {
        $production = $this->findLinkedProduction($productionId);

        if (!$production) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Production nao encontrada nesta D5',
            ]);

            return;
        }

        $this->deleteProductionId = $production->id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => "Excluir Production #{$production->id}",
            'msg'           => 'A Production sera apagada definitivamente. Esta acao nao pode ser desfeita.',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, excluir',
            'btnCanceltxt'  => 'Cancelar',
            'action'        => 'confirmDeleteProduction',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg'    => 'A Production foi mantida.',
        ]);
    }

    public function deleteProduction(): void
    {
        if (!$this->deleteProductionId) {
            return;
        }

        try {
            DB::transaction(function (): void {
                $production = $this->findLinkedProduction($this->deleteProductionId, true);

                if (!$production) {
                    throw new \DomainException('A Production nao esta mais vinculada a esta D5.');
                }

                WorkReportFlowProduction::query()->where('production_id', $production->id)->delete();
                $production->delete();
            });
        } catch (\Throwable $exception) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Nao foi possivel excluir a Production',
                'text'     => $exception->getMessage(),
            ]);

            return;
        } finally {
            $this->deleteProductionId = null;
        }

        $this->five?->load('productions.user', 'productions.service');
        $this->refreshProductionLists();
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Production excluida',
            'timer'    => 2000,
        ]);
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

        $all = Production::with([
            'service',
            'user',
            'Analise:id,production_id',
            'd5Return:id,production_id',
            'WorkReportFlowProductions:id,work_report_id,production_id,stage,final_scope,is_current',
        ])
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
            $production->setAttribute('current_work_report_id', $production->WorkReportFlowProductions
                ->where('stage', $this->stageForProduction($production))
                ->where('is_current', true)
                ->value('work_report_id'));
        });
        $linkedIds                     = $this->five->productions->pluck('id')->all();
        $this->linkedProductions       = $all->whereIn('id', $linkedIds)->values()->all();
        $this->availableProductions    = $all->whereNotIn('id', $linkedIds)->values()->all();
        $this->productionWorkReportIds = collect($this->linkedProductions)
            ->mapWithKeys(fn (Production $production) => [$production->id => $production->current_work_report_id])
            ->all();
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
        $this->five                    = null;
        $this->note                    = null;
        $this->productionId            = null;
        $this->deleteProductionId      = null;
        $this->dateInputs              = [];
        $this->productionWorkReportIds = [];
        $this->availableProductions    = [];
        $this->linkedProductions       = [];
        $this->availableWorkReports    = [];
        $this->pendingEvidenceSave     = false;
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

    /** @return array<int, string> */
    private function dateAttributes(): array
    {
        return ['dispatch_at', 'completed_at', 'supervisioned_at', 'payed_at'];
    }

    /** @return array<string, ?string> */
    private function dateValuesForStorage(): array
    {
        $dates = [];

        foreach ($this->dateAttributes() as $attribute) {
            $value             = $this->dateInputs[$attribute] ?? null;
            $dates[$attribute] = blank($value)
                ? null
                : \Carbon\Carbon::parse($value, config('app.timezone'))->format('Y-m-d H:i:s');
        }

        return $dates;
    }

    private function findLinkedProduction(int $productionId, bool $lock = false): ?Production
    {
        if (!$this->five) {
            return null;
        }

        $query = Production::query()
            ->with(['WorkReportFlowProductions:id,production_id'])
            ->whereKey($productionId)
            ->where('note_id', $this->five->note_id)
            ->whereHas('fiveNotes', fn ($fiveNotes) => $fiveNotes->whereKey($this->five->id));

        if ($lock) {
            $query->lockForUpdate();
        }

        $production = $query->first();

        return $production;
    }

    private function stageForProduction(Production $production): string
    {
        $service = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) ($production->service?->service ?? '')));

        return str_contains($service, 'fiscal')
            ? WorkReportFlowProduction::STAGE_FISCALIZATION
            : (str_contains($service, 'public') ? WorkReportFlowProduction::STAGE_PUBLICATION : WorkReportFlowProduction::STAGE_PAYMENT);
    }

    public function render()
    {
        return view('livewire.admin.control.d5-edit');
    }
}
