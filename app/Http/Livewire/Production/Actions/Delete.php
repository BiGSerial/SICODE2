<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\SicodeSql\Production as SicodeSqlProduction;
use App\Models\{Analise, Notetimeline, Prodtransfer, Production, Wpa};
use Livewire\Component;

class Delete extends Component
{
    public ?Production $production = null;

    public $chave;

    public $listeners = [
        'confirm_delete' => 'confirm_delete',
    ];

    public function mount($production, $chave)
    {

        $this->production = $production;
        $this->chave      = $chave;
    }

    public function to_delete()
    {

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'REMOVER DESPACHO',
            'msg'   => "Você está prestes a remover {$this->production->load('Note')->Note->note} da produção. Esteja ciente ao fazer isso de forma inadequada poderá prejudicar a medição do usuário ou empresa. 
                Lembrando que a exclusão também removerá do LOG do BI. \n Deseja Continuar?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_delete',
            'chave'         => $this->chave,
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function confirm_delete($chave)
    {
        if ($chave === $this->chave) {

            $production = $this->production;

            if ($this->production->delete()) {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Produção removida com sucesso',
                    'timer'    => 2500,
                ]);

                //Verifica se existe alguma analise e remove
                if ($analise = Analise::Where('production_id', $production->id)->first()) {
                    $analise->delete();
                }

                //Verifica se existe algum Wpa e remove
                if ($wpa = Wpa::Where('production_id', $production->id)->first()) {
                    $wpa->update(['production_id' => null]);
                }

                if ($transfer = Prodtransfer::Where('production_id', $production->id)->first()) {
                    $transfer->delete();
                }

                if ($production) {
                    Notetimeline::Create([
                        'note_id'      => $production->id,
                        'service_id'   => $production->service_id,
                        'user_id'      => Auth()->User()->id,
                        'info'         => 'Produção REMOVIDA',
                        'status'       => 2,
                        'productionId' => $production->id,
                    ]);
                }

                //Só exscuta caso o servidor esteja configurado produção.
                if (Env('APP_ENV') === 'production' && !Env('APP_QA')) {
                    if ($sqlLog = SicodeSqlProduction::Where('production_id', $production->id)->first()) {
                        $sqlLog->delete();
                    }
                }

                $this->emit('refresh_list');

            } else {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                    'timer'    => 6000,
                ]);

                $this->emit('refresh_list');
            }
        }
    }

    public function render()
    {
        return view('livewire.production.actions.delete');
    }
}
