<?php

namespace App\Http\Livewire\Services\Cadastro\Forms;

use App\Models\{AddressCad, Analise as ModelsAnalise, Note, Notetimeline, Production};
use Carbon\Carbon;
use Livewire\Component;

class Analise extends Component
{
    public $search;

    public $conclusion;

    public $address;

    public $district;

    public $city;

    public $cod;

    public $info;

    public $exist;

    public $production;

    public $note;

    public $analise;

    public $cadastrar = false;

    public $encontrado = false;

    public $addresses;

    public $block_time = false;

    public $count;

    public $limit_pause = 5000;

    public $view_form = false;

    protected $listeners = [
        'open_analise_cadastro' => 'openAnalise',
        'analise_clean'         => 'clean',
        'confirm_goFinish'      => 'goFinish',

    ];

    public function search()
    {

        $this->cadastrar  = false;
        $this->encontrado = false;

        if (!trim($this->search)) {

            return;
        }

        // $searchTerms = explode(' ', $this->search);

        // $this->addresses = AddressCad::where(function ($query) use ($searchTerms) {
        //     foreach ($searchTerms as $term) {
        //         $query->orWhere('address', 'like', '%' . $term . '%');
        //     }
        // })->orWhere(function ($query) {
        //     $query->where('address', 'like', '%' . $this->search . '%');
        // })->get();

        $this->addresses = AddressCad::Where(function ($query) {
            $query->where('address', 'like', '%' . $this->search . '%');
        })->get();

        if ($this->addresses->count()) {
            $this->encontrado = true;
        } else {
            $this->cadastrar = true;
        }
    }

    public function UseAddress($id)
    {
        $address = AddressCad::with('Note')->find($id);

        if ($address) {
            $user = Auth()->User()->name;

            $this->info = "
RUA: {$address->address}
BAIRRO: {$address->district}
MUNICIPIO: {$address->city}
CODIGO: {$address->cod}

CADASTRADO PELA OV/NOTA: {$address->Note->note}

Att,
{$user}
            ";
        }
    }

    public function newAddress()
    {
        if ($this->encontrado) {
            $this->encontrado = false;
        }

        if (!$this->cadastrar) {
            $this->cadastrar = true;
        }
    }

    public function openAnalise($data)
    {
        $this->clean();
        $this->clean_form();

        $productionId = $data['productionId'];
        $noteId       = $data['noteId'];

        $this->production = Production::find($productionId);
        $this->note       = Note::find($noteId);

        // if (Carbon::now()->isAfter(Carbon::parse($this->production->created_at))) {
        //     $this->block_time = false;
        // } else {
        //     $this->block_time = true;
        // }

        $address = AddressCad::where('production_id', $this->production->id)->first();

        if ($address) {
            if ($this->encontrado) {
                $this->encontrado = false;
            }

            if (!$this->cadastrar) {
                $this->cadastrar = true;
            }

            $this->address  = $address->address;
            $this->district = $address->district;
            $this->city     = $address->city;
            $this->cod      = $address->cod;
        }

        // Verficando a existencia de uma analise ja atriobuida para esta produção
        $this->analise = ModelsAnalise::where('production_id', $productionId)->first();

        if ($this->analise) {
            $this->info = $this->analise->info;
        } else {
            $this->clean_form();
            $this->production->Analise()->create();
            $this->analise = ModelsAnalise::where('production_id', $productionId)->first();
        }

        if ($this->production && $this->note) {

            $time = 0;

            if ($this->production->status === 4) {
                $hist = Notetimeline::where('note_id', $this->production->note_id)->Where('service_id', $this->production->service_id)->where('status', 4)->orderBy('created_at', 'DESC')->first();

                if ($hist) {
                    $time = (Carbon::parse($hist->created_at))->diffInSeconds(Carbon::now());
                    $hist->update(['return_stop' => date('Y-m-d H:i:s')]);
                }

            }
            // Coloca nota em andamento
            $update = $this->production->update([
                'status'  => 3,
                'stopped' => $this->production->stopped + $time,
            ]);

            if ($update && $this->production->status !== 3) {
                // Registra Movimento Nota
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'    => $this->note->id,
                    'service_id' => $this->production->service_id,
                    'user_id'    => Auth()->User()->id,
                    'info'       => "Usuário {$user} iniciou a Nota/OV.",
                    'status'     => 3,
                ]);
            }

