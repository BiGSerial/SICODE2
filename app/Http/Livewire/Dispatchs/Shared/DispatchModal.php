<?php

namespace App\Http\Livewire\Dispatchs\Shared;

use App\Models\{Company, FiveNote, Note, Notetimeline, Production, Service, User, WorkReport};
use App\Services\Dispatch\{DispatchContextResolver, DispatchException, DispatchWorkflowService};
use App\Services\WorkReports\WorkReportFinalScopeOptions;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DispatchModal extends Component
{
    public $service;

    public array $dispatchContextsByIndex = [];

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

    public array $targetWorkReportIdsByNote = [];

    public array $targetPartialIdsByContext = [];

    protected $listeners = [
        'openForNotes'           => 'openForNotes',
        'openForWorkReports'     => 'openForWorkReports',
        'openForProductions'     => 'openForProductions',
        'confirm_dispatch_modal' => 'confirmedAtt',
    ];

    public function mount(string $serviceId): void
    {
        $this->service      = Service::where('uuid', $serviceId)->firstOrFail();
        $this->notes        = collect();
        $this->company_l    = collect();
        $this->user_l       = collect();
        $this->contractMode = (bool) auth()->user()?->contract;
    }

    public function openForNotes(array $noteIds): void
    {
        $contexts = collect($noteIds)
            ->map(function ($value) {
                if (is_array($value)) {
                    $noteId        = (int) ($value['note_id'] ?? $value['id'] ?? 0);
                    $workReportId  = (int) ($value['work_report_id'] ?? 0);
                    $partialId     = (int) ($value['partial_id'] ?? 0);
                    $fiveNoteId   = (int) ($value['five_note_id'] ?? 0);
                    $bulkAnyStatus = (bool) ($value['bulk_any_status'] ?? false);

                    return ['note_id' => $noteId, 'work_report_id' => $workReportId ?: null, 'partial_id' => $partialId ?: null, 'five_note_id' => $fiveNoteId ?: null, 'bulk_any_status' => $bulkAnyStatus];
                }

                return ['note_id' => (int) $value, 'work_report_id' => null, 'partial_id' => null, 'five_note_id' => null, 'bulk_any_status' => false];
            });

        $contexts = $contexts
            ->filter(fn (array $context) => $context['note_id'] > 0)
            ->filter(fn (array $context) => empty($context['five_note_id']) || DB::table('five_notes')
                ->where('id', $context['five_note_id'])
                ->where('note_id', $context['note_id'])
                ->whereNull('work_report_id')
                ->where('is_completed', true)
                ->where('is_supervisioned', false)
                ->where('is_archived', false)
                ->exists())
            ->values();
        $noteIds  = $contexts->pluck('note_id')->unique()->values();

        if (!$noteIds->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->resetModalState();
        $loadedNotes = Note::with($this->modalNoteRelations())
            ->whereIn('id', $noteIds)
            ->get()
            ->keyBy('id');

        $this->targetWorkReportIdsByNote = $contexts
            ->filter(fn (array $context) => $context['work_report_id'])
            ->mapWithKeys(fn (array $context) => [
                $this->contextKey($context['note_id'], $context['work_report_id']) => $context['work_report_id'],
            ])
            ->all();
        $this->targetPartialIdsByContext = $contexts
            ->filter(fn (array $context) => $context['partial_id'])
            ->mapWithKeys(fn (array $context) => [
                $this->contextKey($context['note_id'], $context['work_report_id'], $context['partial_id']) => $context['partial_id'],
            ])
            ->all();
        $this->dispatchContextsByIndex = $contexts->values()->all();

        $this->notes = new EloquentCollection($contexts
            ->map(function (array $context) use ($loadedNotes) {
                $note = $loadedNotes->get($context['note_id']);

                if (!$note) {
                    return null;
                }

                $row = clone $note;
                $row->setAttribute('dispatch_work_report_id', $context['work_report_id']);
                $row->setAttribute('dispatch_partial_id', $context['partial_id']);
                $row->setAttribute('dispatch_five_note_id', $context['five_note_id']);
                $row->setAttribute('dispatch_bulk_any_status', (bool) ($context['bulk_any_status'] ?? false));
                $row->setAttribute('dispatch_context_key', $this->contextKey($context['note_id'], $context['work_report_id'], $context['partial_id']));

                if ($context["work_report_id"] && $row->relationLoaded("WorkForms")) {
                    $row->setRelation("WorkForm", $row->WorkForms->firstWhere("id", $context["work_report_id"]));
                }

                return $row;
            })
            ->filter()
            ->values()
            ->all());

        if ($this->contractMode) {
            $this->sourceProductionIdsByNote = [];

            foreach ($this->notes as $note) {
                $sourceProduction = $this->sourceProductionForContext($note);

                if ($sourceProduction) {
                    $this->sourceProductionIdsByNote[$this->contextKeyFor($note)] = (int) $sourceProduction->id;
                }
            }
        }

        $this->loadDispatchCompanies();
        $this->preselectContractDispatchCompany();
        $this->applyContractModeDefaults();
        $this->additionalData = [];
        $contextResolver      = app(DispatchContextResolver::class);
        $scopeAwareService    = in_array($contextResolver->serviceKey($this->service), ['supervision', 'payment', 'publication'], true);

        foreach ($this->notes as $index => $note) {
            $this->additionalData[$index] = SicodeRules::dispatchDdFor($note, $this->service->uuid) ?? '';
            $this->prepareFinalScopeSelection($note);

            $context                  = $contextResolver->for($note, $this->service);
            $this->requiresDd         = $this->requiresDd || (bool) ($context['requires_dd'] ?? false);
            $this->requiresFinalScope = $this->requiresFinalScope || (
                $scopeAwareService
                && count($this->finalScopeOptions[$this->contextKeyFor($note)] ?? []) > 0
            );
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'add_mass_notes',
        ]);
    }

    /**
     * Entrada direta quando o chamador ja resolveu o(s) informe(s) elegivel(is)
     * (ex.: lista de Fiscalizacao dirigida por WorkReportSupervisionCandidateQuery).
     * Reaproveita openForNotes montando os mesmos contextos note_id/work_report_id.
     */
    public function openForWorkReports(array $workReportIds): void
    {
        $workReportIds = collect($workReportIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if (!$workReportIds->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum informe foi selecionado para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $contexts = WorkReport::query()
            ->whereIn('id', $workReportIds)
            ->where('canceled', false)
            ->pluck('note_id', 'id')
            ->map(fn ($noteId, $workReportId) => [
                'note_id'        => (int) $noteId,
                'work_report_id' => (int) $workReportId,
            ])
            ->values()
            ->all();

        $this->openForNotes($contexts);
    }

    public function openForProductions(array $productionIds): void
    {
        $productionIds = collect($productionIds)->filter()->unique()->values();

        if (!$productionIds->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma atividade foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->resetModalState();

        $productions = Production::with(array_merge($this->modalProductionRelations(), ['WorkReportFlowProductions.WorkReport', 'partialInforms']))
            ->whereIn('id', $productionIds)
            ->where('service_id', $this->service->uuid)
            ->where('completed', false)
            ->where('confirmed', false)
            ->get();

        $this->notes = new EloquentCollection($productions
            ->map(function (Production $production) {
                $note = $production->Note;

                if (!$note) {
                    return null;
                }

                $link = $production->WorkReportFlowProductions
                    ->where('is_current', true)
                    ->whereIn('stage', ['fiscalization', 'payment', 'publication'])
                    ->sortByDesc('linked_at')
                    ->first();
                $partial = $production->partialInforms->sortByDesc('created_at')->first();

                $note->setAttribute('dispatch_work_report_id', $link?->work_report_id);
                $note->setAttribute('dispatch_partial_id', $partial?->id);
                $note->setAttribute('dispatch_context_key', $this->contextKey(
                    (int) $note->id,
                    $link?->work_report_id,
                    $partial?->id
                ));

                return $note;
            })
            ->filter()
            ->values()
            ->all());
        $this->dispatchContextsByIndex = $this->notes
            ->map(fn (Note $note) => [
                'note_id'        => (int) $note->id,
                'work_report_id' => (int) ($note->dispatch_work_report_id ?? 0) ?: null,
                'partial_id'     => (int) ($note->dispatch_partial_id ?? 0) ?: null,
            ])
            ->values()
            ->all();
        $this->sourceProductionIdsByNote = $productions
            ->filter(fn ($production) => $production->Note)
            ->mapWithKeys(fn ($production) => [$this->contextKeyFor($production->Note) => (int) $production->id])
            ->all();

        if (!$this->notes->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma atividade aberta foi encontrada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->loadDispatchCompanies();
        $this->preselectContractDispatchCompany();
        $this->applyContractModeDefaults();

        $contextResolver   = app(DispatchContextResolver::class);
        $scopeAwareService = in_array($contextResolver->serviceKey($this->service), ['supervision', 'payment', 'publication'], true);

        foreach ($this->notes as $index => $note) {
            $this->additionalData[$index] = SicodeRules::dispatchDdFor($note, $this->service->uuid) ?? '';
            $this->prepareFinalScopeSelection($note);

            $context                  = $contextResolver->for($note, $this->service);
            $this->requiresDd         = $this->requiresDd || (bool) ($context['requires_dd'] ?? false);
            $this->requiresFinalScope = $this->requiresFinalScope || (
                $scopeAwareService
                && count($this->finalScopeOptions[$this->contextKeyFor($note)] ?? []) > 0
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
                    ->orWhere('company_id', $this->company_s)
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
                'icon'     => 'warning',
                'title'    => 'Usuario com contrato deve atribuir a atividade, nao enviar para pilha.',
                'timer'    => 4000,
            ]);

            return;
        }

        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->type === '2' && !$this->user_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                'timer'    => 2500,
            ]);

            return;
        }

        $company    = Company::find($this->company_s);
        $targetUser = $this->type === '2' ? User::find($this->user_s) : null;
        $para       = $targetUser
            ? $targetUser->name . ' da ' . $company?->name
            : $company?->name;

        $this->dispatchBrowserEvent('alertar', [
            'target'        => 'dispatchs.shared.dispatch-modal',
            'title'         => 'Confirmar Despachar',
            'msg'           => "Você está prestes a Despachar {$this->notes->count()} {$this->dispatchItemLabelPlural} para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch_modal',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => "Nenhum {$this->dispatchItemLabel} foi despachado.",
        ]);
    }

    public function confirmedAtt(): void
    {
        if (!in_array((string) $this->type, ['1', '2'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione o tipo de despacho.',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->contractMode && $this->type !== '2') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Usuario com contrato deve atribuir a atividade, nao enviar para pilha.',
                'timer'    => 4000,
            ]);

            return;
        }

        try {
            $workflow          = app(DispatchWorkflowService::class);
            $scopeOptions      = app(WorkReportFinalScopeOptions::class);
            $contextResolver   = app(DispatchContextResolver::class);
            $serviceKey        = $contextResolver->serviceKey($this->service);
            $scopeAwareService = in_array($serviceKey, ['supervision', 'payment', 'publication'], true);
            $company           = Company::findOrFail($this->company_s);
            $targetUser        = (string) $this->type === '2' ? User::findOrFail($this->user_s) : null;
            $actor             = auth()->user();

            if ($scopeAwareService) {
                foreach ($this->notes as $note) {
                    $available = $scopeOptions->forNote($note, $serviceKey === 'publication');
                    $selected  = $this->selectedFinalScopesForNote($note);

                    if (count($available) > 1 && empty($selected)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'warning',
                            'title'    => "Selecione o escopo fiscalizado para a nota {$note->note}.",
                            'timer'    => 6000,
                        ]);

                        return;
                    }
                }
            }

            DB::transaction(function () use ($workflow, $company, $targetUser, $actor) {
                foreach ($this->notes as $key => $note) {
                    $dd                 = $this->additionalData[$key] ?? null;
                    $finalScopes        = $this->selectedFinalScopesForNote($note);
                    $sourceProductionId = $this->sourceProductionIdsByNote[$this->contextKeyFor($note)] ?? null;

                    if ($sourceProductionId) {
                        $production = Production::findOrFail($sourceProductionId);

                        if ($targetUser) {
                            $production = $workflow->assignProduction($production, $company, $targetUser, $actor, false, $finalScopes, $this->targetWorkReportIdsByNote[$this->contextKeyFor($note)] ?? null, $this->targetPartialIdsByContext[$this->contextKeyFor($note)] ?? null);
                        } else {
                            $production = $workflow->moveProductionToCompanyStack($production, $company, $actor, $finalScopes, $this->targetWorkReportIdsByNote[$this->contextKeyFor($note)] ?? null, $this->targetPartialIdsByContext[$this->contextKeyFor($note)] ?? null);
                        }
                    } elseif ($targetUser) {
                        $production = $workflow->dispatchToUser($note, $this->service, $company, $targetUser, $actor, $dd, $finalScopes, $this->targetWorkReportIdsByNote[$this->contextKeyFor($note)] ?? null, $this->targetPartialIdsByContext[$this->contextKeyFor($note)] ?? null);
                    } else {
                        $production = $workflow->dispatchToCompanyStack($note, $this->service, $company, $actor, $dd, $finalScopes, $this->targetWorkReportIdsByNote[$this->contextKeyFor($note)] ?? null, $this->targetPartialIdsByContext[$this->contextKeyFor($note)] ?? null);
                    }

                    if ($note->getAttribute('dispatch_bulk_any_status')) {
                        $this->recordBulkAnyStatusAudit($note, $production, $actor);
                    }
                }
            });
        } catch (DispatchException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => $e->getMessage(),
                'timer'    => 6000,
            ]);

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao despachar as Notas/OVs.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "{$this->dispatchItemLabelPlural} despachados com sucesso!",
            'timer'    => 2500,
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

    public function getDispatchItemLabelProperty(): string
    {
        return $this->currentServiceKey() === 'publication' ? 'informe' : 'nota/OV';
    }

    public function getDispatchItemLabelPluralProperty(): string
    {
        return $this->currentServiceKey() === 'publication' ? 'informe(s)' : 'nota(s)/OV(s)';
    }

    public function getFinalScopePromptProperty(): string
    {
        return $this->currentServiceKey() === 'publication'
            ? 'Marque o escopo exato desta publicacao.'
            : 'Marque o escopo exato desta fiscalizacao.';
    }

    private function sourceProductionForContext(Note $note): ?Production
    {
        $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());
        $query = Production::query()
            ->where("note_id", $note->id)
            ->where("service_id", $this->service->uuid)
            ->whereIn("company_id", $companyIds)
            ->whereNull("user_id")
            ->where("completed", false)
            ->where("confirmed", false);

        $workReportId = (int) ($note->dispatch_work_report_id ?? 0);
        $fiveNoteId = (int) ($note->dispatch_five_note_id ?? 0);
        $partialId = (int) ($note->dispatch_partial_id ?? 0);
        $stage = match ($this->currentServiceKey()) {
            "supervision" => \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION,
            "payment" => \App\Models\WorkReportFlowProduction::STAGE_PAYMENT,
            "publication" => \App\Models\WorkReportFlowProduction::STAGE_PUBLICATION,
            default => null,
        };

        if ($workReportId > 0 && $stage) {
            $query->whereHas("WorkReportFlowProductions", fn ($link) => $link
                ->where("work_report_id", $workReportId)
                ->where("stage", $stage)
                ->where("is_current", true));
        } elseif ($fiveNoteId > 0) {
            $direct = (clone $query)->whereHas("fiveNotes", fn ($five) => $five->whereKey($fiveNoteId))
                ->orderByDesc("dispatch_at")->orderByDesc("id")->first();
            if ($direct) {
                return $direct;
            }

            $legacyD5Count = DB::table("five_notes")->where("note_id", $note->id)->whereNull("work_report_id")
                ->where("is_completed", true)->where("is_supervisioned", false)->where("is_archived", false)->count();
            if ($legacyD5Count !== 1 || !$stage) {
                return null;
            }

            $fallback = (clone $query)->where("dfive", true)->whereDoesntHave("fiveNotes")
                ->whereHas("WorkReportFlowProductions", fn ($link) => $link->where("stage", $stage)->where("is_current", true));
            if ($fallback->count() !== 1) {
                return null;
            }

            return $fallback->orderByDesc("dispatch_at")->orderByDesc("id")->first();
        } elseif ($partialId > 0) {
            $query->whereHas("partialInforms", fn ($partial) => $partial->whereKey($partialId));
        }

        return $query->orderByDesc("dispatch_at")->orderByDesc("id")->first();
    }

    private function loadDispatchCompanies(): void
    {
        if (auth()->user()?->contract && $this->notes->count()) {
            $sourceIds = collect($this->sourceProductionIdsByNote)->values()->all();
            $companyIds = Production::query()
                ->whereIn("id", $sourceIds)
                ->distinct()
                ->pluck("company_id");

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

        $companyIds = Production::query()
            ->whereIn("id", collect($this->sourceProductionIdsByNote)->values()->all())
            ->pluck("company_id")
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($companyIds->count() === 1) {
            $this->company_s = $companyIds->first();
        }
    }

    private function resetModalState(): void
    {
        $this->notes                     = collect();
        $this->company_l                 = collect();
        $this->user_l                    = collect();
        $this->company_s                 = '';
        $this->user_s                    = '';
        $this->type                      = '1';
        $this->search_user               = '';
        $this->additionalData            = [];
        $this->finalScopeOptions         = [];
        $this->finalScopeSelections      = [];
        $this->contractMode              = (bool) auth()->user()?->contract;
        $this->requiresDd                = false;
        $this->requiresFinalScope        = false;
        $this->sourceProductionIdsByNote = [];
        $this->targetWorkReportIdsByNote = [];
        $this->targetPartialIdsByContext = [];
        $this->dispatchContextsByIndex   = [];
    }

    public function hydrateNotes($value): void
    {
        $rows = collect($this->notes)->map(function ($note, $index) {
            if (!$note instanceof Note) {
                return $note;
            }

            $context = $this->dispatchContextsByIndex[$index] ?? null;

            if (!$context) {
                return $note;
            }

            $row          = clone $note;
            $workReportId = (int) ($context["work_report_id"] ?? 0) ?: null;
            $partialId    = (int) ($context["partial_id"] ?? 0) ?: null;
            $row->setAttribute("dispatch_work_report_id", $workReportId);
            $row->setAttribute("dispatch_partial_id", $partialId);
            $row->setAttribute("dispatch_bulk_any_status", (bool) ($context["bulk_any_status"] ?? false));
            $row->setAttribute("dispatch_context_key", $this->contextKey((int) $context["note_id"], $workReportId, $partialId));

            if ($workReportId) {
                $workReport = WorkReport::query()
                    ->whereKey($workReportId)
                    ->where("note_id", $context["note_id"])
                    ->where("canceled", false)
                    ->with(["Orders", "FiveNote"])
                    ->first();
                $row->setRelation("WorkForm", $workReport);
            }

            return $row;
        })->values();

        $this->notes = new EloquentCollection($rows->all());
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
        $targetWorkReport = $this->targetWorkReportFor($note);
        $options          = $targetWorkReport
            ? $this->scopeOptionsForWorkReport($targetWorkReport, $this->currentServiceKey() === 'publication')
            : app(WorkReportFinalScopeOptions::class)->forNote($note, $this->currentServiceKey() === 'publication');

        $contextKey                              = $this->contextKeyFor($note);
        $this->finalScopeOptions[$contextKey]    = $options;
        $this->finalScopeSelections[$contextKey] = [];

        if (!empty($options) && ($targetWorkReport || count($options) === 1)) {
            $this->finalScopeSelections[$contextKey][$options[0]['scope']] = true;
        }
    }

    public function scopeIsLocked(Note $note): bool
    {
        return isset($this->targetWorkReportIdsByNote[$this->contextKeyFor($note)]);
    }

    private function targetWorkReportFor(Note $note): ?WorkReport
    {
        $workReportId = (int) ($this->targetWorkReportIdsByNote[$this->contextKeyFor($note)] ?? 0);

        if ($workReportId <= 0) {
            return null;
        }

        if ($note->relationLoaded('WorkForms')) {
            return $note->WorkForms->firstWhere('id', $workReportId);
        }

        return WorkReport::query()
            ->where('note_id', $note->id)
            ->where('id', $workReportId)
            ->where('canceled', false)
            ->with('Orders:id,ordem')
            ->first();
    }

    private function scopeOptionsForWorkReport(WorkReport $workReport, bool $publicationOnly): array
    {
        return collect($workReport->finalScopePayloads())
            ->filter(function (array $payload) use ($publicationOnly) {
                return !$publicationOnly
                    || app(\App\Services\WorkReports\WorkReportFinalScopeResolver::class)
                        ->publicationRequired($payload['scope']);
            })
            ->map(fn (array $payload) => [
                'scope'                => $payload['scope'],
                'label'                => $workReport->finalScopeLabel($payload['scope']),
                'publication_required' => app(\App\Services\WorkReports\WorkReportFinalScopeResolver::class)
                    ->publicationRequired($payload['scope']),
            ])
            ->values()
            ->all();
    }

    private function selectedFinalScopesForNote(Note $note): array
    {
        $selected = collect($this->finalScopeSelections[$this->contextKeyFor($note)] ?? [])
            ->filter(fn ($enabled) => (bool) $enabled)
            ->keys()
            ->all();

        return app(WorkReportFinalScopeOptions::class)
            ->validScopesForNote($note, $selected, $this->currentServiceKey() === 'publication');
    }

    private function currentServiceKey(): string
    {
        return app(DispatchContextResolver::class)->serviceKey($this->service);
    }

    private function contextKey(int $noteId, ?int $workReportId, ?int $partialId = null): string
    {
        return $noteId . ':' . (int) ($workReportId ?: 0) . ':p' . (int) ($partialId ?: 0);
    }

    private function contextKeyFor(Note $note): string
    {
        return (string) ($note->getAttribute('dispatch_context_key')
            ?: $this->contextKey((int) $note->id, (int) ($note->dispatch_work_report_id ?? 0), (int) ($note->dispatch_partial_id ?? 0)));
    }

    /**
     * A busca "em qualquer situacao / todos os servicos" ignora o criterio padrao
     * de elegibilidade da pilha (Fluxo Normal, D5 ou Partial). Quando alguem despacha
     * uma nota que so apareceu por causa dessa busca ampliada, fica registrado no
     * historico da propria nota para permitir auditoria posterior.
     */
    private function recordBulkAnyStatusAudit(Note $note, Production $production, User $actor): void
    {
        Notetimeline::create([
            'note_id'       => $production->id,
            'service_id'    => $production->service_id,
            'user_id'       => $actor->id,
            'info'          => "Usuario {$actor->name} despachou esta Nota/OV com a busca \"em qualquer situacao/todos os servicos\" ativa, fora do criterio padrao de elegibilidade da pilha.",
            'status'        => $production->status,
            'production_id' => $production->id,
            'category'      => 'bulk_any_status_dispatch',
        ]);
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
            'WorkForm.Note:id,type_note',
            'WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem']),
            'WorkForms'       => fn ($q) => $q->select([
                'id',
                'note_id',
                'company_id',
                'informed_at',
                'created_at',
                'rejected',
                'selected_final_scopes',
            ])->where('canceled', false),
            'WorkForms.Note:id,type_note',
            'WorkForms.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem']),
            'FiveNote:id,note_id,work_report_id,is_supervisioned,is_completed,is_archived,completed_at',
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
