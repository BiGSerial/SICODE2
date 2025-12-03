<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Custom\Partial\Ads;
use App\Custom\Partial\Rules;
use App\Models\File;
use App\Models\Note;
use App\Models\Order;
use App\Models\Partial;
use App\Traits\WithFileUploadProcessing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReceiveAdsfomrm extends Component
{
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

    // Serialized state for $theAds
    public $theAdsPath = null;

    // Protected property for the Ads object
    protected $theAds = null;

    protected $listeners = [
        'confirm_save' => 'save',
        'hasFile',
        'savedFiles'
    ];

    protected $rules = [
        'file' => 'nullable|file|mimes:xlsx,xls|max:30720', // 30 MB in kilobytes
    ];

    protected $messages = [
        'file.file' => 'O arquivo deve ser um arquivo válido.',
        'file.mimes' => 'O arquivo deve ser um arquivo do tipo: xlsx, xls.',
        'file.max' => 'O arquivo não pode ser maior que 30MB.',
    ];

    public function mount()
    {
        $this->search = '';
        $this->note = null;
        $this->notes = null;
        $this->file = null;
        $this->theAdsPath = null;
        $this->theAds = null;
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
            $this->theAds = new Ads($this->theAdsPath);
        } else {
            $this->theAdsPath = null;
            $this->theAds = null;
        }

    }

    public function hasFile($hasFile)
    {
        $this->hasFile = $hasFile;
    }

    public function savedFiles()
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ENVIADO COM SUCESSO',
            'timer'    => 2500,
        ]);

        $this->cleanAll();
    }

    public function search()
    {
        $this->note = null;
        $this->notes = null;
        $this->file = null;
        $this->theAdsPath = null;
        $this->theAds = null;

        $this->notes = Note::where(function ($q) {
            $q->where('note', trim($this->search))
                ->orWhereRelation('Orders', 'ordem', trim($this->search));
        })
            ->with('WorkForm.Orders', 'Adsform', 'TempAdsInfos')->get();
    }

    public function getNote($id)
    {
        $this->note = Note::find($id);
    }

    public function removeTempFile($path)
    {
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
        $this->file = null;
        $this->theAdsPath = null;
        $this->theAds = null;
    }

    public function processFile()
    {
        $this->process = false;

        if (is_null($this->theAds) && $this->theAdsPath) {


            $this->theAds = new Ads($this->theAdsPath);
        }

        if (!$this->theAds->exists()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'ADS INVÁLIDA',
                'html' => "O ARQUIVO NÃO CONRRESPONDE AO MODELO DIGITAL ENTREGUE, NEM POSSUI AS INFORMAÇÕES NESCESSÁRIAS.",
            ]);

            $this->removeTempFile($this->theAdsPath);

            return;
        }



        if ($this->theAds->note != $this->note->note) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'OBRA NÂO CORRESPONDENTE',
                'html' => "A ADS REFERE-SE A OBRA <STRONG>{$this->theAds->note}</STRONG>. ENVIE A ADS CORRESPONDENTE A OBRA <STRONG>{$this->note->note}</STRONG>. .",
            ]);

            $this->removeTempFile($this->theAdsPath);

            return;
        }

        if ($this->theAds->partial) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'ADS FINAL',
                'html' => "A ADS INFORMADA PARECE NÃO ESTAR SINALIZADA COMO FINAL. VERIFIQUE O ARQUIVO E TENTE NOVAMENTE.",
            ]);

            $this->removeTempFile($this->theAdsPath);

            return;
        }

        $this->amount = $this->theAds->getValue();

        $this->process = true;
    }

    public function toSave()
    {
        if (trim($this->responsible) == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'SEM RESPONSÁVEL',
                'html' => "INSIRA O NOME DO RESPONSAVEL POR ESTE INFORME.",
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
                'icon' => 'error',
                'title' => 'VALOR ADS NÃO INFORMADO',
                'html' => "INSIRA O VALOR DA ADS FINAL.",
            ]);
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENVIAR ADS FINAL',
            'msg' => "
            Você deseja informar o ADS da obra {$this->note->note} Final?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p>Uma vez enviado, não será mais possível re-submeter. Confira se toda documentação Necessária está presente.</p>
            </div>
            </div>
            ",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Envie!',
            'btnCanceltxt' => 'Não, Cancele!',
            'action' => 'confirm_save',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhuma ADS foi enviada.',

        ]);
    }

    public function save()
    {
        $newName = "ADS_IFINAL_" . $this->note->note;
        $newName = $newName . "_N" . str_pad((File::where('file_name', 'like', $newName . "%")->count() + 1), 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        try {
            $adsForm = $this->note->WorkForm->Adsform()->create(
                [
                    'note_id' => $this->note->id,
                    'name' => Auth()->User()->Employee->Contract->company_id,
                    'user_id' => Auth()->User()->id,
                    'obs' => $this->observation,
                    'name' => $this->responsible,
                    'amount' => $this->amount ? $this->amount : 0.00,
                    'contract' => $this->theAds->getContract(),
                    'center' => $this->theAds->getCenter(),
                    'deposit' => $this->theAds->getDeposit(),
                    'partial' => $this->theAds->getPartial(),
                ]
            );

            if ($adsForm) {
                $caminho = $this->file->storeAs('/arquivos/ADS_FINAL/', $newName . '.' . $this->file->getClientOriginalExtension());

                if (Storage::exists($caminho)) {
                    $file = File::create([
                        'note_id' => $this->note->id,
                        'user_id' => Auth()->User()->id,
                        'service_id' => null,
                        'file_name' => $newName,
                        'original_name' => $this->file->getClientOriginalName(),
                        'path' => $caminho,
                        'ext' => $this->file->getClientOriginalExtension(),
                        'suspicious' => false,
                        'noexists' => false,
                    ]);

                    if ($file) {
                        $adsForm->files()->attach($file->id);

                        if ($this->hasFile) {
                            $this->emitTo('files.manager.create-ads-files', 'saveFiles');
                        }
                    }
                } else {
                    DB::rollback();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon' => 'warning',
                        'title' => 'ERRO AO SALVAR',
                        'html' => '<div class="card bg-primary text-white"><div class="card-body">
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
                'icon' => 'error',
                'title' => 'ERRO AO ENVIAR',
                'html' => '<div class="card bg-primary text-white"><div class="card-body">
                            <p class="fw-bold">Ocoreu algum problema ao tentar registrar o envio do Informe parcial. Revise as operações e tente novamente.</p>

                            </div></div>' . $th->getMessage(),

            ]);

            return;
        }
    }

    public function cleanAll()
    {
        $this->process = false;
        $this->theAds = null;
        $this->file = null;
        $this->note = null;
        $this->notes = null;
        $this->search = '';
        $this->observation = '';
        $this->responsible = '';
        $this->amount = '';
        $this->theAdsPath = null;
    }

    public function render()
    {
        return view('livewire.partner.forms.receive-adsfomrm', [
            'myAds' => $this->theAds
        ]);
    }
}
