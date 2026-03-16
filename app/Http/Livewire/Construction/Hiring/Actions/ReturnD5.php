<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Reclaim;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnD5 extends Component
{
    public $list;
    public $services;
    public $service_s;
    public $comment;

    public function mount($list)
    {
        $this->list = $list;
        $this->services = Service::orderBy('service')->get();
    }

    public function returnD5()
    {



        if (!trim($this->comment)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'O Comentário é Obrigatório.',
                'timer'    => 5000,
            ]);

            return;
        }

        if (!$this->service_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'O serviço de devolução é Obrigatório.',
                'timer'    => 5000,
            ]);

            return;
        }

        DB::beginTransaction();

        try {
            if (Reclaim::hasActiveForService($this->list->id, $this->service_s)) {
                DB::rollBack();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'RECLAIM JÁ EM ANDAMENTO',
                    'html'     => 'Já existe retorno interno ativo para esta obra e serviço.',
                    'timer'    => 5000,
                ]);

                return;
            }

            $return = Reclaim::create([
                'note_id' => $this->list->id,
                'service_id' => $this->service_s,
            ]);



            $return->Comments()->create([
                'user_id' => Auth()->User()->id,
                'message' => $this->comment
            ]);

            if ($return && $this->list->Viabilities->count()) {
                foreach ($this->list->Viabilities as $viab) {
                    // dd($viab);
                    $viab->update([
                        'status' => 11
                    ]);

                    $viab->Reclaims()->attach($return->id);
                }
            } else {
                DB::rollback();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Ocorreu um erro individual. tente novamente.',
                    'timer'    => 8000,
                ]);

                return;
            }

            DB::commit();

            $this->emitUp('update_list');

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Ocorreu um erro. tente novamente.',
                'html'      => $th->getMessage(),
                'timer'    => 8000,
            ]);

            $this->emitUp('update_list');

            return;
        }
    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.return-d5', [
            'services' => $this->services
        ]);
    }
}
