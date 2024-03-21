<?php

namespace App\Http\Livewire\Monitor\Services;

use App\Models\Edp_depc\{BaseEP, BaseOV};
use App\Models\{Manualconfirm, Production};
use Carbon\Carbon;
use Livewire\{Component, WithPagination};

class Inconsistencylist extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $historics;

    public $titleModal = 'TITLE';

    public $date_complete;

    public $info;

    public $registro = [];

    public $production;

    public $user_s;

    public $service_s;

    public $company_s;

    public $action;

    public $perPage = 50;

    protected $listeners = [
        'refreshInconsistency' => '$refresh',
        'searchUser'           => 'selUSer',
        'searchService'        => 'selService',
        'searchCompany'        => 'selCompany',
    ];

    public function selUSer($user)
    {
        $this->user_s = $user;
    }

    public function selService($service)
    {
        $this->service_s = $service;
    }

    public function selCompany($company)
    {
        $this->company_s = $company;
    }

    public function getListProperty()
    {
        return Production::Where('completed', true)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            })
            ->when($this->service_s, function ($q) {
                return $q->where('service_id', $this->service_s);
            })
            ->when($this->company_s, function ($q) {
                return $q->where('company_id', $this->company_s);
            })
            ->where('confirmed', false)->where('tries', '>', 2)
            ->whereDate('completed_at', '<', Carbon::now());
    }

    public function historicsql(string $ov, $prod)
    {
        unset($this->historics);

        $this->date_complete = Production::with('User', 'Note', 'Service')->find($prod);

        if ($this->date_complete->Note->type_note == 2) {
            $this->historics = BaseOV::where('OV', $ov)->orderBy('dhStat', 'DESC')->get();

        } else {
            $this->historics = BaseEP::where('nota', $ov)->orderBy('dtNota', 'DESC')->get();
        }

        if ($this->historics) {
            $this->titleModal = $ov;

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'historicnotes',
            ]);
        }
    }

    public function reject($id)
    {
        $this->registro   = [];
        $this->production = Production::find($id);
        $this->registro[] = ['rejected' => true, 'confirmed' => true, 'confirmed_at' => date('Y-m-d H:i:s')];

        $this->titleModal = 'REJEITAR PRODUÇÃO';
        $this->action     = 'REJEITAR';
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'actionConfirm',
        ]);
    }

    public function confirm_prod($id)
    {
        $this->registro   = [];
        $this->production = Production::find($id);
        $this->registro[] = ['conf_manual' => true, 'confirmed' => true, 'confirmed_at' => date('Y-m-d H:i:s')];

        $this->titleModal = 'APROVAR PRODUÇÃO';
        $this->action     = 'APROVAR';
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'actionConfirm',
        ]);
    }

    public function confirm()
    {
        if (strlen(trim($this->info)) < 3) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'A Informação do Motivo é Obrigatório',
                'timer'    => 8000,
            ]);

            return;
        }

        try {
            $this->production->update($this->registro[0]);
            Manualconfirm::create([
                'user_id'       => Auth()->User()->id,
                'production_id' => $this->production->id,
                'info'          => $this->info,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => $this->titleModal . ' Confirmado',
                'timer'    => 2500,
            ]);

            $this->closemodal();

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOPS! Ocorreu algum erro ao liberar manualmente',
                'timer'    => 8000,
            ]);

            if (env('APP_DEBUG')) {
                $this->dispatchBrowserEvent('torrada', [
                    'status'   => 'wawrning',
                    'menssage' => $th->getMessage(),
                ]);
            }
        }

    }

    public function closemodal()
    {
        $this->historics  = '';
        $this->production = '';
        $this->registro   = [];
        $this->info       = '';
        $this->emit('refreshInconsistency');

    }

    public function render()
    {
        return view('livewire.monitor.services.inconsistencylist', [
            'lists' => $this->list->with('Note', 'Service', 'User', 'Analise')->orderBy('completed_at')->paginate($this->perPage),
        ]);
    }
}
