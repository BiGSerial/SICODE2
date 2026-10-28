<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Custom\Partial\{Ads};
use App\Models\{File, Note, Partial};
use App\Services\Files\FileUploadService;
use App\Services\Partner\{PartialScopeAvailabilityService, WorkReportBudgetGuard};
use Illuminate\Support\Facades\{DB};
use Livewire\{Component, WithFileUploads};

class Partialreport extends Component
{
    use \App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
    use WithFileUploads;

    public $search;

    public $note;

    public $notes;

    public $partial;

    public $file;

    public $orders = [];

    public string $scope = 'general';

    public $process = false;

    public $responsible;

    public $observation;

    public $amount;

    public bool $hasFiles = false;

    public $theAdsPath = null;

    protected $theAds = null;

    protected $listeners = [
        'confirm_save' => 'save',
        'hasFile',
        'savedFiles',
    ];

    public function hasFile(bool $hasFile)
    {
        $this->hasFiles = $hasFile;
    }

    public function savedFiles()
    {
        $this->emitTo('files.manager.create-gen-files', 'cleanFiles');
        $this->finishSaveSuccess();
    }

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
        }
    }

    public function updatedFile()
    {

        $this->process = false;

        if ($this->file) {
            $this->theAdsPath = $this->file->getRealPath();
            $this->theAds     = new Ads($this->theAdsPath);
        } else {
            $this->theAdsPath = null;
            $this->theAds     = null;
        }
    }

    public function search()
    {

        $this->note       = null;
        $this->notes      = null;
        $this->file       = null;
        $this->theAdsPath = null;
        $this->theAds     = null;
        $this->process    = false;

        $this->notes = Note::where(function ($q) {
            $q->where('note', trim($this->search))
            ->orWhereRelation('Orders', 'ordem', trim($this->search));
        })
        ->with('Orders', 'WorkForms.Note', 'WorkForms.Orders', 'Partials.Orders')->get();
    }

    public function getNote($id, string $scope = 'general')
    {
        $note = Note::with('Orders', 'WorkForms.Note', 'WorkForms.Orders', 'Partials.Orders')->find($id);

        $scopeData = $note
            ? app(PartialScopeAvailabilityService::class)->findAvailableScope($note, $scope)
            : null;

        if (!$scopeData) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ESCOPO INDISPONÍVEL',
                'html'     => 'Este escopo não está mais disponível para informe parcial. Atualize a busca e tente novamente.',
            ]);

            return;
        }

        $budget = app(WorkReportBudgetGuard::class);

        if ($peps = $budget->blockedPeps($scopeData['open_orders'])) {
            $this->dispatchBrowserEvent('swal', $budget->swal($note->note));

            return;
        }

        $this->note     = $note;
        $this->scope    = $scope;
        $this->orders   = collect($scopeData['open_orders'])->pluck('id')->all();
        $this->hasFiles = false;
    }

    public function scopeRows(Note $note): array
    {
        return app(PartialScopeAvailabilityService::class)->for($note);
    }

    public function getScopeLabelProperty(): string
    {
        return app(PartialScopeAvailabilityService::class)->label($this->scope);
    }

    public function processFile()
    {
        $this->process = false;

        if (is_null($this->theAds) && $this->theAdsPath) {
            $this->theAds = new Ads($this->theAdsPath);
        }

        if (!$this->theAds || !$this->theAds->exists()) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ADS INVÁLIDA',
                'html'     => "O ARQUIVO NÃO CONRRESPONDE AO MODELO ENTREGUE, NEM POSSUI AS INFORMAÇÕES NESCESSÁRIAS.",

            ]);

            return;
        }

        if ($this->theAds->note != $this->note->note) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OBRA NÂO CORRESPONDENTE',
                'html'     => "A ADS REFERE-SE A OBRA <STRONG>{$this->theAds->note}</STRONG>. ENVIE A ADS CORRESPONDENTE A OBRA <STRONG>{$this->note->note}</STRONG>. .",

            ]);

            return;
        }

        if (!$this->theAds->partial) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ADS NÃO PARCIAL',
                'html'     => "A ADS INFORMADA PARECE NÃO ESTAR SINALIZADA COMO PARCIAL. VERIFIQUE O ARQUIVO E TENTE NOVAMENTE.",

            ]);

            return;
        }

        $this->amount = number_format($this->theAds->getValue(), 2, ',', '.');

        $this->process = true;
    }

    public function toSave()
    {
        if (trim($this->responsible) == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM RESPONSÁVEL',
                'html'     => "INSIRA O NOME DO RESPONSAVEL POR ESTE INFORME.",
            ]);

            return;
        }

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
                'html'     => "INSIRA O VALOR DA ADS PARCIAL.",
            ]);

            return;
        }

        if ($this->amountExceedsBudget()) {
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENVIAR OBRA PARCIAL',
            'msg'   => "
            Você deseja informar a obra {$this->note->note} parcialmente?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p>A informação de obra parcial, seguirá para apreciação do Engenheiro responsável.</p>
            </div>
            </div>
            ",
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
        $this->authorizePartnerAccess('partial_reports.create');

        $scopeData = app(PartialScopeAvailabilityService::class)->findAvailableScope($this->note, $this->scope);

        if (!$scopeData) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ESCOPO INDISPONÍVEL',
                'html'     => 'O escopo selecionado foi concluído ou recebeu outra parcial enquanto este formulário estava aberto.',
            ]);

            return;
        }

        // O saldo pode ter zerado desde a seleção da obra: confere de novo antes de gravar.
        $budget = app(WorkReportBudgetGuard::class);

        if ($peps = $budget->blockedPeps($scopeData['open_orders'])) {
            $this->dispatchBrowserEvent('swal', $budget->swal($this->note->note));

            return;
        }

        if ($this->amountExceedsBudget($scopeData['open_orders'])) {
            return;
        }

        $newName = 'ADS_IPARC_' . $this->note->note . '_' . mb_strtoupper($this->scope);
        $newName = $newName . "_N" . str_pad((File::where('file_name', 'like', $newName . "%")->count() + 1), 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        try {

            $partial = Partial::create(
                [
                    'note_id'     => $this->note->id,
                    'scope'       => $this->scope,
                    'company_id'  => Auth()->User()->company_id,
                    'user_id'     => Auth()->User()->id,
                    'observation' => $this->observation,
                    'responsible' => $this->responsible,
                    'value'       => $this->amount ? $this->amount : 0.00,
                ]
            );

            if ($partial) {

                foreach ($scopeData['open_orders'] as $order) {
                    $partial->Orders()->attach($order->id);
                }

                try {
                    $file = app(FileUploadService::class)->create(
                        $this->file,
                        $this->note,
                        '/arquivos/ADS/',
                        $newName,
                        $this->file->getClientOriginalExtension(),
                        ['service_id' => null],
                    );

                    $partial->Files()->syncWithoutDetaching([$file->id]);
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

            if ($this->hasFiles) {
                $this->emitTo('files.manager.create-gen-files', 'saveFiles', Partial::class, $partial->id);

                return;
            }

            $this->finishSaveSuccess();

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO ENVIAR',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                            <p class="fw-bold">Ocoreu algum problema ao tentar registrar o envio do Informe parcial. Revvise as operações e tente novamente.</p>

                            </div></div>' . $th->getMessage(),

            ]);

            return;
        }
    }

    /** Valor da ADS maior que o saldo dos PEPs da parcial: mostra o mesmo aviso de bloqueio, sem revelar o saldo. */
    private function amountExceedsBudget(?iterable $orders = null): bool
    {
        $orders ??= app(PartialScopeAvailabilityService::class)->findAvailableScope($this->note, $this->scope)['open_orders'] ?? [];
        $budget = app(WorkReportBudgetGuard::class);

        if (!($peps = $budget->exceededPeps($orders, (float) $this->amount))) {
            return false;
        }

        $this->dispatchBrowserEvent('swal', $budget->swal($this->note->note));

        return true;
    }

    public function cleanAll()
    {
        $this->process     = false;
        $this->theAds      = null;
        $this->theAdsPath  = null;
        $this->file        = null;
        $this->note        = null;
        $this->notes       = null;
        $this->search      = '';
        $this->observation = '';
        $this->scope       = 'general';
        $this->orders      = [];
        $this->hasFiles    = false;

        $user              = Auth()->User();
        $this->responsible = $user ? mb_convert_case(mb_strtolower($user->name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : null;
    }

    private function finishSaveSuccess(): void
    {
        $this->cleanAll();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ENVIADO COM SUCESSO',
            'timer'    => 2500,
        ]);
    }

    public function render()
    {
        return view('livewire.partner.forms.partialreport', [
            'myAds' => $this->theAds,
        ]);
    }
}
