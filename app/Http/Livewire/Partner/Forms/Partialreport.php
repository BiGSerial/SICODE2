<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Custom\Partial\Ads;
use App\Custom\Partial\Rules;
use App\Models\File;
use App\Models\Note;
use App\Models\Order;
use App\Models\Partial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Partialreport extends Component
{
    use WithFileUploads;

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

    protected $theAds = null;

    protected $listeners = [
        'confirm_save' => 'save'
    ];

    public function mount()
    {
        $this->search = '';
        $this->note = null;
        $this->notes = null;
        $this->file = null;
    }

    public function updatedFile()
    {

        $this->process = false;
        $this->theAds = null;
    }

    public function search()
    {

        $this->note = null;
        $this->notes = null;
        $this->file = null;

        $this->notes = Note::where(function ($q) {
            $q->where('note', trim($this->search))
            ->orWhereRelation('Orders', 'ordem', trim($this->search));
        })
        ->with('Orders', 'WorkForm', 'Partials')->get();
    }

    public function getNote($id)
    {
        $this->note = Note::find($id);
    }

    public function processFile()
    {
        $this->process = false;

        $path = $this->file->getRealPath();


        $this->theAds = new Ads($path);

        if (!$this->theAds->exists()) {

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

        $this->amount = $this->theAds->getValue();

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

        $newName = "ADS_IPARC_".$this->note->note;
        $newName = $newName."_N".str_pad((File::where('file_name', 'like', $newName."%")->count() + 1), 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        try {

            $partial = Partial::create(
                [
                    'note_id' => $this->note->id,
                    'company_id' => Auth()->User()->Employee->Contract->company_id,
                    'user_id' => Auth()->User()->id,
                    'observation' => $this->observation,
                    'responsible' => $this->responsible,
                    'value' => $this->amount ? $this->amount : 0.00,
                ]
            );

            if ($partial) {

                $orders = Order::where('note_id', $this->note->id)->where('statusSist', 'Not Like', "ENT%")->where('statusSist', 'Not Like', "ENC%")->get();
                // Order::where(
                //     'note_id',
                //     $this->note->id
                // )->where('statusSist', 'Not Like', "ENT%")->where('statusSist', 'Not Like', "ENC%")->get();
                if ($orders) {
                    foreach ($orders as $order) {
                        $partial->Orders()->attach($order->id);
                    }
                }

                $caminho = $this->file->storeAs('/arquivos/ADS/', $newName.'.'.$this->file->getClientOriginalExtension());

                if (Storage::exists($caminho)) {
                    $partial->Files()->create([
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
                } else {
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

            $this->cleanAll();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'ENVIADO COM SUCESSO',
                'timer'    => 2500,

            ]);

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO ENVIAR',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                            <p class="fw-bold">Ocoreu algum problema ao tentar registrar o envio do Informe parcial. Revvise as operações e tente novamente.</p>

                            </div></div>'.$th->getMessage(),

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
    }

    public function render()
    {
        return view('livewire.partner.forms.partialreport', [
            'myAds' => $this->theAds
        ]);
    }
}
