<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\Category;
use App\Models\External;
use App\Models\Production;
use App\Models\Service;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InterReturn extends Component
{
    public $observations;

    public $serviceId;
    public $categories;
    public $subcategories;
    public $categorySelected;
    public $subcategorySelected;
    public $services;
    public $serviceSelected;
    public $production;

    public $external;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openInternReturn',
        'confirm_inter_return',
    ];

    protected $messages = [
        'observations.required' => 'O Campo de Descrição é Requerido.',
        'serviceSelected.required' => 'O Campo Serviço para Retorno é Requerido.',
        'subcategorySelected.required' => 'O campo Sub-categoria é requerido.',
    ];

    public function mount()
    {
        $this->serviceId = request()->route('service');
        $this->categories = Category::orderBy('name')->get();
        $this->services = Service::where('canReturn', true)->orderBy('service')->get();

    }

    public function updatedServiceSelected($value)
    {
        $this->production = Production::where('service_id', $value)->where('note_id', $this->external->note_id)->where('completed', true)->with('service', 'user')->orderBy('id', 'DESC')->first();
    }

    public function openInternReturn(External $external)
    {
        $this->external = $external;

        if ($this->external) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modalInterReturn',
            ]);
        }
    }

    public function updatedCategorySelected($value)
    {

        $this->subcategories = Subcategory::where('category_id', $value)->orderBy('name')->get();
    }

    public function closeAll()
    {
        $this->reset([
            'observations',
            'serviceSelected',
            'subcategorySelected',
            'categorySelected',
        ]);
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refreshComponent');
    }

    public function sendInterReturn()
    {
        $this->validate([
            'observations' => 'required',
            'serviceSelected' => 'required',
            'subcategorySelected' => 'required',
        ]);

        $this->dispatchBrowserEvent('alertar', [
               'title'         => "Retorno Interno",
               'msg'           => "Você deseja realmente enviar uma atividade interna?",
               'icon'          => 'warning',
               'btnOktxt'      => 'Sim, Enviar!',
               'btnCanceltxt'  => 'Não, Cancelar',
               'action'        => 'confirm_inter_return',
               'cancel_titulo' => 'Cancelado!',
               'cancel_msg'    => 'Nenhuma NOTA/OV foi encerrada!',

           ]);
    }

    public function confirm_inter_return()
    {


        DB::beginTransaction();

        $this->external->update([
            'status' => 1,
        ]);

        try {

            $reclaim = $this->external->Reclaims()->create([
                'note_id' => $this->external->note_id,
                'service_id' => $this->serviceSelected,
                'subcategory_id' => $this->subcategorySelected,
                'category' => Subcategory::find($this->subcategorySelected)->Category->name,
            ]);

            if ($reclaim) {
                $this->external->Comments()->create([
                    'user_id'     => auth()->user()->id,
                    'title'       => 'RETORNO INTERNO',
                    'comment'     => $this->observations,
                ]);

                $this->external->Reclaims()->attach($reclaim->id);

                $reclaim->Comments()->create([
                    'user_id'     => auth()->user()->id,

                    'message'     => $this->observations,
                ]);


                if ($this->production && !$this->production->User->trashed()) {
                    $production = Production::create([
                        'service_id' => $this->serviceSelected,
                        'note_id' => $this->external->note_id,
                        'user_id' => $this->production->user_id,
                        'company_id' => $this->production->User->company_id,
                        'dispatch_by' => auth()->user()->id,
                        'att_by' => auth()->user()->id,
                        'dispatch_at' => now(),
                        'att_at' => now(),
                        'dt_note' => $this->external->note->dt_note,
                        'dhstats' => $this->external->note->dt_note,
                        'd5' => true,
                        'status' => 2,
                    ]);

                    if ($production) {
                        $reclaim->update([
                            'production_id' => $production->id,
                        ]);
                    }
                }

            }


        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao criar protocolo.',
                'html'      => $e->getMessage(),
                'timer'    => 5000,
            ]);

            return;
        }


        DB::commit();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Retorno Interno enviado com sucesso.',
            'html'      => 'Retorno Interno enviado com sucesso.',
            'timer'    => 5000,
        ]);

        $this->closeAll();
        $this->emitUp('refreshComponent');
    }


    public function render()
    {
        return view('livewire.services.oexterno.actions.inter-return', [
            'categories_s' => $this->categories,
            'subcategories_s' => $this->subcategories,
        ]);
    }
}
