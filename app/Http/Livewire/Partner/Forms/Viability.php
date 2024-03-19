<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\Edp_depc\City;
use App\Models\Note;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;
use Livewire\WithFileUploads;

class Viability extends Component
{
    use WithFileUploads;

    public $data;
    public $cities;
    public $changes = "";
    public $result = [];

    // Files
    public $files = [];
    public $show_files = [];

    protected $queryString = [
        'changes' => ['except' => '']
    ];

    protected $listeners = [
        'confirm_cancelForm' => 'cancelForm'
    ];



    public function mount($id)
    {
        try {

            $this->cities = City::orderBy('cidade')->get();

        } catch (\Throwable $th) {

            $this->cities = false;
        }

        $this->result = ['sizechange' => 0];

        if ($id) {
            $this->data = Note::With(['Viabilities' => function ($query) {
                $query->where('approved', false)
                        ->where('tacit', false)
                        ->where('canceled', false)
                        ->with('Order');
            }])->find(Crypt::decrypt($id));
        }


    }

    public function updatedFiles()
    {


        if (count($this->files)) {

            $this->show_files = [];

            foreach ($this->files as $index => $file) {

                $skip_file = false;

                if (!$skip_file) {

                    if (count($this->files) > 1) {

                        $name = "CROQUI-{$this->data->note}-F". str_pad($index + 1, 2, '0', STR_PAD_LEFT) ."_".str_pad(count($this->files), 2, '0', STR_PAD_LEFT);
                    } else {
                        $name = "CROQUI-{$this->data->note}-F01_01";
                    }

                    $this->show_files[$index] = [
                        'id' => $index,
                        'note_id' => "",
                        'name' => $name,
                        'old_name' =>  explode('.', $file->getClientOriginalName())[0],
                        'ext' => $file->getClientOriginalExtension(),
                        'chk' => false,
                    ];
                }
            }


        }
    }

    public function delete_file($id)
    {
        if (isset($this->show_files[$id])) {
            unset($this->files[$id]);
            unset($this->show_files[$id]);
        }

        $this->updatedFiles();
    }

    public function updatedChanges()
    {
        $this->result = [];
    }

    public function toCancelForm()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title' =>  'CANCELAR FORMULÁRIO',
            'msg' => "Você deseja cancelar este formulário e voltar para a listagem de viabilidade?",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Cancele e volte!',
            'btnCanceltxt' => 'Não, Desisto',
            'action' => "confirm_cancelForm",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'O Formulário foi cancelado com sucesso.',
        ]);
    }

    public function cancelForm()
    {
        return redirect(route('partner.todo.viability'));
    }

    public function render()
    {



        return view('livewire.partner.forms.viability', [
            'note' => $this->data
        ])->layout('layouts.forms.viability');

    }
}
