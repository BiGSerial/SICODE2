<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\File;
use App\Models\Order;
use App\Models\User;
use App\Models\Viability as ModelsViability;
use Illuminate\Support\Facades\DB;
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

                    $noRelation = true;

                    foreach ($this->toViabilities as $index => $viability) {
                        if (strpos($fileNameWithoutExtension, $viability['order']['note']['note']) !== false) {
                            $noRelation = false;
                            $this->toViabilities[$index]['files'][] = $file;
                            break; // Sai do loop de viabilidades
                        }
                    }

                    if ($noRelation) {
                        // Remove o arquivo do temp caso exista.
                        $tempPath = $file->getRealPath();
                        if ($tempPath && file_exists($tempPath)) {
                            unlink($tempPath);
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
                <p>Deseja enviar <span class='fw-bold'>".count($this->toViabilities)."</span> obra(s) para <span class='fw-bold'>{$company->name}</span>?</p>
                <div class='card'>
                    <div class='card-body text-left'>
                        <p class='fw-bold'>Responsável:<span class='fw-normal'> {$user->name}</span></p>
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

    public function confirm_viability()
    {

        // dd($this->toViabilities);

        if (empty($this->toViabilities)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO PROCESSO',
                'html'     => 'A variável do sistema está vazia',

            ]);

            return;
        }

        DB::beginTransaction();

        foreach ($this->toViabilities as $order) {

            // dd($order['order']['id'], );

            if (!count($order['files']) && !$order['hasFiles']) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ERRO PROCESSO',
                    'html'     => 'Aquivos nao encontrado. Verifique as informações e tente novamente. Todos os processos foram cancelados.',

                ]);

                DB::rollback();

                return;
            }

            $checkExistsViability = ModelsViability::where('order_id', $order['order']['id'])
                                    ->where(function ($query) {
                                        $query->where('hired', false)
                                            ->orWhere('completed', false);
                                    })
                                    ->count();

            if (!$checkExistsViability) {

                $created_viab = ModelsViability::Create([
                    'order_id'    => $order['order']['id'],
                    'company_id'  => $this->company,
                    'user_id'     => Auth()->User()->id,
                    'engineer_id' => $this->user,
                    'sended_at'   => date('Y-m-d H:i:s'),
                    'hired'       => $this->hiring ? true : false,
                    'hired_at'    => $this->hiring ? date('Y-m-d H:i:s') : null,
                    'status'      => 1,
                ]);

                if ($created_viab) {

                    foreach ($order['files'] as $index => $file) {

                        $folhas = count($order['files']);

                        $newName = "PROJETO_".$order['order']['note']['note']."_F"
                                .str_pad(++$index, 2, '0', STR_PAD_LEFT)."-"
                                .str_pad($folhas, 2, '0', STR_PAD_LEFT);

                        $version = File::where('file_name', 'like', "%".$newName."%")->count();

                        $newName = $newName."_rev".$version.".".$file->getClientOriginalExtension();

                        $caminho = "";

                        // dd($newName);

                        $caminho = $file->store('/arquivos');

                        if ($caminho) {

                            $created_viab->Files()->create([
                                'note_id'    => $order['order']['note']['id'],
                                'user_id'    => Auth()->User()->id,
                                'service_id' => null,
                                'file_name'  => $newName,
                                'path'       => $caminho,
                                'ext'        => $file->getClientOriginalExtension(),
                            ]);
                        }
                    }


                } else {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'ERRO PROCESSO',
                        'html'     => 'Não foi possível viabilizar aguma das Ordens. Tente novamente. PROCESSOS CANCELADOS',

                    ]);

                    DB::rollback();

                    return;
                }
            }
        }

        DB::commit();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'VIABILIDAED ENVIADA COM SUCESSO',
        ]);

        $this->cleanAll();
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_list');

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
