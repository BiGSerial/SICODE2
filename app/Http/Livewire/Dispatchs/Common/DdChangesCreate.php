<?php

namespace App\Http\Livewire\Dispatchs\Common;

use App\Helpers\TextFormatter;
use App\Models\Production;
use App\Models\Service;
use App\Models\Wpa;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class DdChangesCreate extends Component
{
    use TextFormatter;

    public $service;
    private $control = false;
    public $dd_text;
    public $ddChanges;

    protected $listeners = [
        'openDdChangesCreateModal',
        'att_dd_massive',
    ];

    public function mount($service, $control = false)
    {
        $this->service = Service::where('uuid', $service)->first();
        $this->control = $control;
    }

    public function openDdChangesCreateModal()
    {
        $this->dd_text = null;
        $this->ddChanges = null;

        $this->dispatchBrowserEvent('showModal', [
                'id' => 'openDdChangesCreateModal',
            ]);
    }

    public function assignDD()
    {
        $this->ddChanges = null;

        $formmated = $this->formatTextToDDArray($this->dd_text);

        if (count($formmated) > 0) {
            $this->ddChanges = collect($formmated);
        }

        if (!$this->ddChanges || $this->ddChanges->isEmpty()) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum DD foi associado. Verifique o formato do texto inserido',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
                'title' =>  'Confirmar Atribuição de DD em Massa',
                'msg' => "Você está prestes a Atribuir {$this->ddChanges->count()} DD(s)",
                'icon' => 'warning',
                'btnOktxt' => 'Sim, Atribua!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => "att_dd_massive",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma DD foi atribuída.',

            ]);


    }

    public function att_dd_massive()
    {
        if (!$this->ddChanges || $this->ddChanges->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum DD foi associado. Verifique o formato do texto inserido',
                'timer'    => 5000,
            ]);
            return;
        }

        $productions = Production::where('service_id', $this->service->uuid)
            ->where('completed', false)
            ->whereHas('Note', function ($q) {
                $q->whereIn('note', $this->ddChanges->pluck('note')->toArray());
            })
            ->with('Note:id,note', 'latestWpa')
            ->get();

        $wpas = Wpa::whereIn('production_id', $productions->pluck('id')->toArray())
            ->get();

        $stats = [
            'criadas' => 0,
            'atualizadas' => 0,
            'ignoradas' => 0,
            'semProd' => 0,
        ];


        foreach ($this->ddChanges as $ddChange) {



            if (!$production = $productions->firstWhere('Note.note', $ddChange['note'])) {

                $stats['semProd']++;
                continue;
            }

            if ($production->latestWpa) {

                if ($production->latestWpa->dd == $ddChange['dd']) {
                    $stats['ignoradas']++;
                    continue;
                }

                $production->latestWpa->dd = $ddChange['dd'];
                $production->latestWpa->service_id = $this->service->uuid;
                $production->latestWpa->sector = null;
                $production->latestWpa->workcenter = null;
                $production->latestWpa->stats = null;
                $production->latestWpa->execstats = null;
                $production->latestWpa->statuscomp = null;
                $production->latestWpa->ststusexec = null;
                $production->latestWpa->lat = null;
                $production->latestWpa->long = null;
                $production->latestWpa->desired_at = null;
                $production->latestWpa->issue_at = null;
                $production->latestWpa->completed_at = null;

                $production->latestWpa->save();
                $stats['atualizadas']++;

            } else {
                Wpa::create([
                    'production_id' => $production->id,
                    'note_id' => $production->note_id,
                    'service_id' => $this->service->uuid,
                    'dd' => $ddChange['dd'],
                ]);
                $stats['criadas']++;
            }

        }

        $this->emitUp('refresh_list');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Processo concluído',
            'html'     => sprintf(
                'Criadas: <b>%d</b><br>Atualizadas (troca de DD na WPA mais recente): <b>%d</b><br>Ignoradas: <b>%d</b><br>Sem produção atual: <b>%d</b>',
                $stats['criadas'],
                $stats['atualizadas'],
                $stats['ignoradas'],
                $stats['semProd']
            ),
            'timer'    => 7000,
        ]);

    }





    public function render()
    {
        return view('livewire.dispatchs.common.dd-changes-create');
    }
}
