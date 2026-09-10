<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Custom\Partial\{Ads};
use App\Models\{File, Note, WorkReport};
use App\Services\Files\FileUploadService;
use App\Traits\WithFileUploadProcessing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Storage};
use Livewire\{Component, WithFileUploads};

class ReceiveAdsfomrm extends Component
{
    use \App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
    use WithFileUploads;
    use WithFileUploadProcessing;

    public $search;

    public $note;

    public $notes;

    public $partial;

    public $file;

    public $orders = [];

    public $process = false;

    public $responsible;

    public $observation;

    public $amount;

    public $hasFile = false;

    public bool $hasAsbuiltFile = false;

    public $lateDeliveryAfterSubmit = null;
    public $selectedWorkReportId = null;
    public array $workReportOptions = [];

    // Serialized state for $theAds
    public $theAdsPath = null;

    // Protected property for the Ads object
    protected $theAds = null;

    protected $listeners = [
        'confirm_save' => 'save',
        'hasFile',
        'hasAsbuiltFile',
        'savedFiles',
    ];

    protected $rules = [
        // mimes:xlsx falha em Linux pois finfo detecta xlsx como application/zip (xlsx é ZIP internamente)
        'file' => 'nullable|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/zip,application/x-ole-storage|max:30720',
    ];

    protected $messages = [
        'file.file'  => 'O arquivo deve ser um arquivo válido.',
        'file.mimes' => 'O arquivo deve ser um arquivo do tipo: xlsx, xls.',
        'file.max'   => 'O arquivo não pode ser maior que 30MB.',
    ];

    public function mount()
    {
        $this->search     = '';
        $this->note       = null;
        $this->notes      = null;
        $this->file       = null;
        $this->theAdsPath = null;
        $this->theAds     = null;

        $user              = Auth()->User();
        $this->responsible = $user ? mb_convert_case(mb_strtolower($user->name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : null;
    }

    public function hydrate()
    {
        if (is_null($this->theAds) && $this->theAdsPath) {
            $this->theAds = new Ads($this->theAdsPath);
        } else {
            $this->theAds = $this->theAds;
        }
    }

    public function updatedFile()
    {
        $this->validateOnly('file');

        $this->process = false;

        if ($this->file) {
            // Store the path for hydration
            $this->theAdsPath = $this->file->getRealPath();
            $this->theAds     = new Ads($this->theAdsPath);
        } else {
            $this->theAdsPath = null;
            $this->theAds     = null;
        }

    }

    public function hasFile($hasFile)
    {
        $this->hasFile = $hasFile;
    }

    public function hasAsbuiltFile(bool $hasAsbuiltFile)
    {
        $this->hasAsbuiltFile = $hasAsbuiltFile;
    }

    public function savedFiles()
    {
        $html = null;

        if ($this->lateDeliveryAfterSubmit) {
            $html = "<div class='alert alert-warning text-start mb-0'><strong>Entrega em atraso:</strong><br>{$this->lateDeliveryAfterSubmit}</div>";
        }

        $this->dispatchBrowserEvent('swal', [
            'position'          => 'center',
            'icon'              => 'success',
            'title'             => 'ENVIADO COM SUCESSO',
            'html'              => $html,
            'confirmButtonText' => 'OK',
        ]);

        $this->cleanAll();
    }

    public function search()
    {
        $this->note       = null;
        $this->notes      = null;
        $this->file       = null;
        $this->theAdsPath = null;
        $this->theAds     = null;

        $this->notes = Note::where(function ($q) {
            $q->where('note', trim($this->search))
                ->orWhereRelation('Orders', 'ordem', trim($this->search));
        })
            ->with(
                'OldAds',
                'TempAdsInfos',
                'Orders'
            )
            ->get();
    }

    public function getNote($id, $workReportId = null)
    {
        $this->note = Note::with(['Orders', 'OldAds', 'TempAdsInfos'])->find($id);
        $this->workReportOptions = $this->adsDeliveryOptionsForNote($this->note);

        $eligibleIds = collect($this->workReportOptions)
            ->where('block', false)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();

        $requestedId = $workReportId ? (string) $workReportId : null;
        $this->selectedWorkReportId = $requestedId && $eligibleIds->contains($requestedId)
            ? $requestedId
            : ($eligibleIds->count() === 1 ? $eligibleIds->first() : null);
    }

    public function removeTempFile($path)
    {
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
        $this->file       = null;
        $this->theAdsPath = null;
        $this->theAds     = null;
    }

    public function processFile()
    {
        $this->process = false;

        if (!$this->selectedWorkReport()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'INFORME NÃO SELECIONADO',
                'html'     => 'Selecione o informe final correto antes de processar a ADS.',
            ]);

            return;
        }

        if (is_null($this->theAds) && $this->theAdsPath) {

            $this->theAds = new Ads($this->theAdsPath);
        }

        if (!$this->theAds->exists()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ADS INVÁLIDA',
                'html'     => "O ARQUIVO NÃO CONRRESPONDE AO MODELO DIGITAL ENTREGUE, NEM POSSUI AS INFORMAÇÕES NESCESSÁRIAS.",
            ]);

