<?php

namespace App\Http\Livewire\Services\Payment\Forms;

use App\Models\{Analise, Company, FiveNote, Notetimeline, Production};
use App\Services\D5\D5WorkflowService;
use App\Services\Payment\{MeasurementProductionContextService, PaymentD5CreationService};
use App\Services\WorkReports\WorkReportScopedProductionSplitter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Jobform extends Component
{
    public ?Production $production = null;

    public ?Analise $analise = null;

    public $companies;

    public $five;

    public array $closeFinalScopeSelections = [];

    /** Origem das evidências anexadas à D5 criada por aqui. */
    public string $origin = 'PAGAMENTO';

    public bool $hasEvidence = false;

    /** Criar D5 no encerramento: o processo é guiado (fiscal → dados e anexos) e nada é gravado antes de ENCERRAR. */
    public bool $creatingD5 = false;

    /** Etapa do assistente: fiscal (quem encerrou a atividade anterior) → form (dados da D5 e anexos). */
    public string $d5Stage = 'fiscal';

    /** '' = ainda não escolheu; 'none' = sem fiscal; senão o id da produção de Fiscalização escolhida. */
    public string $d5Fiscal = '';

    public array $newD5 = [];

    protected $listeners = [
        'refresh' => '$refresh',
        'showProduction',
        'confirmFinish'         => 'save',
        'confirmProductionIsD5' => 'markProductionAsD5',
        'hasEvidence',
        'evidenceSaved',
        'evidenceSaveFailed',
    ];

    protected $rules = [
        'analise.postes'        => 'required|numeric',
        'analise.info'          => 'nullable|string',
        'analise.conclusion'    => 'required|min:1',
        'five.note_d5'          => 'required|numeric',
        'five.reason'           => 'nullable|string',
        'five.description'      => 'nullable|string',
        'five.loc_install'      => 'nullable|string',
        'five.codify'           => 'nullable|string',
        'five.sintoms'          => 'nullable|string',
        'five.pep'              => 'nullable|string',
        'five.conjunto'         => 'nullable|string',
        'five.dispatch_at'      => 'nullable|date',
        'five.supervisioned_at' => 'nullable|date',
        'five.payed_at'         => 'nullable|date',
        'five.company_id'       => 'required|exists:companies,id',

    ];

    public function messages()
    {
        return [
            'analise.postes.required'     => 'O campo [Qtd de Ativos] é obrigatório.',
            'analise.postes.numeric'      => 'O campo [Qtd de Ativos] só aceita números.',
            'analise.conclusion.required' => 'O campo [Resultado] é Obrigatório.',
            'five.note_d5.required'       => 'O campo [Número da D5] é obrigatório.',
            'five.note_d5.numeric'        => 'O campo [Número da D5] dev ser apenas números.',
            'five.note_d5.unique'         => 'O D5 informado já está em uso.',
            'five.company_id.required'    => 'O campo [Empresa] é obrigatório.',
        ];
    }

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }

    public function hasEvidence($value): void
    {
        $this->hasEvidence = (bool) $value;
    }

    public function getCanCreateD5Property(): bool
    {
        return $this->production && !$this->five && app(PaymentD5CreationService::class)->canCreate($this->production);
    }

    public function getD5FiscalOptionsProperty(): array
    {
        return $this->production ? app(PaymentD5CreationService::class)->fiscalOptions($this->production)->all() : [];
    }

    public function startCreateD5(): void
    {
        if (!$this->canCreateD5) {
            return;
        }

        $this->creatingD5 = true;
        $this->d5Stage    = 'fiscal';
        $this->d5Fiscal   = '';
        $this->newD5      = app(PaymentD5CreationService::class)->defaults($this->production);
    }

    public function confirmD5Fiscal(): void
    {
        if ($this->d5Fiscal === '') {
            $this->addError('d5Fiscal', 'Selecione o fiscal que encerrou a atividade ou "Nenhum".');

            return;
        }

        try {
            app(PaymentD5CreationService::class)->resolveFiscal($this->production, $this->d5Fiscal);
        } catch (\DomainException $e) {
            $this->addError('d5Fiscal', $e->getMessage());

            return;
        }

        $this->resetErrorBag('d5Fiscal');
        $this->d5Stage = 'form';
    }

    public function backToD5Fiscal(): void
    {
        $this->d5Stage = 'fiscal';
    }

    public function cancelCreateD5(): void
    {
        $this->resetCreateD5();
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');
    }

    private function resetCreateD5(): void
    {
        $this->creatingD5  = false;
        $this->d5Stage     = 'fiscal';
        $this->d5Fiscal    = '';
        $this->newD5       = [];
        $this->hasEvidence = false;
        $this->resetErrorBag();
    }

    private function newD5Complete(): bool
    {
        return $this->creatingD5
            && $this->d5Stage === 'form'
            && collect(['note_d5', 'company_id', 'reason', 'codify', 'loc_install'])
                ->every(fn (string $field) => $this->filledValue($this->newD5[$field] ?? null));
    }

    private function validateNewD5(): void
    {
        $this->validate([
            'newD5.note_d5'     => 'required|numeric|unique:five_notes,note_d5',
            'newD5.company_id'  => 'required|exists:companies,id',
            'newD5.reason'      => 'required|string|max:255',
            'newD5.codify'      => 'required|string|max:255',
            'newD5.loc_install' => 'required|string|max:255',
            'newD5.conjunto'    => 'nullable|max:255',
            'newD5.pep'         => 'nullable|max:255',
            'newD5.sintoms'     => 'nullable|max:255',
            'newD5.description' => 'nullable|string',
        ], [
            'newD5.note_d5.required'     => 'O campo [Número da D5] é obrigatório.',
            'newD5.note_d5.numeric'      => 'O campo [Número da D5] deve ser apenas números.',
            'newD5.note_d5.unique'       => 'O D5 informado já está em uso.',
            'newD5.company_id.required'  => 'O campo [Empresa] é obrigatório.',
            'newD5.reason.required'      => 'O campo [Motivo] da D5 é obrigatório.',
            'newD5.codify.required'      => 'O campo [Código] da D5 é obrigatório.',
            'newD5.loc_install.required' => 'O campo [Local de Instalação] da D5 é obrigatório.',
        ]);
    }

    public function getCloseStepSummaryProperty(): array
    {
        $steps   = $this->closeSteps;
        $current = collect($steps)->firstWhere('state', 'current')
            ?? collect($steps)->firstWhere('state', 'warning')
            ?? collect($steps)->firstWhere('state', 'todo')
            ?? collect($steps)->last();

        return [
            'icon'      => $current['icon'] ?? 'ri-information-line',
            'iconClass' => match ($current['state'] ?? 'todo') {
                'done'    => 'is-ready',
                'warning' => 'is-warning',
                default   => ($this->canCloseFinish ? 'is-ready' : 'is-warning'),
            },
            'label'   => $current['label'] ?? 'Pronto para encerrar',
            'message' => $current['message'] ?? 'Todas as etapas obrigatórias foram preenchidas.',
        ];
    }

    public function getCanCloseFinishProperty(): bool
    {
        return collect($this->closeSteps)
            ->where('required', true)
            ->every(fn (array $step) => $step['state'] === 'done');
    }

    public function getCloseStepsProperty(): array
    {
        $needsD5Data       = (bool) ($this->five && !$this->five->is_supervisioned);
        $hasConclusion     = $this->filledValue($this->analise?->conclusion);
        $hasScopeSelection = $this->hasValidCloseFinalScopeSelection();

        $steps = [];

        if ($this->creatingD5) {
            $steps[] = $this->closeStep('Fiscal da D5', $this->d5Fiscal !== '' && $this->d5Stage === 'form', 'Informe quem encerrou a atividade anterior (ou "Nenhum") para criar a D5.', 'ri-user-search-line');
            $steps[] = $this->closeStep('Dados da D5', $this->newD5Complete(), 'Preencha número, empresa, motivo, código e local de instalação da D5.', 'ri-file-text-line');
        }

        if ($needsD5Data) {
            $steps[] = $this->closeStep('Número D5', $this->filledValue($this->five?->note_d5), 'Informe o número da D5.', 'ri-file-text-line');
            $steps[] = $this->closeStep('Empresa D5', $this->filledValue($this->five?->company_id), 'Selecione a empresa responsável pela D5.', 'ri-building-line');
        }

        if ($this->hasMultipleCloseFinalScopes()) {
            $steps[] = $this->closeStep('Escopo medido', $hasScopeSelection, 'Selecione o escopo que será encerrado nesta medição.', 'ri-focus-3-line');
        }

        $steps[] = $this->closeStep('Resultado', $hasConclusion, 'Selecione o resultado da medição.', 'ri-checkbox-circle-line');

        $steps[] = [
            'label'   => 'Observações',
            'message' => $this->filledValue($this->analise?->info)
                ? 'Observação registrada.'
                : 'Observação opcional para contextualizar o encerramento.',
            'icon'     => 'ri-message-3-line',
            'required' => false,
            'state'    => $this->filledValue($this->analise?->info) ? 'done' : 'warning',
        ];

        if (collect($steps)->where('required', true)->every(fn (array $step) => $step['state'] === 'done')) {
            $steps[] = [
                'label'    => 'Encerrar',
                'message'  => 'Etapas obrigatórias concluídas. Encerramento liberado.',
                'icon'     => 'ri-checkbox-circle-line',
                'required' => true,
                'state'    => 'done',
            ];
        }

        return $this->markCurrentCloseStep($steps);
    }

    private function closeStep(string $label, bool $done, string $message, string $icon): array
    {
        return [
            'label'    => $label,
            'message'  => $done ? "{$label} preenchido." : $message,
            'icon'     => $icon,
            'required' => true,
            'state'    => $done ? 'done' : 'todo',
        ];
    }

    private function markCurrentCloseStep(array $steps): array
    {
        foreach ($steps as &$step) {
            if (($step['required'] ?? false) && $step['state'] === 'todo') {
                $step['state'] = 'current';

                break;
            }
        }

        return $steps;
    }

    private function filledValue($value): bool
    {
        return !is_null($value) && trim((string) $value) !== '';
    }

    public function showProduction(Production $production)
    {
        $this->five       = null;
        $this->production = $production;
        $this->resetCreateD5();

        if ($this->production) {
            $this->syncCloseFinalScopeSelections();

            if ($fiveNote = $this->fiveNoteForCurrentProduction()) {
                $this->five = $fiveNote;
            }

            // Garantir a existência de Analise
            $this->analise = $this->production->Analise()->firstOrCreate(
                [], // Condições de busca (vazias pois já está relacionado à production)
                [   // Valores padrão caso precise criar
                    'postes'     => 0,
                    'info'       => null,
                    'conclusion' => null,
                ]
            );

            $this->status();

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'formProductionModal',
            ]);

            $this->promptPendingD5();
        }
    }

    /**
     * D5 aberta no informe desta produção, quando a produção ainda não foi marcada como D5
     * (ex.: o status mudou no SAP e a atividade chegou à Medição sem a solicitação de D5).
     */
    private function pendingD5ForProduction(): ?FiveNote
    {
        if (!$this->production || $this->production->partial || $this->five) {
            return null;
        }

        $workReportId = $this->currentWorkReportIdForPayment();

        if (!$workReportId) {
            return null;
        }

        $fiveNote = FiveNote::query()
            ->where('note_id', $this->production->note_id)
            ->where('work_report_id', $workReportId)
            ->where('is_archived', false)
            ->latest('id')
            ->first();

        if (!$fiveNote) {
            return null;
        }

        // D5 que já tem outra atividade em aberto não é puxada para esta.
        $hasOtherOpenProduction = $fiveNote->productions()
            ->where('productions.id', '!=', $this->production->id)
            ->where('productions.dfive', true)
            ->where('productions.completed', false)
            ->exists();

        return $hasOtherOpenProduction ? null : $fiveNote;
    }

    private function promptPendingD5(): void
    {
        if (!$this->pendingD5ForProduction()) {
            return;
        }

        $this->dispatchBrowserEvent('swalcustom', [
            'title'         => 'Existe uma D5 neste informe',
            'msg'           => 'Este informe possui uma D5 associada. A atividade que você está encerrando é sobre essa D5?',
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, é sobre a D5',
            'btnCanceltxt'  => 'Não',
            'action'        => 'confirmProductionIsD5',
            'chave'         => $this->production->id,
            'cancel_titulo' => 'Mantido',
            'cancel_msg'    => 'A atividade segue como medição normal.',
        ]);
    }

    public function markProductionAsD5($productionId = null): void
    {
        if (!$this->production || (int) $productionId !== (int) $this->production->id) {
            return;
        }

        $fiveNote = $this->pendingD5ForProduction();

        if (!$fiveNote) {
            return;
        }

        DB::transaction(function () use ($fiveNote) {
            $this->production->update(['dfive' => true]);
            $fiveNote->productions()->syncWithoutDetaching([$this->production->id]);

            // Ciclo 1 sem D5 passa a apontar a D5 do informe; o ciclo não muda.
            $this->production->measurementContext()
                ->where('cycle', \App\Models\MeasurementProductionContext::CYCLE_ONE)
                ->whereNull('five_note_id')
                ->where('work_report_id', $fiveNote->work_report_id)
                ->update(['five_note_id' => $fiveNote->id]);

            Notetimeline::create([
                'note_id'    => $this->production->note_id,
                'service_id' => $this->production->service_id,
                'user_id'    => auth()->id(),
                'info'       => 'Usuário ' . auth()->user()->name . ' marcou a atividade como D5 ao encerrar (D5 do informe).',
                'status'     => $this->production->status,
            ]);
        });

        $this->showProduction($this->production->fresh());
    }

    private function currentWorkReportIdForPayment(): ?int
    {
        foreach ([\App\Models\WorkReportFlowProduction::STAGE_PAYMENT, \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION] as $stage) {
            $workReportId = $this->production?->WorkReportFlowProductions()
                ->where('stage', $stage)
                ->where('is_current', true)
                ->latest('id')
                ->value('work_report_id');

            if ($workReportId) {
                return (int) $workReportId;
            }
        }

        return null;
    }

    /**
     * Produção `dfive` com D5 associada resolve a D5; caso contrário resolve o informe normalmente.
     */
    private function fiveNoteForCurrentProduction(): ?FiveNote
    {
        $contextFive = $this->production?->measurementContext?->fiveNote;

        if ($contextFive) {
            return $contextFive;
        }

        return $this->production
            ? app(\App\Services\D5\ProductionFiveNoteResolver::class)->resolve($this->production)
            : null;
    }

    public function status()
    {
        if ($this->production->status != 4) {

            if (!(session_status() == PHP_SESSION_ACTIVE)) {
                if (!session()->isStarted()) {
                    session()->start();
                }
            }

            if (isset($_SESSION['waitingForm'])) {
                $_SESSION['waitingForm'] = false;
                unset($_SESSION['waitingForm']);
            }

            $this->production->update(['status' => 3]);
            $this->production->save();

        } else {
            $hist = Notetimeline::where('note_id', $this->production->note_id)->Where('service_id', $this->production->service_id)->where('status', 4)->orderBy('created_at', 'DESC')->first();

            if ($hist) {
                $time = (Carbon::parse($hist->created_at))->diffInSeconds(Carbon::now());
                $hist->update(['return_stop' => date('Y-m-d H:i:s')]);
            } else {
                $time = 0;
            }

            $update = $this->production->update([
                'status'  => 3,
                'stopped' => $this->production->stopped + $time,
            ]);

            if ($update && $this->production->status !== 3) {
                // Registra Movimento Nota
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'       => $this->note->id,
                    'service_id'    => $this->production->service_id,
                    'user_id'       => Auth()->User()->id,
                    'info'          => "Usuário {$user} iniciou a Nota/OV.",
                    'status'        => 3,
                    'production_id' => $this->production->id,
                ]);
            }
        }

        $this->emitUp('refresh_list');
    }

    public function saveForm($end = false): bool
    {

        try {
            if ($end) {
                if ($this->creatingD5 && !$this->five) {
                    $this->validateNewD5();
                }

                if ($this->five) {
                    $this->validate();
                } else {
                    $this->validate([
                        'analise.postes'     => 'required|numeric',
                        'analise.info'       => 'nullable|string',
                        'analise.conclusion' => 'required|min:1',
                    ]);
                }
            }

            $this->production->Analise()->updateOrCreate([], $this->analise->toArray());

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'SALVO COM SUCESSO',
            ]);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            $html   = '<ul>';

            foreach ($errors as $error) {
                $html .= '<li>' . $error . '</li>';
            }

            $html .= '</ul>';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">' . $html . '</div></div>',
            ]);

            return false;
        }
    }

    public function waitingForm()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) {
                session()->start();
            }
        }

        $_SESSION['waitingForm'] = true;

        $this->saveForm();
        $this->production->update(['status' => 27]);
        $this->production->save();
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function to_finish()
    {
        if (!$this->canCloseFinish) {
            $summary = $this->closeStepSummary;

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Encerramento incompleto',
                'html'     => '<div class="card"><div class="card-body text-start">'
                    . e($summary['message'])
                    . '</div></div>',
            ]);

            return;
        }

        if (!$this->hasValidCloseFinalScopeSelection()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Escopo do encerramento obrigatório',
                'html'     => '<div class="card"><div class="card-body text-start">Selecione ao menos um escopo para encerrar nesta medição.</div></div>',
            ]);

            return;
        }

        $this->validate(['analise.conclusion' => 'required|min:1']);

        if ($this->creatingD5 && !$this->five) {
            try {
                $this->validateNewD5();
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Sem isto o botão parecia não fazer nada: o erro podia ser de um campo fora da tela.
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Dados da D5 incompletos',
                    'html'     => '<div class="card"><div class="card-body text-start"><ul><li>' . implode('</li><li>', array_map('e', $e->validator->errors()->all())) . '</li></ul></div></div>',
                ]);

                return;
            }
        }

        if ($this->production->partial) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO PARCIAL',
                'msg'   => "Você está prestes encerrar a medição parcial de <strong>{$this->production->Note->note}</strong>.
                <div class='card'>
                    <div class='card-body'>
                        Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                        Uma vez encerrado, essa operação nao poderá ser desfeita.
                        <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                    </div>
                </div>
            ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO',
                'msg'   => "Você está prestes encerrar a medição de <strong>{$this->production->Note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                            Uma vez encerrado, essa operação nao poderá ser desfeita.
                            <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                        </div>
                    </div>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        }
    }

    public function save()
    {
        if (!$this->saveForm(true)) {
            return;
        }

        DB::beginTransaction();

        try {
            app(WorkReportScopedProductionSplitter::class)->splitRemainingScopes(
                $this->production,
                \App\Models\WorkReportFlowProduction::STAGE_PAYMENT,
                $this->selectedCloseFinalScopes()
            );

            // D5 criada aqui: só agora o processo é cadastrado. Depois segue o mesmo caminho do ciclo 1 com D5 (despacho à empreiteira).
            $createdFive = null;

            if ($this->creatingD5 && !$this->five) {
                $d5Service = app(PaymentD5CreationService::class);
                $fiscal    = $d5Service->resolveFiscal($this->production, $this->d5Fiscal);

                $createdFive = $d5Service->create($this->production, $this->newD5, $fiscal, auth()->user());
                $this->five  = $createdFive;

                $this->analise->info = trim(($this->analise->info ? $this->analise->info . "\n\n" : '') . $d5Service->auditLine(auth()->user(), $fiscal, $createdFive));
                $this->analise->save();
            }

            $chk = $this->production->update([
                'status'       => 5,
                'completed_at' => date('Y-m-d H:i:s'),
                'postes_u'     => $this->analise->postes ? $this->analise->postes : 0,
                'completed'    => true,
                'confirmed'    => false,
                'priority'     => false,
            ]);

            if ($this->production->partial) {
                // Nunca escolher o último parcial da Nota: a Production traz a
                // identidade do parcial que ela está efetivamente encerrando.
                $partial = $this->production->measurementContext?->partial;

                if ($partial) {

                    if ($partial->allow && !$partial->deny && $partial->supervision && !$partial->payment) {
                        $partial->update([
                            'payment'    => true,
                            'complete'   => true,
                            'payment_at' => date('Y-m-d H:i:s'),
                            'payment_id' => Auth()->User()->id,
                        ]);
                    }
                }

                app(MeasurementProductionContextService::class)->releasePartial($this->production);
            }

            if ($this->five) {
                $fromStage = app(D5WorkflowService::class)->currentStage($this->five);

                if (!$this->five->is_supervisioned) {
                    $this->validate([
                        'five.note_d5'    => 'required|numeric',
                        'five.company_id' => 'required|exists:companies,id',
                    ]);
                    $this->five->dispatch_at     = now();
                    $this->five->visible_partner = true;
                    $this->five->is_payed        = true;
                    $this->five->payed_at        = now();

                } else {
                    $this->five->is_archived = true;
                }

                $this->five->save();

                if (!$this->five->is_supervisioned) {
                    app(D5WorkflowService::class)->onReleasedToPartner(
                        $this->five,
                        $fromStage,
                        auth()->id(),
                        $this->production
                    );
                } else {
                    app(D5WorkflowService::class)->onArchived(
                        $this->five,
                        $fromStage,
                        auth()->id(),
                        $this->production
                    );
                }

                $this->five->Productions()->syncWithoutDetaching([$this->production->id]);
            }

            if ($chk) {
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'    => $this->production->note_id,
                    'service_id' => $this->production->service_id,
                    'user_id'    => Auth()->User()->id,
                    'info'       => "Usuário {$user} encerrou a Nota/OV.",
                    'status'     => 5,
                ]);

                DB::commit();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Encerrado com Sucesso',
                ]);

                if ($createdFive && $this->hasEvidence) {
                    // Os anexos precisam da D5 já cadastrada; o modal fecha quando o upload avisar `evidenceSaved`.
                    $this->emitTo('files.evidence.upload-evidence', 'saveEvidences', $createdFive->id);
                } else {
                    $this->closeAll();
                }
            }

        } catch (\Throwable $th) {

            DB::rollback();

            if ($this->creatingD5 && !empty($createdFive)) {
                $this->five = null;
                $this->analise->refresh();
            }

            $this->addError('general', 'Não conseguimos encerrar a atividade. ' . $th->getMessage());

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO FINALIZADO',
                'html'     => 'Não Conseguimos encerrar a atividade, tente novamente.<br>' . $th->getMessage(),
            ]);

            $this->emitSelf('refresh');

            return;
        }
    }

    public function evidenceSaved(): void
    {
        $this->closeAll();
    }

    public function evidenceSaveFailed(): void
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'warning',
            'title'    => 'D5 criada, anexos não salvos',
            'html'     => 'A atividade foi encerrada e a D5 foi criada, mas os arquivos não puderam ser salvos. Anexe-os novamente pela D5.',
        ]);

        $this->closeAll();
    }

    public function closeAll()
    {
        $this->resetCreateD5();
        $this->production                = null;
        $this->analise                   = null;
        $this->five                      = null;
        $this->closeFinalScopeSelections = [];
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.services.payment.forms.jobform');
    }

    public function closeFinalScopeOptions(): array
    {
        if (!$this->production) {
            return [];
        }

        return app(WorkReportScopedProductionSplitter::class)
            ->currentScopeOptions($this->production, \App\Models\WorkReportFlowProduction::STAGE_PAYMENT);
    }

    public function hasMultipleCloseFinalScopes(): bool
    {
        return count($this->closeFinalScopeOptions()) > 1;
    }

    protected function selectedCloseFinalScopes(): array
    {
        $options = collect($this->closeFinalScopeOptions())->pluck('scope')->all();

        if (count($options) <= 1) {
            return $options;
        }

        return collect($this->closeFinalScopeSelections)
            ->filter(fn ($enabled) => (bool) $enabled)
            ->keys()
            ->intersect($options)
            ->values()
            ->all();
    }

    protected function hasValidCloseFinalScopeSelection(): bool
    {
        return !$this->hasMultipleCloseFinalScopes() || !empty($this->selectedCloseFinalScopes());
    }

    protected function syncCloseFinalScopeSelections(): void
    {
        $this->closeFinalScopeSelections = collect($this->closeFinalScopeOptions())
            ->mapWithKeys(fn (array $option) => [$option['scope'] => true])
            ->all();
    }
}