            $this->view_form = true;
        }
    }

    public function updatedConclusion($value)
    {
        // dd($value);
        if ($this->cadastrar && (!trim($this->address) || !trim($this->district) || !trim($this->city) || !trim($this->cod))) {

            return;
        }

        $user = Auth()->User()->name;

        if ($this->cadastrar) {
            $this->info = "
RUA: {$this->address}
BAIRRO: {$this->district}
MUNICIPIO: {$this->city}
CODIGO: {$this->cod}

Att,
{$user}
            ";

        }
    }

    public function save_info()
    {
        $chk = $this->analise->update([

            'conclusion' => $this->conclusion,
            'info'       => $this->info,

        ]);

        if ($this->cadastrar && (!trim($this->address) || !trim($this->district) || !trim($this->city) || !trim($this->cod))) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CAMPOS VAZIOS',
                'html'     => 'Todos os campos de cadastramento do endereço, devem obrigatóriamente serem preenchidos.
                ',
            ]);

            return;
        }

        if ($this->cadastrar) {

            $chk = AddressCad::where('production_id', $this->production->id)->first();

            if ($chk) {
                $chk->update([
                    'note_id'       => $this->note->id,
                    'production_id' => $this->production->id,
                    'analise_id'    => $this->analise->id,
                    'address'       => mb_strtoupper(trim($this->address)),
                    'district'      => mb_strtoupper(trim($this->district)),
                    'city'          => mb_strtoupper(trim($this->city)),
                    'cod'           => $this->cod,
                    'exist'         => $this->exist ? true : false,
                ]);
            } else {
                AddressCad::create([
                    'note_id'       => $this->note->id,
                    'production_id' => $this->production->id,
                    'analise_id'    => $this->analise->id,
                    'address'       => mb_strtoupper(trim($this->address)),
                    'district'      => mb_strtoupper(trim($this->district)),
                    'city'          => mb_strtoupper(trim($this->city)),
                    'cod'           => $this->cod,
                    'exist'         => $this->exist ? true : false,
                ]);
            }
        }
    }

    public function to_pause()
    {
        $this->save_info();

        $this->count = Production::Where('status', 4)->Where('service_id', $this->production->service_id)->Where('user_id', Auth()->User()->id)->count();

        if ($this->count === $this->limit_pause) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'LIMITE ATINGIDO',
                'html'     => "Você atingiu o limite máximo de pausas. Não é possível interromper esta nota. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                        adequada. 
                    </p>
                ",
            ]);

            return;
        }

        $this->emit('stop_note', ['productionId' => $this->production->id, 'noteId' => $this->production->note_id, 'limit' => $this->limit_pause]);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'pause_note',
        ]);
    }

    public function to_finish(Production $production)
    {
        if ($this->cadastrar && (!trim($this->address) || !trim($this->district) || !trim($this->city) || !trim($this->cod))) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÕES INCOMPLETAS',
                'html'     => 'Você precisa completar todos os campos do cadastro.
                ',
            ]);

            return;
        }

        // dd(strlen(trim($this->info)));

        if (!trim($this->info) || strlen(trim($this->info)) < 5) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÃO COMPLEMENTAR OBRIGATÓRIO',
                'html'     => 'VOCÊ PRECISA FORNECER INFORMAÇÕES COMPLEMENTARES OBRIGATÓRIOS.
                ',
            ]);

            return;
        }

        $this->save_info();
        $this->production = $production;
        $this->note       = Note::find($this->production->note_id);

        if (!$this->conclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CONCLUSÃO NÃO DEFINIDA',
                'html'     => 'Você não definiu uma conclusão para a nota/ov em questão. Gentileza concluir a análise da mesma.
                ',
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENCERRAMENTO DE SERVIÇO',
            'msg'   => "Você está prestes encerrar <strong>{$this->note->note}</strong>.
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
            'action'        => 'confirm_goFinish',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',

        ]);
    }

    public function goFinish()
    {
        $chk = $this->production->update([
            'status'       => 5,
            'completed_at' => date('Y-m-d H:i:s'),
            'completed'    => true,
            'confirmed'    => false,

        ]);

        if ($chk) {
            $user = Auth()->User()->name;

            Notetimeline::Create([
                'note_id'    => $this->note->id,
                'service_id' => $this->production->service_id,
                'user_id'    => Auth()->User()->id,
                'info'       => "Usuário {$user} encerrou a Nota/OV.",
                'status'     => 5,
            ]);

            $this->clean();
            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_accomany');
        }
    }

    public function clean()
    {
        $this->search     = '';
        $this->conclusion = '';
        $this->address    = '';
        $this->district   = '';
        $this->city       = '';
        $this->cod        = '';
        $this->info       = '';
        $this->exist      = false;

        $this->production = '';
        $this->note       = '';
        $this->analise    = '';

        $this->cadastrar  = false;
        $this->encontrado = false;
        $this->addresses  = '';
        $this->view_form  = false;

    }

    public function clean_form()
    {
        $this->search     = '';
        $this->conclusion = '';
        $this->address    = '';
        $this->district   = '';
        $this->city       = '';
        $this->cod        = '';
        $this->info       = '';
        $this->exist      = false;

    }

    public function render()
    {
        return view('livewire.services.cadastro.forms.analise');
    }
}