            $this->removeTempFile($this->theAdsPath);

            return;
        }

        if ($this->theAds->note != $this->note->note) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OBRA NÂO CORRESPONDENTE',
                'html'     => "A ADS REFERE-SE A OBRA <STRONG>{$this->theAds->note}</STRONG>. ENVIE A ADS CORRESPONDENTE A OBRA <STRONG>{$this->note->note}</STRONG>. .",
            ]);

            $this->removeTempFile($this->theAdsPath);

            return;
        }

        if ($this->theAds->partial) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ADS FINAL',
                'html'     => "A ADS INFORMADA PARECE NÃO ESTAR SINALIZADA COMO FINAL. VERIFIQUE O ARQUIVO E TENTE NOVAMENTE.",
            ]);

            $this->removeTempFile($this->theAdsPath);

            return;
        }

        $this->amount = $this->theAds->getValue();

        $this->process = true;
    }

    public function toSave()
    {
        $workReport = $this->selectedWorkReport();

        if (!$workReport || (bool) $workReport->rejected) {
            $reason = $this->buildRejectedWorkFormReasonHtml($workReport);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'INFORME INVÁLIDO',
                'html'     => "Selecione um Informe de Obra válido para entrega da ADS." . ($reason ? "<br><br>{$reason}" : ''),
            ]);

            return;
        }

        if (!$this->isEligibleByOrderStatusRule($workReport)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OBRA NAO ELEGIVEL',
                'html'     => 'Para envio da ADS e obrigatorio existir ORDER ativa no informe selecionado (status diferente de ENT/ENC).',
            ]);

            return;
        }

        if ($this->isAdsClosed($workReport)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ADS BLOQUEADA',
                'html'     => "O informe selecionado já possui ADS entregue e não pode ser reenviado.",
            ]);

            return;
        }

        if (trim($this->responsible) == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM RESPONSÁVEL',
                'html'     => "INSIRA O NOME DO RESPONSAVEL POR ESTE INFORME.",
            ]);

            return;
        }

        // if (!$this->hasFile) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'error',
        //         'title' => 'FALTANDO ARQUIVOS',
        //         'html' => "Favor anexar todos os arquivos necessário para envio da ADS.",
        //     ]);
        //     return;
        // }

        if (trim($this->amount)) {
            if (str_contains($this->amount, ',') && str_contains($this->amount, '.')) {
                if (strpos($this->amount, ',') > strpos($this->amount, '.')) {
                    // Format: 1.234,56 -> convert to 1234.56
                    $this->amount = str_replace('.', '', $this->amount);
                    $this->amount = str_replace(',', '.', $this->amount);
                } else {
                    // Format: 1,234.56 -> convert to 1234.56
                    $this->amount = str_replace(',', '', $this->amount);
                }
            } elseif (str_contains($this->amount, ',')) {
                // Format: 1234,56 -> convert to 1234.56
                $this->amount = str_replace(',', '.', $this->amount);
            }
            // If only dot exists, keep as is
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'VALOR ADS NÃO INFORMADO',
                'html'     => "INSIRA O VALOR DA ADS FINAL.",
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'ENVIAR ADS FINAL',
            'msg'           => $this->buildConfirmMessage(),
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_save',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma ADS foi enviada.',

        ]);
    }

    public function save()
    {
        $this->authorizePartnerAccess('conclusion_reports.ads_delivery');

        $workReport = $this->selectedWorkReport();

        if (!$workReport || (bool) $workReport->rejected) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'INFORME INVÁLIDO',
                'html'     => 'Selecione o informe correto para vincular a ADS.',
            ]);

            return;
        }

        if (!$this->isEligibleByOrderStatusRule($workReport) || $this->isAdsClosed($workReport)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ADS BLOQUEADA',
                'html'     => 'O informe selecionado não está apto para receber ADS.',
            ]);

            return;
        }

        $newName = "ADS_IFINAL_" . $this->note->note;
        $newName = $newName . "_N" . str_pad((File::where('file_name', 'like', $newName . "%")->count() + 1), 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        try {
            $adsForm             = $workReport->Adsform;
            $lateDeliveryMessage = null;

            if ($adsForm && $this->isAdsClosed($workReport)) {
                DB::rollBack();
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ADS BLOQUEADA',
                    'html'     => 'Esta ADS já foi entregue e não pode ser reenviada.',
                ]);

                return;
            }

            $payload = [
                'note_id'  => $this->note->id,
                'name'     => $this->responsible,
                'user_id'  => Auth()->User()->id,
                'obs'      => $this->observation,
                'amount'   => $this->amount ? $this->amount : 0.00,
                'contract' => $this->theAds->getContract(),
                'center'   => $this->theAds->getCenter(),
                'deposit'  => $this->theAds->getDeposit(),
                'partial'  => $this->theAds->getPartial(),
            ];

            if ($adsForm) {
                if ($adsForm->tacit && !$adsForm->tacit_delivered_at) {
                    $payload['tacit_delivered_at'] = now();

                    if ($adsForm->tacit_due_at && now()->greaterThan($adsForm->tacit_due_at)) {
                        $lateDeliveryMessage = 'A ADS está sendo entregue em atraso. Prazo vencido em ' . $adsForm->tacit_due_at->format('d/m/Y H:i:s') . '. Penalidades contratuais podem ser aplicadas.';
                    }
                }
                $adsForm->update($payload);
            } else {
                $adsForm = $workReport->Adsform()->create($payload);
            }

            $this->lateDeliveryAfterSubmit = $lateDeliveryMessage;

            if ($adsForm) {
                try {
                    $file = app(FileUploadService::class)->create(
                        $this->file,
                        $this->note,
                        '/arquivos/ADS_FINAL/',
                        $newName,
                        $this->file->getClientOriginalExtension(),
                        ['service_id' => null],
                    );

                    $adsForm->files()->attach($file->id);

                    if ($this->hasFile) {
                        $this->emitTo('files.manager.create-ads-files', 'saveFiles');
                    }
                } catch (\Throwable) {
                    DB::rollback();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'ERRO AO SALVAR',
                        'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                            <p class="fw-bold">Ocorreu um erro ao salvar um dos, ou o arquivo. Aparentemente não foi concluído o upload. Remova-o(os) da lista e tente novamente. </p>

                            </div></div>',

                    ]);

                    return;
                }
            }

            DB::commit();

            if (!$this->hasFile) {
                $this->savedFiles();
            }

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO ENVIAR',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                            <p class="fw-bold">Ocoreu algum problema ao tentar registrar o envio do Informe parcial. Revise as operações e tente novamente.</p>

                            </div></div>' . $th->getMessage(),

            ]);

            return;
        }
    }

    public function cleanAll()
    {
        $this->process     = false;
        $this->theAds      = null;
        $this->file        = null;
        $this->note        = null;
        $this->notes       = null;
        $this->search      = '';
        $this->observation = '';

        $user                          = Auth()->User();
        $this->responsible             = $user ? mb_convert_case(mb_strtolower($user->name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : null;
        $this->amount                  = '';
        $this->theAdsPath              = null;
        $this->lateDeliveryAfterSubmit = null;
        $this->hasFile                 = false;
        $this->hasAsbuiltFile          = false;
        $this->selectedWorkReportId    = null;
        $this->workReportOptions       = [];
    }

    public function adsDeliveryOptionsForNote(?Note $note): array
    {
        if (!$note) {
            return [];
        }

        $hasOldAds = $note->relationLoaded('OldAds')
            ? $note->OldAds->isNotEmpty()
            : $note->OldAds()->exists();

        return $this->activeWorkReportsForNote($note)
            ->map(function (WorkReport $workReport) use ($hasOldAds) {
                $reason = '';
                $block = false;

                if ($hasOldAds) {
                    $block = true;
                    $reason = 'DOCUMENTAÇÃO JÁ ENTREGUE';
                } elseif ((bool) $workReport->rejected) {
                    $block = true;
                    $reason = 'INFORME REJEITADO';
                } elseif (!$this->isEligibleByOrderStatusRule($workReport)) {
                    $block = true;
                    $reason = 'SEM ORDEM ATIVA NO INFORME';
                } elseif ($this->isAdsClosed($workReport)) {
                    $block = true;
                    $reason = 'DOCUMENTAÇÃO JÁ ENTREGUE';
                }

                return [
                    'id' => (string) $workReport->id,
                    'block' => $block,
                    'reason' => $reason,
                    'company' => $workReport->Company?->name ?? '---',
                    'date' => optional($workReport->informed_at ?? $workReport->created_at)->format('d/m/Y H:i') ?? '---',
                    'orders' => $workReport->Orders->pluck('ordem')->filter()->values()->all(),
                    'scopes' => $workReport->finalScopeBadges(),
                    'rejected_reason_html' => $this->buildRejectedWorkFormReasonHtml($workReport),
                ];
            })
            ->values()
            ->all();
    }

    private function activeWorkReportsForNote(Note $note): Collection
    {
        return WorkReport::query()
            ->with(['Company', 'Orders', 'LatestReturnwork.User', 'Adsform.Files', 'Note'])
            ->where('note_id', $note->id)
            ->where(function ($query) {
                $query->where('canceled', false)
                    ->orWhereNull('canceled');
            })
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();
    }

    private function selectedWorkReport(): ?WorkReport
    {
        if (!$this->note || !$this->selectedWorkReportId) {
            return null;
        }

        return $this->activeWorkReportsForNote($this->note)
            ->firstWhere('id', (int) $this->selectedWorkReportId);
    }

    private function isAdsClosed(?WorkReport $workReport = null): bool
    {
        if (!$this->note) {
            return false;
        }

        $hasOldAds = $this->note->relationLoaded('OldAds')
            ? $this->note->OldAds->isNotEmpty()
            : $this->note->OldAds()->exists();

        if ($hasOldAds) {
            return true;
        }

        $workReport = $workReport ?: $this->selectedWorkReport();

        if (!$workReport?->Adsform) {
            return false;
        }

        $adsForm = $workReport->Adsform;

        if ($adsForm->tacit && !$adsForm->tacit_delivered_at) {
            return false;
        }

        return $adsForm->files()->exists() || (bool) $adsForm->tacit_delivered_at;
    }

    private function buildConfirmMessage(): string
    {
        $workReport = $this->selectedWorkReport();
        $scopes = $workReport
            ? collect($workReport->finalScopeBadges())->pluck('label')->implode(', ')
            : '---';

        return "
            Você deseja informar o ADS da obra {$this->note->note} Final?</br></br>
            <p class='mb-2'><strong>Informe:</strong> #{$workReport?->id} {$scopes}</p>
            <div class='card card-light'>
            <div class='card-body'>
            <p>Uma vez enviado, não será mais possível re-submeter. Confira se toda documentação Necessária está presente.</p>
            </div>
            </div>
            ";
    }

    public function getRejectedWorkFormReasonProperty(): string
    {
        return $this->buildRejectedWorkFormReasonText($this->selectedWorkReport());
    }

    private function buildRejectedWorkFormReasonText(?WorkReport $workForm = null): string
    {
        if (!$workForm?->rejected) {
            return '';
        }

        $latestReturn = $workForm->relationLoaded('LatestReturnwork')
            ? $workForm->LatestReturnwork
            : $workForm->LatestReturnwork()->first();

        $category = trim((string) ($latestReturn?->category ?? ''));
        $textObs  = trim((string) ($latestReturn?->text_obs ?? ''));

        $parts = [];

        if ($category !== '') {
            $parts[] = "Motivo: {$category}";
        }

        if ($textObs !== '') {
            $parts[] = "Observação: {$textObs}";
        }

        if (empty($parts)) {
            return 'Informe rejeitado (sem detalhe registrado).';
        }

        return implode(' | ', $parts);
    }

    private function buildRejectedWorkFormReasonHtml(?WorkReport $workForm = null): string
    {
        $text = $this->buildRejectedWorkFormReasonText($workForm);

        if ($text === '') {
            return '';
        }

        return "<strong>Motivo do bloqueio:</strong><br>{$text}";
    }

    private function isEligibleByOrderStatusRule(?WorkReport $workReport = null): bool
    {
        $workReport = $workReport ?: $this->selectedWorkReport();

        if (!$workReport) {
            return false;
        }

        $hasOrders = $workReport->Orders()->exists();

        if (!$hasOrders) {
            return false;
        }

        return $workReport->Orders()
            ->where(function ($query) {
                $query->where('statusSist', 'not like', 'ENT%')
                    ->where('statusSist', 'not like', 'ENC%');
            })
            ->exists();
    }

    public function render()
    {
        return view('livewire.partner.forms.receive-adsfomrm', [
            'myAds' => $this->theAds,
        ]);
    }
}
