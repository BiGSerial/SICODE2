<?php

namespace App\Http\Livewire\Production\Return;

use App\Models\Production;
use App\Models\ReturnWork as ModelsReturnWork;
use App\Helpers\TextValidator;
use Livewire\Component;

class ReturnWork extends Component
{
    public ?Production $production = null;
    public ?ModelsReturnWork $returnWork = null;

    protected $listeners = [
        'toReturn',
        'close',
        'confirm_return_work' => 'save',
    ];

    protected $rules = [
        'returnWork.category' => 'required|string',
        'returnWork.text_obs' => 'required|string|min:6',
    ];

    protected function messages()
    {
        return [
            'returnWork.category.required' => 'É obrigatório selecionar a categotria.',
            'returnWork.text_obs.required' => 'É obrigatório detalhar o motivo do retorno.',
            'returnWork.text_obs.min' => 'Texto do detalhamento muito curto.',
        ];
    }

    public function toReturn(Production $production)
    {
        $this->production = $production;



        if (!isset($this->production->Note->WorkForm->id)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORME INEXISTENTE',
                'html'     => 'O INFORME pode ter sido removido da base ou ser inexistente. Comunique ao responsável este problema.'
            ]);

            return;
        }

        if ($this->production) {

            $this->returnWork = new ModelsReturnWork();


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'returnWorkform',
            ]);

        }
    }

    public function toSave()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'DEVOLVER INFORME',
            'msg'   => "<p><strong>VOCÊ ESTÁ PRESTES A DEVOLVER O INFORME: </strong></p>
                <p class='mb-0 py-0'>OBRA: <strong>{$this->production->Note->note}</strong> </p>
                <p class='mt-0 py-0'>EMPREITEIRA: <strong>".mb_strToUpper($this->production->Note->WorkForm->Company->name)."</strong></p>

                <p>Deseja continuar?</p> ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Devolva!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_return_work',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function save()
    {
        try {

            $this->production->Note->WorkForm->update([
                'rejected' => true,
                'informed_at' => null,
            ]);

            $this->returnWork->work_report_id = $this->production->Note->WorkForm->id;
            $this->returnWork->service_id = $this->production->service_id;
            $this->returnWork->user_id = Auth()->User()->id;
            $this->returnWork->save();

            $this->production->Note->WorkForm->update([
                'retry' => $this->production->Note->WorkForm->Returnwork->count(),
            ]);


            $this->production->delete();

            $this->emitUp('refresh_list');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {

            $text = "";

            foreach ($e->errors() as $error) {
                $text .= $error[0]."<br>";
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CAMPOS OBRIGATÓRIO',
                'html'     => $text,
            ]);

            return;

        }
    }

    public function close()
    {
        $this->production = null;
        $this->returnWork = null;
        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.production.return.return-work');
    }
}
