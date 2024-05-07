<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;

class Viability extends Component
{
    use WithFileUploads;

    public $uploadsfiles = [];
    public $toViabilities = [];
    public $orders;
    public $hiring = false;

    public $companies;
    public $company;

    public $users;
    public $user;
    public $searchUser;


    protected $listeners = [
        'go_viability' => 'go_viability',
        'conf_viability' => 'confirm_viability'
    ];

    public function go_viability(array $orders_id)
    {
        $this->cleanAll();

        $this->orders = Order::with('Note.Files')->whereIn('id', $orders_id)->limit(5)->get();

        if ($this->orders->isNotEmpty()) {
            foreach ($this->orders as $order) {
                $this->toViabilities[] = [
                    'order' => $order->toArray(),
                    'files'  => [],
                    'hasFiles' => $order->Note->Files->isNotEmpty(),
                ];
            }

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_viability',
            ]);
        }


    }

    public function updatedUploadsfiles()
    {


        if (count($this->uploadsfiles) && count($this->toViabilities)) {
            foreach ($this->uploadsfiles as $file) {
                $fileNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $existingRelation = false;

                // Verifica se o arquivo já está relacionado a alguma viabilidade existente
                foreach ($this->toViabilities as $index => $viability) {
                    foreach ($viability['files'] as $existingFile) {
                        $existingFileNameWithoutExtension = pathinfo($existingFile->getClientOriginalName(), PATHINFO_FILENAME);

                        if ($fileNameWithoutExtension === $existingFileNameWithoutExtension) {
                            $existingRelation = true;
                            break 2; // Sai dos dois loops
                        }
                    }
                }

                // Se não houver relação existente, adiciona o arquivo à viabilidade
                if (!$existingRelation) {
                    foreach ($this->toViabilities as $index => $viability) {
                        if (strpos($fileNameWithoutExtension, $viability['order']['note']['note']) !== false) {
                            $this->toViabilities[$index]['files'][] = $file;
                            break; // Sai do loop de viabilidades
                        }
                    }
                } else {
                    // Remove o arquivo do temp caso exista.
                    $tempPath = $file->getRealPath();
                    if ($tempPath && file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                }
            }
        }



    }


    public function cancel()
    {
        if (count($this->toViabilities) > 0) {
            foreach ($this->toViabilities as $index => $viability) {
                if (count($viability['files'])) {
                    foreach ($viability['files'] as $index2 => $file) {
                        $tempPath = $file->getRealPath();
                        if ($tempPath && file_exists($tempPath)) {
                            unlink($tempPath);
                        }

                        unset($this->toViabilities[$index]['files'][$index2]);
                    }
                }
            }
        }
    }

    public function deleteFile($index, $index2)
    {
        if ($this->toViabilities[$index]['files'][$index2]) {
            $tempPath = $this->toViabilities[$index]['files'][$index2]->getRealPath();
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            unset($this->toViabilities[$index]['files'][$index2]);
        }
    }

    public function deleteRegister($index)
    {
        if (isset($this->toViabilities[$index]['files'])) {
            if (count($this->toViabilities[$index]['files'])) {
                foreach ($this->toViabilities[$index]['files'] as $index2 => $file) {
                    $tempPath = $file->getRealPath();
                    if ($tempPath && file_exists($tempPath)) {
                        unlink($tempPath);
                    }

                    unset($this->toViabilities[$index]['files'][$index2]);
                }
            }

            if (count($this->toViabilities) == 1) {
                unset($this->toViabilities[$index]);
                $this->toViabilities = [];
                $this->dispatchBrowserEvent('hideModal');
            } else {
                unset($this->toViabilities[$index]);
            }

        }
    }

    public function getTheusersProperty()
    {
        $query = User::Query();

        if (trim($this->searchUser)) {
            $query->where('name', 'like', "%".$this->searchUser."%");
        }

        return $query->where('engineer', true)->orderBy('name')->get();
    }


    public function goViability()
    {

        if (!$this->user || !$this->company) {
            foreach ($this->toViabilities as $viability) {
                if (isset($viability['files']) && !count($viability['files']) || !count($viability['files']) && !$viability['hasFiles']) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'SEM ORIENTAÇÃO DE DESTINO',
                        'html'     => 'É obrigatório a indicação da empreiteira e o responsável pela obra antes de enviar para viabilidade.',

                    ]);

                    return;
                }
            }
        }

        if (count($this->toViabilities) > 0) {
            foreach ($this->toViabilities as $viability) {
                if (isset($viability['files']) && !count($viability['files']) || !count($viability['files']) && !$viability['hasFiles']) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'ARQUIVO FALTANTE',
                        'html'     => 'Existem ORDEM sem arquivo anexado, ou sem regitro de arquivo. Verifique e tente novamente.',
                        'timer'    => 5000,
                    ]);

                    return;
                }
            }
        }

        $company = Company::find($this->company);
        $user = User::find($this->user);

        $this->dispatchBrowserEvent('alertar', [
            'title'         => "ENVIAR VIABILIDADE",
            'msg'           => "
                <p>Deseja enviar as ".count($this->toViabilities)." para {$company->name}?</p>
                <div class='card'>
                    <div class='card-body>
                        <p><span class='fw-bold'>Responsável: {$user}</span></p>
                    </div>
                </div>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'conf_viability',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Ordem foi Enviada!',

        ]);

        return;


    }

    public function cleanAll()
    {
        $this->company = "";
        $this->user = "";
    }

    public function render()
    {
        $this->users = $this->theusers;
        $this->companies = Company::orderBy('name')->get();

        return view('livewire.construction.hiring.actions.viability');
    }
}
