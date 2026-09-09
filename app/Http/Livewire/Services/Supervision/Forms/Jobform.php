<?php

namespace App\Http\Livewire\Services\Supervision\Forms;

use App\Models\{Analise, D5Return, EvidenceFile, File, FiveNote, Notetimeline, Production};
use App\Services\D5\D5WorkflowService;
use App\Services\Files\EvidenceFileService;
use App\Services\Files\FileStorageService;
use App\Services\WorkReports\WorkReportScopedProductionSplitter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Jobform extends Component
{
    public ?Production $production = null;

    public ?Analise $analise = null;

    public $lastReturnwork = null;

    public $five;

    public $origin = 'FISCALIZACAO';

    public $hasFile = false;

    public $hasEvidence = false;

    public array $closeNoteDetails = [];

    public array $productionFileGroups = [];

    public int $productionFilesCount = 0;

    public bool $hasExistingProductionFiles = false;

    public array $closeFinalScopeSelections = [];

    public $d5 = 2;

    public $supervisionByPartnerPhotos = '';

    public $return = [
        // 'note' => '',
        'reason'      => '',
        'description' => '',
        'loc_install' => '',
        'codify'      => '',
        'sintoms'     => '',
    ];

    protected $listeners = [
        'showProduction',
        'confirmFinish' => 'save',
        'hasFile',
        'hasEvidence',
        'evidenceSaved',
        'savedFiles',
    ];

    protected $rules = [
        'analise.postes'     => 'required|numeric|min:0',
        'analise.info'       => 'nullable|string',
        'analise.conclusion' => 'required',
        'return.loc_install' => 'nullable|string',
    ];

    public function messages()
    {
        return [
            'analise.postes.required'     => 'O campo [Qtd de Ativos] é obrigatório.',
            'analise.postes.numeric'      => 'O campo [Qtd de Ativos] só aceita números.',
            'analise.conclusion.required' => 'O campo [Resultado] é Obrigatório.',

        ];
    }

    public function hasFile($value)
    {
        $this->hasFile = $value;
    }

    public function hasEvidence($value)
    {
        $this->hasEvidence = $value;
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
        $isPartial             = (bool) ($this->production?->partial);
        $d5Selected            = $isPartial || in_array((string) $this->d5, ['0', '1'], true) || (bool) ($this->production?->dfive);
        $needsD5               = !$isPartial && (string) $this->d5 === '1' && !(bool) ($this->production?->dfive);
        $hasConclusion         = $this->filledValue($this->analise?->conclusion);
        $hasPartnerPhotoAnswer = in_array((string) $this->supervisionByPartnerPhotos, ['0', '1'], true);
        $hasPostes             = $this->filledValue($this->analise?->postes);
        $postesIsZero          = $hasPostes && (float) $this->analise->postes === 0.0;
        $hasExistingFiles      = $this->hasExistingProductionFiles;

        $steps = [];

        if ($isPartial) {
            $steps[] = [
                'label'    => 'Parcial',
                'message'  => 'Encerramento parcial: D5 não se aplica e rejeição de obra fica disponível.',
                'icon'     => 'ri-scissors-cut-line',
                'required' => false,
                'state'    => 'done',
            ];
        } else {
            $steps[] = $this->closeStep(
                'Necessidade D5',
                $d5Selected,
                'Informe se existe necessidade de D5.',
                'ri-question-line'
            );
        }

        if ($needsD5) {
            $steps[] = $this->closeStep('Motivo D5', $this->filledValue($this->return['reason'] ?? null), 'Selecione o motivo da D5.', 'ri-error-warning-line');
            $steps[] = $this->closeStep('Código D5', $this->filledValue($this->return['codify'] ?? null), 'Selecione o código da D5.', 'ri-hashtag');
            $steps[] = $this->closeStep('Local de Instalação', $this->filledValue($this->return['loc_install'] ?? null), 'Informe o local de instalação.', 'ri-map-pin-line');
            $steps[] = $this->closeStep('Observação D5', $this->filledValue($this->return['description'] ?? null), 'Descreva a observação da D5.', 'ri-message-3-line');
        }

        $steps[] = $this->closeStep('Conclusão', $hasConclusion, 'Selecione a conclusão da fiscalização.', 'ri-checkbox-circle-line');
        $steps[] = $this->closeStep('Fotos da Parceira', $hasPartnerPhotoAnswer, 'Informe se a fiscalização ocorreu por fotos da parceira.', 'ri-image-line');

        $steps[] = [
            'label'   => 'Arquivos',
            'message' => $hasExistingFiles || $this->hasFile || $this->hasEvidence
                ? 'Revise se todos os arquivos obrigatórios foram anexados.'
                : 'Lembrete: anexe os arquivos obrigatórios antes de encerrar.',
            'icon'     => 'ri-attachment-2',
            'required' => false,
            'state'    => $hasExistingFiles || $this->hasFile || $this->hasEvidence ? 'done' : 'warning',
        ];

        if ($postesIsZero) {
            $steps[] = [
                'label'    => 'Postes zerado',
                'message'  => 'Lembrete: a quantidade de postes está zerada.',
                'icon'     => 'ri-alert-line',
                'required' => false,
                'state'    => 'warning',
            ];
        }

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

    public function downloadFile(EvidenceFile $file)
    {
        $service = app(EvidenceFileService::class);

        if ($service->exists($file)) {
            return $service->download($file);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO INEXISTENTE!',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function downloadProductionFile(File $file)
    {
        if ($file->isTacitAdsRestricted() && !(auth()->user()?->superadm ?? false)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'DOWNLOAD BLOQUEADO',
                'html'     => 'Arquivo de ADS tácita. Download permitido apenas para Super Admin.',
                'timer'    => 5000,
            ]);

            return;
        }

        $storage = app(FileStorageService::class);

        if ($storage->exists($file)) {
            return $storage->download($file, $file->stored_name);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO INEXISTENTE!',
            'timer'    => 5000,
        ]);
    }

    public function deleteFile(EvidenceFile $file)
    {
        if ($file) {
            $file->delete();
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Arquivo removido com sucesso!',
            ]);
            $this->emit('refreshComponent');
        }
    }

    public function updatedD5($value)
    {

        $this->d5 = $value;

        if (!$value) {
            $this->return = [
                // 'note' => '',
                'reason'      => '',
                'description' => '',
                'loc_install' => '',
                'codify'      => '',
                'sintoms'     => '',
            ];
        } else {
            if ($this->production->dfive) {
                $fiveNote                    = $this->production->Note->FiveNote;
                $this->return['reason']      = $fiveNote?->reason;
                $this->return['description'] = $fiveNote?->description;
                $this->return['loc_install'] = $fiveNote?->loc_install;
                $this->return['codify']      = $fiveNote?->codify;
                $this->return['sintoms']     = $fiveNote?->sintoms;
            } else {
                $this->return['reason']      = '';
                $this->return['description'] = '';
                $this->return['loc_install'] = $this->production->Note->WorkForm?->Orders?->sortBy('ordem')->first()?->locInstalacao;
                $this->return['codify']      = '';
                $this->return['sintoms']     = '';
            }
        }
    }

    public function showProduction(Production $production)
    {

        $this->five           = null;
        $this->lastReturnwork = null;
        $this->production     = $production->load(
            'Service',
            'Analise',
            'Files.Service',
            'morphFiles.Service',
            'Note.Files.Service',
            'Note.WorkForm.Company',
            'Note.WorkForm.Orders',
            'Note.WorkForm.LatestReturnwork.User',
            'Note.FiveNote.company',
            'Note.Partials.Orders',
            'Company'
        );

        if ($this->production) {
            $this->syncCloseFinalScopeSelections();

            $this->lastReturnwork = $this->production->Note->WorkForm?->LatestReturnwork;

            $this->return['loc_install'] = $this->production->Note->WorkForm?->Orders?->sortBy('ordem')->first()?->loc_install ?? '';

            if ($this->production->dfive) {
                $this->d5   = 1;
                $this->five = $this->production->Note->FiveNote;
            }

            if (isset($this->production->Analise)) {
                $this->analise = $this->production->Analise;
            } else {
                $this->analise = new Analise();
            }

            $this->supervisionByPartnerPhotos = is_null($this->production->supervision_by_partner_photos)
                ? ''
                : ($this->production->supervision_by_partner_photos ? '1' : '0');
            $this->prepareCloseFormSnapshot();

            $this->status();

            $this->dispatchBrowserEvent('supervisionCloseModalReady', [
                'id' => 'formProductionModal',
            ]);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'formProductionModal',
            ]);
        }
    }

    private function prepareCloseFormSnapshot(): void
    {
        $note          = $this->production->Note;
        $workForm      = $note->WorkForm;
        $latestPartial = $note->Partials?->sortByDesc('created_at')->first();
        $orders        = $workForm?->Orders?->pluck('ordem')->all()
            ?: ($latestPartial?->Orders?->pluck('ordem')->all() ?? []);
        $scopeBadges = $this->production->visibleWorkReportScopeBadges(\App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION);

        $this->closeNoteDetails = [
            'type'        => $this->production->partial ? 'PARCIAL' : 'FINAL',
            'typeClass'   => $this->production->partial ? 'text-bg-warning' : 'text-bg-success',
            'note'        => $note->note ?? '---',
            'orders'      => count($orders) ? implode(', ', $orders) : '---',
            'municipio'   => $note->lexp ?? '---',
            'rubrica'     => $note->rubrica ?? '---',
            'material'    => $note->material ?? '---',
            'description' => $note->descricao ?? $note->description ?? $note->material ?? '---',
            'responsible' => $workForm?->responsible ?? $latestPartial?->responsible ?? '---',
            'company'     => $workForm?->Company?->name ?? $this->production->Company?->name ?? '---',
            'date'        => $workForm?->date
                ? Carbon::parse($workForm->date)->format('d/m/Y')
                : ($latestPartial?->created_at ? Carbon::parse($latestPartial->created_at)->format('d/m/Y') : '---'),
            'sicodeDate' => $workForm?->informed_at
                ? Carbon::parse($workForm->informed_at)->format('d/m/Y H:i:s')
                : ($this->production->partial ? 'Não aplica' : '---'),
            'scopeBadges' => count($scopeBadges) ? $scopeBadges : [[
                'label' => 'Geral',
                'class' => 'text-bg-secondary',
            ]],
        ];

        $files = collect()
            ->merge($note->Files ?? collect())
            ->merge($this->production->Files ?? collect())
            ->merge($this->production->morphFiles ?? collect())
            ->unique('id')
            ->sortBy(fn ($file) => [
                $file->service_id === $this->production->service_id ? 0 : 1,
                $file->Service->service ?? 'Outros',
                $file->file_name,
            ]);

        $tacitAdsFileIds = $files->isEmpty()
            ? []
            : DB::table('adsforms_files as af')
                ->join('adsforms as a', 'a.id', '=', 'af.adsform_id')
                ->whereIn('af.file_id', $files->pluck('id')->all())
                ->where('a.tacit', true)
                ->whereNotNull('a.work_report_id')
                ->pluck('af.file_id')
                ->flip()
                ->all();

        $isSuperAdmin                     = auth()->user()?->superadm ?? false;
        $this->productionFilesCount       = $files->count();
        $this->hasExistingProductionFiles = $files->isNotEmpty();
        $this->productionFileGroups       = $files
            ->groupBy(fn ($file) => $file->Service->service ?? 'Outros')
            ->map(function ($serviceFiles, $serviceName) use ($tacitAdsFileIds, $isSuperAdmin) {
                return [
                    'service' => $serviceName,
                    'count'   => $serviceFiles->count(),
                    'files'   => $serviceFiles->map(function ($file) use ($tacitAdsFileIds, $isSuperAdmin) {
                        $isTacitAds = isset($tacitAdsFileIds[$file->id]);
                        $isImage    = in_array(strtolower((string) $file->ext), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg'], true);

                        return [
                            'id'         => $file->id,
                            'name'       => $file->stored_name,
                            'ext'        => strtoupper((string) $file->ext),
                            'icon'       => \App\Helpers\FileIcon::getIcon($file->ext)->icon ?? 'ri-file-3-line',
                            'isTacitAds' => $isTacitAds,
                            'isBlocked'  => $isTacitAds && !$isSuperAdmin,
                            'isImage'    => $isImage,
                            'thumb'      => $isImage && (!$isTacitAds || $isSuperAdmin)
                                ? route('files.preview', ['file' => $file->id, 'thumbnail' => 1, 'v' => optional($file->updated_at)->timestamp])
                                : null,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
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

            $time = 0;

            if ($hist) {
                $time = (Carbon::parse($hist->created_at))->diffInSeconds(Carbon::now());
                $hist->update(['return_stop' => date('Y-m-d H:i:s')]);
            }

            $update = $this->production->update([
                'status'  => 3,
                'stopped' => $this->production->stopped + $time,
            ]);

            if ($update && $this->production->status !== 3) {
                // Registra Movimento Nota
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'      => $this->note->id,
                    'service_id'   => $this->production->service_id,
                    'user_id'      => Auth()->User()->id,
                    'info'         => "Usuário {$user} iniciou a Nota/OV.",
                    'status'       => 3,
                    'productionId' => $this->production->id,
                ]);
            }
        }

        $this->emitUp('refresh_list');
    }

    public function saveForm($end = false)
    {

        try {
            if ($end) {
                $this->validate();
            }

            $this->production->Analise()->updateOrCreate([], $this->analise->toArray());

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'SALVO COM SUCESSO',
            ]);
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

            return;
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
        $this->production->update([
            'status' => 27,
        ]);
        $this->production->save();
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function to_finish()
    {
        if (!$this->hasValidCloseFinalScopeSelection()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Escopo do encerramento obrigatório',
                'html'     => '<div class="card"><div class="card-body text-start">Selecione ao menos um escopo para encerrar nesta fiscalização.</div></div>',
            ]);

            return;
        }

        if (!in_array((string) $this->supervisionByPartnerPhotos, ['0', '1'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">Informe se a fiscalização se deu por fotos da parceira.</div></div>',
            ]);

            return;
        }

        if ($this->production->dfive == true) {
            $this->validate([
                'analise.info' => 'required|min:10|string',

            ], [
                'analise.info.required' => 'O campo Observações é obrigatório quando a D5 já está em retorno. Detalhe o motivo do retorno.',
            ]);
        }

        if (!$this->production->partial && !$this->production->dfive && !in_array((string) $this->d5, ['0', '1'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">Informe se existe necessidade de D5.</div></div>',
            ]);

            return;
        }

        if (!$this->production->partial && $this->d5 == '1' && !$this->production->dfive) {
            $requiredD5Fields = [
                'reason'      => 'MOTIVO',
                'codify'      => 'CÓDIGO',
                'loc_install' => 'LOCAL DE INSTALAÇÃO',
                'description' => 'OBSERVAÇÃO',
            ];

            foreach ($requiredD5Fields as $key => $label) {
                if (!$this->filledValue($this->return[$key] ?? null)) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Erros de Validação',
                        'html'     => '<div class="card"><div class="card-body text-start">O Campo em D5: ' . $label . ' é Obrigatório.</div></div>',
                    ]);

                    return;
                }
            }
        }

        if (!$this->analise->conclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">O Campo Conclusão é Obrigatório.</div></div>',
            ]);

            return;
        }

        if (!$this->analise->postes) {
            $alert = "
        <div class='card text-bg-danger py-0 my-1'>
            <div class='card-body'>
                <h4 class='fw-bold'>ATENÇÃO</h4>
                <p class='my-0'>Sua produção consta como <strong>ZERO</strong>. Este aviso é exibido mesmo que sua produção seja definida realmente como 0. Se não for seu caso, verifique novamente as informações inseridas e submeta novamente.</p>
            </div>
        </div>
    ";
        } else {
            $alert = "";
        }

        $reviewAlert = $this->buildRevisionAlert();

        if ($this->production->partial) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO PARCIAL',
                'msg'   => "Você está prestes encerrar fiscalização Parcial de <strong>{$this->production->Note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                            Uma vez encerrado, essa operação nao poderá ser desfeita.
                            <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                        </div>
                    </div>
                " . $reviewAlert . $alert,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } elseif ($this->analise->conclusion == 'OBRA NAO EXECUTADA') {

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO',
                'msg'   => "Você está prestes rejeitar a obra <strong>{$this->production->Note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao infomar que a obra não foi executada, o informe da parceira será rejeitado por não conformidade, entendemos que você seguiu todos os procedimentos, ANEXOU AS EVIDÊNCIAS e tomou as devidas providências em relação as transações no SAP.\n
                            Uma vez encerrado, essa operação nao poderá ser desfeita.
                            <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                        </div>
                    </div>
                " . $reviewAlert . $alert,
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
                'msg'   => "Você está prestes encerrar <strong>{$this->production->Note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                            Uma vez encerrado, essa operação nao poderá ser desfeita.
                            <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                        </div>
                    </div>
                " . $reviewAlert . $alert,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        }

    }

    private function buildRevisionAlert(): string
    {
        $workForm = $this->production?->Note?->WorkForm;

        if (!$workForm || !$workForm->rejected) {
            return '';
        }

        $latestReturn = $workForm->LatestReturnwork ?? $workForm->Returnwork()->latest('id')->first();
        $category     = e($latestReturn?->category ?? 'Não informado');
        $reason       = nl2br(e($latestReturn?->text_obs ?? 'Não informado'));

        return "
            <div class='card border-warning my-2'>
                <div class='card-body text-start'>
                    <h5 class='mb-2 text-warning'>Informe em revisão</h5>
                    <p class='mb-1'><strong>Por quê:</strong> {$category}</p>
                    <p class='mb-1'><strong>Motivo:</strong></p>
                    <div class='bg-light border rounded p-2'>{$reason}</div>
                </div>
            </div>
        ";
    }

    public function save()
    {
        $this->saveForm(true);

        DB::beginTransaction();

        try {
            $user = Auth()->User()->name;

            app(WorkReportScopedProductionSplitter::class)->splitRemainingScopes(
                $this->production,
                \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION,
                $this->selectedCloseFinalScopes()
            );

            $chk = $this->production->update([
                'status'                        => 5,
                'completed_at'                  => date('Y-m-d H:i:s'),
                'postes_u'                      => $this->analise ? $this->analise->postes : null,
                'completed'                     => true,
                'priority'                      => false,
                'supervision_by_partner_photos' => (string) $this->supervisionByPartnerPhotos === '1',

            ]);

            // Se for parcial, encerra a supervisão da parcial e libera para pagamento.
            if ($this->production->partial) {

                if ($this->analise->conclusion == 'reject') {

                    $text = $partial->engineer_info ?? '';
                    $text .= "\n ------------------------ \n" . "Nota/OV encerrada com rejeição em Fiscalização. \n" . "Motivo: " . $this->analise->info . "\n" . "Fiscal: " . auth()->user()->name;

                    $this->production->partialReject($text, false);

                } else {
                    $this->production->partialFiscalDone();

                }

            }

            if ($this->d5 == 1 || $this->production->dfive) {

                // $d5 = D5Return::create([
                //     'production_id' => $this->production->id,
                //     'note_id' => $this->production->note_id,
                //     'user_id'    => Auth()->User()->id,
                //     'note' => $this->return['note'] ?? trim($this->return['note']),
                //     'reason' => $this->return['reason'],
                //     'description' => $this->return['description'] ?? trim($this->return['description']),

                // ]);

                if (!$this->production->Note->FiveNote) {
                    $note             = $this->production->Note;
                    $existingFiveNote = $note->FiveNote;
                    $order            = null;

                    if ($note) {
                        $order    = $note->WorkForm?->Orders()->orderBy('ordem', 'asc')->first();
                        $workForm = $note->WorkForm;
                    }

                    $fiveNote = FiveNote::updateOrCreate(
                        [

                            'note_id' => $this->production->note_id,
                        ],
                        [
                            'reason'      => !$this->production->dfive ? $this->return['reason'] : $existingFiveNote?->reason,
                            'description' => !$this->production->dfive ? $this->return['description'] ?? $this->return['description'] : $existingFiveNote?->description,
                            'loc_install' => $this->return['loc_install'] ? trim($this->return['loc_install']) : null,
                            'conjunto'    => $this->production->Note->num_material,
                            'pep'         => $order?->pep,
                            'e_pep'       => $order?->pep,
                            'company_id'  => $this->production->Note?->WorkForm?->company_id,
                            'codify'      => $this->return['codify'] ? trim($this->return['codify']) : null,
                            'sintoms'     => $this->return['sintoms'] ? trim($this->return['sintoms']) : null,
                            'codify'      => $this->return['codify'] ? trim($this->return['codify']) : null,
                        ]
                    );

                    if ($fiveNote) {
                        $fiveNote->Productions()->syncWithoutDetaching([$this->production->id]);

                        if ($fiveNote->wasRecentlyCreated) {
                            app(D5WorkflowService::class)->onCreatedFromSupervision(
                                $fiveNote,
                                auth()->id(),
                                $this->production
                            );
                        }
                    }
                } else {

                    if (!$this->five) {
                        $this->five = $this->production->Note->FiveNote;
                    }

                    $fromStage = app(D5WorkflowService::class)->currentStage($this->five);

                    if ($this->analise->conclusion == 'FISCALIZADO COM PENDENCIAS') {
                        $this->five->update([
                            'is_completed' => false,
                            'completed_at' => null,
                            'returned'     => true,
                        ]);

                        app(D5WorkflowService::class)->onReturnedWithPending(
                            $this->five,
                            $fromStage,
                            auth()->id(),
                            $this->production
                        );
                    } else {
                        $this->five->update([
                            'is_supervisioned' => true,
                            'supervisioned_at' => now(),
                        ]);

                        app(D5WorkflowService::class)->onSupervisionApproved(
                            $this->five,
                            $fromStage,
                            auth()->id(),
                            $this->production
                        );
                    }

                    $this->five->Productions()->syncWithoutDetaching([$this->production->id]);

                }
            }

            if ($this->analise->conclusion == 'OBRA NAO EXECUTADA') {

                $wf = $this->production->Note->WorkForm;

                if ($wf) {
                    $wf->update([
                        'rejected'    => true,
                        'approved'    => false,
                        'informed_at' => null,
                    ]);

                    $wf->ReturnWork()->create([
                        'service_id' => $this->production->service_id,
                        'user_id'    => Auth()->User()->id,
                        'category'   => 'OBRA NÃO EXECUTADA',
                        'text_obs'   => 'Retorno via Fiscalização: ' . ($this->analise->info ?? 'Não informado.'),
                    ]);
                }

            }

            Notetimeline::Create([
                'note_id'    => $this->production->note_id,
                'service_id' => $this->production->service_id,
                'user_id'    => Auth()->User()->id,
                'info'       => "Usuário {$user} encerrou a Nota/OV.",
                'status'     => 5,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'ENVIADO COM SUCESSO',
            ]);

            // $this->emitTo('files.filesupervision', 'save_files');
            DB::commit();

            if (isset($fiveNote) && $this->hasEvidence) {

                $this->emitTo('files.evidence.upload-evidence', 'saveEvidences', $fiveNote->id);
            } elseif ($this->hasFile) {

                $this->emitTo('files.manager.create-prod-files', 'saveFiles');
            } else {

                $this->closeAll();

            }

        } catch (\Throwable $th) {

            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO FINALIZADO',
                'html'     => 'Não COnseguimos encerrar a atividade, tente novamente.<br>' . $th->getMessage(),
            ]);

            return;
        }
    }

    public function evidenceSaved()
    {
        if ($this->hasFile) {
            $this->emitTo('files.manager.create-prod-files', 'saveFiles');
        } else {
            $this->closeAll();
        }
    }

    public function savedFiles()
    {

        $this->emitTo('files.manager.create-prod-files', 'cleanFiles');
        $this->closeAll();
    }

    public function closeAll()
    {
        $this->analise                    = null;
        $this->five                       = null;
        $this->production                 = null;
        $this->lastReturnwork             = null;
        $this->supervisionByPartnerPhotos = '';
        $this->hasFile                    = false;
        $this->hasEvidence                = false;
        $this->closeNoteDetails           = [];
        $this->productionFileGroups       = [];
        $this->productionFilesCount       = 0;
        $this->hasExistingProductionFiles = false;
        $this->closeFinalScopeSelections  = [];
        $this->return                     = [
            'reason'      => '',
            'description' => '',
            'loc_install' => '',
            'codify'      => '',
            'sintoms'     => '',
        ];

        $this->emitTo('services.supervision.main', 'refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.services.supervision.forms.jobform');
    }

    public function closeFinalScopeOptions(): array
    {
        if (!$this->production) {
            return [];
        }

        return app(WorkReportScopedProductionSplitter::class)
            ->currentScopeOptions($this->production, \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION);
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
