<?php

namespace App\Http\Livewire\Production\Return;

use App\Models\Production;
use App\Helpers\TextValidator;
use App\Models\Notetimeline;
use App\Models\ReturnRamal;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnRamalWork extends Component
{
    public ?Production $production = null;
    public ?ReturnRamal $returnWork = null;

    protected $listeners = [
        'toReturn',
        'close',
        'confirm_return_ramal' => 'save',
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



        if (!isset($this->production->Note->RamalForm->id)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORME INEXISTENTE',
                'html'     => 'O INFORME pode ter sido removido da base ou ser inexistente. Comunique ao responsável este problema.'
            ]);

            return;
        }

        if ($this->production) {

            $this->returnWork = new ReturnRamal();


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'returnRamalform',
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
                <p class='mt-0 py-0'>EMPREITEIRA: <strong>".mb_strToUpper($this->production->Note->RamalForm->Company->name)."</strong></p>

                <p>Deseja continuar?</p> ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Devolva!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_return_ramal',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum informe foi devolvido.',

        ]);
    }

    public function save()
    {
        DB::beginTransaction();

        try {

            $this->production->Note->RamalForm->update([
                'rejected' => true,
                'informed_at' => null,
                'retry' => $this->production->Note->RamalForm->retry + 1,
                'rejected_at' => date('Y-m-d H:i:s'),
            ]);

            $this->returnWork->ramal_report_id = $this->production->Note->RamalForm->id;
            $this->returnWork->service_id = $this->production->service_id;
            $this->returnWork->user_id = Auth()->User()->id;
            $this->returnWork->save();

            $this->production->update([
                'status' => 20,
            ]);

            Notetimeline::create([
                'note_id' => $this->production->note_id,
                'service_id' => $this->production->service_id,
                'user_id' => Auth()->User()->id,
                'info' => "Devolução do Informe: O informe foi devolvido a digitação para correção.",
                'status' => 20,
                'system' => 0,
                'production_id' => $this->production->id,
                'return_stop' => null,
                'category' => $this->returnWork->category,
            ]);
            // $this->production->delete();

            DB::commit();

            $text = "<p>A Obra em questão foi devolvida a Digitação com sucesso. Ela voltará a aparecer em sua
            pilha, assim que a digitação retornar com as informações</p>";

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OBRA DEVOLVIDA COM SUCESSO',
                'html'     => $text,
            ]);

            $this->emitUp('refresh_list');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {

            DB::rollBack();
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
        $this->emit('refresh_list');
        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.production.return.return-ramal-work');
    }
}
