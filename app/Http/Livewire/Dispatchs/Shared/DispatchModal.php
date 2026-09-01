<?php

namespace App\Http\Livewire\Dispatchs\Shared;

use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Services\Dispatch\DispatchContextResolver;
use App\Services\Dispatch\DispatchException;
use App\Services\Dispatch\DispatchWorkflowService;
use App\Services\WorkReports\WorkReportFinalScopeOptions;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DispatchModal extends Component
{
    public $service;
    public $notes;
    public $company_l;
    public $user_l;
    public string $company_s = '';
    public string $user_s = '';
    public string $type = '1';
    public string $search_user = '';
    public array $additionalData = [];
    public array $finalScopeOptions = [];
    public array $finalScopeSelections = [];
    public bool $contractMode = false;
    public bool $requiresDd = false;
    public bool $requiresFinalScope = false;
    public array $sourceProductionIdsByNote = [];

    protected $listeners = [
        'openForNotes' => 'openForNotes',
        'openForProductions' => 'openForProductions',
        'confirm_dispatch_modal' => 'confirmedAtt',
    ];

    public function mount(string $serviceId): void
    {
        $this->service = Service::where('uuid', $serviceId)->firstOrFail();
        $this->notes = collect();
        $this->company_l = collect();
        $this->user_l = collect();
        $this->contractMode = (bool) auth()->user()?->contract;
    }

    public function openForNotes(array $noteIds): void
    {
        $noteIds = collect($noteIds)->filter()->unique()->values();

        if (!$noteIds->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma nota foi selecionada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        $this->resetModalState();
        $this->notes = Note::with($this->modalNoteRelations())->find($noteIds);
        $this->loadDispatchCompanies();
        $this->preselectContractDispatchCompany();
        $this->applyContractModeDefaults();
        $this->additionalData = [];
        $contextResolver = app(DispatchContextResolver::class);
        $scopeAwareService = in_array($contextResolver->serviceKey($this->service), ['supervision', 'payment'], true);

        foreach ($this->notes as $index => $note) {
            $this->additionalData[$index] = SicodeRules::dispatchDdFor($note, $this->service->uuid) ?? '';
            $this->prepareFinalScopeSelection($note);

            $context = $contextResolver->for($note, $this->service);
            $this->requiresDd = $this->requiresDd || (bool) ($context['requires_dd'] ?? false);
            $this->requiresFinalScope = $this->requiresFinalScope || (
                $scopeAwareService
                && count($this->finalScopeOptions[$note->id] ?? []) > 0
            );
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'add_mass_notes',
        ]);
    }

    public function openForProductions(array $productionIds): void
    {
        $productionIds = collect($productionIds)->filter()->unique()->values();

        if (!$productionIds->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma atividade foi selecionada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        $this->resetModalState();

        $productions = Production::with($this->modalProductionRelations())
            ->whereIn('id', $productionIds)
            ->where('service_id', $this->service->uuid)
            ->where('completed', false)
            ->where('confirmed', false)
            ->get();

        $this->notes = $productions->pluck('Note')->filter()->values();
        $this->sourceProductionIdsByNote = $productions
            ->filter(fn ($production) => $production->Note)
            ->mapWithKeys(fn ($production) => [(string) $production->note_id => (int) $production->id])
            ->all();

        if (!$this->notes->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma atividade aberta foi encontrada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        $this->loadDispatchCompanies();
        $this->preselectContractDispatchCompany();
        $this->applyContractModeDefaults();

        $contextResolver = app(DispatchContextResolver::class);
        $scopeAwareService = in_array($contextResolver->serviceKey($this->service), ['supervision', 'payment'], true);

        foreach ($this->notes as $index => $note) {
            $this->additionalData[$index] = SicodeRules::dispatchDdFor($note, $this->service->uuid) ?? '';
            $this->prepareFinalScopeSelection($note);

            $context = $contextResolver->for($note, $this->service);
            $this->requiresDd = $this->requiresDd || (bool) ($context['requires_dd'] ?? false);
            $this->requiresFinalScope = $this->requiresFinalScope || (
                $scopeAwareService
                && count($this->finalScopeOptions[$note->id] ?? []) > 0
            );
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'add_mass_notes',
        ]);
    }

    public function dispatchCompanyChanged($companyId): void
    {
        $this->company_s = (string) $companyId;
        $this->loadDispatchUsers();
    }

    public function dispatchTypeChanged($type): void
    {
        if ($this->contractMode && (string) $type !== '2') {
            $this->type = '2';
            $this->loadDispatchUsers();
            return;
        }

        $this->type = (string) $type;

        if ($this->type === '2') {
            $this->loadDispatchUsers();
            return;
        }

        $this->user_s = '';
        $this->user_l = collect();
    }

    public function loadDispatchUsers(): void
    {
        $this->user_s = '';

        if (!$this->company_s) {
            $this->user_l = collect();
            return;
        }

        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
            ->where(function ($q) {
                $q->where('company_id', $this->company_s)
                    ->orWhereRelation('Employee.Contract', 'company_id', $this->company_s)
                    ->orWhereRelation('Companies', 'companies.id', $this->company_s);
            })
            ->when($this->search_user, function ($q) {
                return $q->where('name', 'like', '%' . $this->search_user . '%');
            })
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function confirmAtt(): void
    {
        if ($this->contractMode && $this->type !== '2') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Usuario com contrato deve atribuir a atividade, nao enviar para pilha.',
                'timer' => 4000,
            ]);

            return;
        }

        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma empresa foi selecionada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        if ($this->type === '2' && !$this->user_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhum usuário foi selecionado para despacho individual!',
                'timer' => 2500,
            ]);

            return;
        }

        $company = Company::find($this->company_s);
        $targetUser = $this->type === '2' ? User::find($this->user_s) : null;
        $para = $targetUser
            ? $targetUser->name . ' da ' . $company?->name
            : $company?->name;

        $this->dispatchBrowserEvent('alertar', [
            'target' => 'dispatchs.shared.dispatch-modal',
            'title' => 'Confirmar Despachar',
            'msg' => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Despache!',
            'btnCanceltxt' => 'Não, Cancele',
            'action' => 'confirm_dispatch_modal',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhuma nota foi despachada.',
        ]);
    }

    public function confirmedAtt(): void
    {
        if (!in_array((string) $this->type, ['1', '2'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione o tipo de despacho.',
                'timer' => 2500,
            ]);

            return;
        }

        if ($this->contractMode && $this->type !== '2') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Usuario com contrato deve atribuir a atividade, nao enviar para pilha.',
                'timer' => 4000,
            ]);

            return;
        }

        try {
            $workflow = app(DispatchWorkflowService::class);
            $scopeOptions = app(WorkReportFinalScopeOptions::class);
            $contextResolver = app(DispatchContextResolver::class);
            $scopeAwareService = in_array($contextResolver->serviceKey($this->service), ['supervision', 'payment'], true);
            $company = Company::findOrFail($this->company_s);
            $targetUser = (string) $this->type === '2' ? User::findOrFail($this->user_s) : null;
            $actor = auth()->user();

            if ($scopeAwareService) {
                foreach ($this->notes as $note) {
                    $available = $scopeOptions->forNote($note);
                    $selected = $this->selectedFinalScopesForNote($note);

                    if (count($available) > 1 && empty($selected)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon' => 'warning',
                            'title' => "Selecione o escopo fiscalizado para a nota {$note->note}.",
                            'timer' => 6000,
                        ]);

                        return;
                    }
                }
            }

            DB::transaction(function () use ($workflow, $company, $targetUser, $actor) {
                foreach ($this->notes as $key => $note) {
                    $dd = $this->additionalData[$key] ?? null;
                    $finalScopes = $this->selectedFinalScopesForNote($note);
                    $sourceProductionId = $this->sourceProductionIdsByNote[(string) $note->id] ?? null;

                    if ($sourceProductionId) {
                        $production = Production::findOrFail($sourceProductionId);

                        if ($targetUser) {
                            $workflow->assignProduction($production, $company, $targetUser, $actor, false, $finalScopes);
                        } else {
                            $workflow->moveProductionToCompanyStack($production, $company, $actor, $finalScopes);
                        }

                        continue;
                    }

                    if ($targetUser) {
                        $workflow->dispatchToUser($note, $this->service, $company, $targetUser, $actor, $dd, $finalScopes);
                    } else {
                        $workflow->dispatchToCompanyStack($note, $this->service, $company, $actor, $dd, $finalScopes);
                    }
                }
            });
        } catch (DispatchException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => $e->getMessage(),
                'timer' => 6000,
            ]);

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Erro ao despachar as Notas/OVs.',
                'timer' => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Notas Despachadas com sucesso!',
            'timer' => 2500,
        ]);

        $this->closeAll();
        $this->emitUp('refresh_dispatch');
        $this->emitUp('refresh_list');
    }

    public function closeAll(): void
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->resetModalState();
    }

    public function render()
    {
        return view('livewire.dispatchs.shared.dispatch-modal');
    }

    private function loadDispatchCompanies(): void
    {
        if (auth()->user()?->contract && $this->notes->count()) {
            $companyIds = Production::whereIn('note_id', $this->notes->pluck('id'))
                ->where('service_id', $this->service->uuid)
                ->whereIn('company_id', SicodeRules::visibleCompanyIdsFor(auth()->user()))
                ->whereNull('user_id')
                ->where('completed', false)
                ->where('confirmed', false)
                ->distinct()
                ->pluck('company_id');

            if (!$companyIds->count()) {
                $companyIds = collect(SicodeRules::visibleCompanyIdsFor(auth()->user()));
            }

            $this->company_l = Company::whereIn('id', $companyIds)
                ->orderBy('name', 'ASC')
                ->get();

            return;
        }

        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->when(auth()->user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());

                return count($companyIds)
                    ? $q->whereIn('id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    private function preselectContractDispatchCompany(): void
    {
        if (!auth()->user()?->contract || !$this->notes->count()) {
            return;
        }

        $companyIds = $this->notes
            ->map(fn ($note) => SicodeRules::openCompanyStackProductionFor($note, auth()->user(), $this->service->uuid)?->company_id)
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($companyIds->count() === 1) {
            $this->company_s = $companyIds->first();
        }
    }

    private function resetModalState(): void
    {
        $this->notes = collect();
        $this->company_l = collect();
        $this->user_l = collect();
        $this->company_s = '';
        $this->user_s = '';
        $this->type = '1';
        $this->search_user = '';
        $this->additionalData = [];
        $this->finalScopeOptions = [];
        $this->finalScopeSelections = [];
        $this->contractMode = (bool) auth()->user()?->contract;
        $this->requiresDd = false;
        $this->requiresFinalScope = false;
        $this->sourceProductionIdsByNote = [];
    }

    private function applyContractModeDefaults(): void
    {
        if (!$this->contractMode) {
            return;
        }

        $this->type = '2';
        $this->loadDispatchUsers();

        if ($this->user_l->contains('id', auth()->id())) {
            $this->user_s = (string) auth()->id();
        }
    }

    private function prepareFinalScopeSelection(Note $note): void
    {
        $options = app(WorkReportFinalScopeOptions::class)->forNote($note);
        $this->finalScopeOptions[$note->id] = $options;
        $this->finalScopeSelections[$note->id] = [];

        if (count($options) === 1) {
            $this->finalScopeSelections[$note->id][$options[0]['scope']] = true;
        }
    }

    private function selectedFinalScopesForNote(Note $note): array
    {
        $selected = collect($this->finalScopeSelections[$note->id] ?? [])
            ->filter(fn ($enabled) => (bool) $enabled)
            ->keys()
            ->all();

        return app(WorkReportFinalScopeOptions::class)
            ->validScopesForNote($note, $selected);
    }

    private function modalNoteRelations(): array
    {
        return [
            'Wpas:id,note_id,production_id,service_id,dd',
            'Productions' => fn ($q) => $q->select([
                'id',
                'note_id',
                'service_id',
                'user_id',
                'company_id',
                'completed',
                'confirmed',
                'status',
                'partial',
                'dfive',
                'created_at',
                'completed_at',
                'dt_note',
                'status_note',
            ])->where('service_id', $this->service->uuid)->orderByDesc('created_at'),
            'WorkForm' => fn ($q) => $q->select([
                'id',
                'note_id',
                'company_id',
                'informed_at',
                'created_at',
                'rejected',
                'selected_final_scopes',
            ]),
            'WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem']),
            'FiveNote:id,note_id,is_supervisioned,is_completed,is_archived,completed_at',
            'Partials' => fn ($q) => $q->select([
                'id',
                'note_id',
                'company_id',
                'allow',
                'deny',
                'payment',
                'supervision',
                'supervision_at',
                'created_at',
            ])
                ->where('allow', true)
                ->where('deny', false)
                ->where('supervision', true)
                ->where('payment', false)
                ->orderByDesc('created_at'),
        ];
    }

    private function modalProductionRelations(): array
    {
        $relations = [];

        foreach ($this->modalNoteRelations() as $relation => $constraint) {
            if (is_int($relation)) {
                $relations[] = 'Note.' . $constraint;
                continue;
            }

            $relations['Note.' . $relation] = $constraint;
        }

        return $relations;
    }
}
