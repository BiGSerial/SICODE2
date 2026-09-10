<?php

namespace App\Http\Livewire\Components\Modal;

use App\Models\{Priority as ModelsPriority, Production};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Priority extends Component
{
    public $priority;

    public $productions = null;

    public $priorityInfo;

    protected $listeners = [
        'setPriority'           => 'setPriority',
        'confirmPriority'       => 'confirmPriority',
        'removePriority'        => 'removePriority',
        'confirmRemovePriority' => 'confirmRemovePriority',
        'infoPriority'          => 'showPriorityInfo',
    ];

    public function setPriority($production)
    {

        if (!is_array($production)) {
            $production = [$production];
        }

        if ($production) {
            $this->productions = Production::find($production);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'priorityModal',
            ]);
        }
    }

    public function givePriority()
    {
        if (!trim($this->priority)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'A informação da prioridade é obrigatório.',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->productions) {

            if ($this->productions->count() > 1) {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Prioridade',
                    'msg'           => "Deseja confirmar prioridade para as {$this->productions->count()} Notas/OVs?",
                    'icon'          => 'warning',
                    'btnOktxt'      => 'Sim, Priorize!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirmPriority',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhum nota/ov foi priorizada.',

                ]);
            } else {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Prioridade',
                    'msg'           => "Deseja confirmar prioridade para Nota/OV {$this->productions[0]->load('Note')->Note->note}",                'icon' => 'warning',
                    'btnOktxt'      => 'Sim, Priorize!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirmPriority',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhum nota/ov foi priorizada.',

                ]);
            }
        }
    }

    public function confirmPriority()
    {
        if ($this->productions) {

            $erro = false;

            foreach ($this->productions as $production) {

                DB::beginTransaction();

                $priority = ModelsPriority::Create([
                    'production_id' => $production->id,
                    'note_id'       => $production->note_id,
                    'user_id'       => Auth()->User()->id,
                    'service_id'    => $production->service_id,
                    'prioridade'    => $this->priority,
                ]);

                if ($priority) {
                    $production->update(['priority' => true]);

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Prioridade adicionada com sucesso.',
                        'timer'    => 2500,
                    ]);

                    DB::commit();

                } else {
                    $erro = true;
                    DB::rollback();
                }
            }

            if (!$erro) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Prioridade(s) adicionada(s) com sucesso.',
                    'timer'    => 2500,
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Prioridade(s) adicionada(s) com erros, algumas podem nao ter sido priorizadas, confirme novamente e tente novamente.',
                    'timer'    => 8000,
                ]);
            }

            $this->productions = null;
            $this->dispatchBrowserEvent('hideModal');
            $this->emit('closeall');
            // $this->emit('refresh_list');

        }
    }

    public function removePriority($production)
    {
        if (!is_array($production)) {
            $production = [$production];
        }

        if ($production) {

            $this->productions = Production::find($production);

            if ($this->productions->count() > 1) {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Remover Prioridade',
                    'msg'           => "Deseja remover prioridade das {$this->productions->count()} Notas/OVs?",
                    'icon'          => 'warning',
                    'btnOktxt'      => 'Sim, Remova!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirmRemovePriority',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhum nota/ov foi removido a prioridade.',

                ]);
            } else {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Remover Prioridade',
                    'msg'           => "Deseja remover prioridade para Nota/OV {$this->productions[0]->load('Note')->Note->note}",
                    'icon'          => 'warning',
                    'btnOktxt'      => 'Sim, Remova!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirmRemovePriority',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhum nota/ov foi removido a prioridade.',

                ]);
            }
        }
    }

    public function confirmRemovePriority()
    {
        if ($this->productions) {

            $erro = false;

            foreach ($this->productions as $production) {
                if (!$production->update(['priority' => false])) {
                    $erro = true;
                }
            }

            if (!$erro) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Prioridade(s) removida(s) com sucesso.',
                    'timer'    => 2500,
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Prioridade(s) removida(s) com com erros. Verifique e tente novamente',
                    'timer'    => 8000,
                ]);
            }

            $this->priority = null;

            // $this->dispatchBrowserEvent('hideModal');

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'danger',
                'title'    => 'Não foi conseguimos remover prioridades a note/ov, tente novamente,',
                'timer'    => 2500,
            ]);
        }

        $this->emit('closeall');
        // $this->emit('refresh_list');

    }

    public function showPriorityInfo($production)
    {
        $productionModel = Production::query()
            ->select(['id', 'note_id', 'service_id'])
            ->find($production);

        $this->priorityInfo = ModelsPriority::query()
            ->with(['Note:id,note', 'User:id,name'])
            ->where('production_id', $production)
            ->latest('id')
            ->first();

        if (!$this->priorityInfo && $productionModel) {
            $this->priorityInfo = ModelsPriority::query()
                ->with(['Note:id,note', 'User:id,name'])
                ->where('note_id', $productionModel->note_id)
                ->where('service_id', $productionModel->service_id)
                ->latest('id')
                ->first();
        }

        if ($this->priorityInfo) {
            $this->dispatchBrowserEvent('priorityInfoLoaded', [
                'id' => 'infoPrioridade',
                'note' => $this->priorityInfo->Note?->note ?? '',
                'reason' => $this->priorityInfo->prioridade ?: '---',
                'user' => $this->priorityInfo->User?->name ?: '---',
                'created_at' => $this->priorityInfo->created_at
                    ? $this->priorityInfo->created_at->format('d/m/Y H:i:s')
                    : '--:--',
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Não encontramos nenhuma informação sobre essa prioridade.',
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.components.modal.priority');
    }
}
