<?php

namespace App\Http\Livewire\Production\Return;

use App\Models\Production;
use App\Helpers\TextValidator;
use App\Models\Notetimeline;
use App\Models\ReturnRamal;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RejectInformPartial extends Component
{
    public ?Production $production = null;
    public $text_obs;

    protected $listeners = [
        'toReturn',
        'close',
        'confirm_reject_inform_partial' => 'save',
    ];

    protected $rules = [
        'text_obs' => 'required|string',
        'text_obs' => 'required|string|min:6',
    ];

    protected function messages()
    {
        return [
            'text_obs.min' => 'Texto do detalhamento muito curto.',
        ];
    }

    public function toReturn(Production $production)
    {
        $this->production = $production;



        if ($this->production->Note->Partials->isEmpty() || $this->production->Note->Partials?->last()->payment) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORME INEXISTENTE',
                'html'     => 'O INFORME pode ter sido removido da base ou ser inexistente. Comunique ao responsável este problema.'
            ]);

            return;
        }

        if ($this->production) {

            // $this->returnWork = new ReturnRamal();


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'rejectInformPartial',
            ]);

        }
    }

    public function toSave()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'REJEITAR INFORME PARCIAL',
            'msg'   => "<p><strong>VOCÊ ESTÁ PRESTES A REJEITAR O INFORME: </strong></p>
                <p class='mb-0 py-0'>OBRA: <strong>{$this->production->Note->note}</strong> </p>
                <p class='mt-0 py-0'>EMPREITEIRA: <strong>".mb_strToUpper($this->production->Note->Partials?->last()->Company->name)."</strong></p>

                <p>Deseja continuar?</p> ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Rejeitar!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_reject_inform_partial',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum informe foi rejeitado.',

        ]);
    }

    public function save()
    {
        DB::beginTransaction();

        try {


            $toUpdate = $this->production->Note->Partials->last();

            $info = $this->production->Note->Partials->last()->engineer_info . "\n==================\n".$this->text_obs."\n".Auth()->user()->name." - ".now()->format('d/m/Y H:i:s');

            if ($toUpdate) {
                $toUpdate->update([
                    'payment_at' => now(),
                    'deny' => true,
                    'allow' => false,
                    'engineer_info' => $info,
                    'complete' => true,
                ]);
            }

            $this->production->delete();


            DB::commit();

            $text = "<p>O Informe foi Rejeitado com sucesso.</p>";

            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'INFORME REJEITADO COM SUCESSO',
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
                'title'    => 'Erro ao Rejeitar o Informe',
                'html'     => $text,
            ]);

            return;

        }
    }

    public function close()
    {
        $this->production = null;
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.production.return.reject-inform-partial');
    }
}
