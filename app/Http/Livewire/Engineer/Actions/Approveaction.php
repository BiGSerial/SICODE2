<?php

namespace App\Http\Livewire\Engineer\Actions;

use App\Models\Comment;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Approveaction extends Component
{
    public $list;
    public $comment;
    public $restrict;
    public $services;
    public $service_s;
    public $lastUser;
    public $blkResponse;
    public $blkReturn;
    public $newReturn = false;



    public function mount()
    {
        $this->services = Service::orderBy('service')->get();
    }

    public function updatedServiceS()
    {
        $this->lastUser = Production::Where('service_id', $this->service_s)->where('note_id', $this->list->id)->where('completed', true)->with('Service')->get()->last();
    }

    public function newReturn($value)
    {
        $this->newReturn = $value;
    }

    public function agree()
    {
        if (strlen(trim($this->comment)) <= 5) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Comentário Necessário',
                'html'      => 'As informações adicionais são necessárias para uma conclusão mais apurada e futuras referências.',
                'timer'    => 5000,
            ]);

            return;

        }

        if ($this->list->Viabilities->count()) {



            DB::beginTransaction();

            try {

                if ($this->lastUser) {

                    $production = Production::Create([
                        'note_id' => $this->list->id,
                        'service_id' => $this->service_s,
                        'company_id' => User::find($this->lastUser->user_id)->Employee->Contract->company->id ?? null,
                        'user_id' => $this->lastUser->user_id,
                        'att_by' => Auth()->User()->id,
                        'dispatch_by' => Auth()->User()->id,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'att_at' => date('Y-m-d H:i:s'),
                        'dt_note' => $this->list->dt_status,
                        'status_note' => $this->list->nstats,
                        'status' => 2,
                        'd5' => true,
                    ]);

                    if ($production) {

                        $return = Reclaim::create([
                            'note_id' => $this->list->id,
                            'service_id' => $this->service_s,
                            'production_id' => $production->id,
                        ]);

                        $return->Comments()->create([
                            'user_id' => Auth()->User()->id,
                            'message' => $this->comment
                        ]);

                        if ($return && $this->list->Viabilities->count()) {
                            $block = false;
                            foreach ($this->list->Viabilities as $viab) {
                                // dd($viab);
                                $viab->update([
                                    'status' => 12,
                                    'engineer' => true,
                                    'engineer_at' => date('Y-m-d H:i:s'),
                                ]);

                                if (!$block) {
                                    $viab->Reclaims()->attach($return->id);
                                    $viab->Comments()->create([
                                        'user_id' => auth()->user()->id,
                                        'message' => 'Responsável informou em conformidade com a viabilidade.',

                                    ]);

                                    $block = true;
                                }


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



                    }


                } else {

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
                }

                DB::commit();

                // Send refresh command to 'main' page to update..
                $this->emitUp('update_list');

            } catch (\Throwable $th) {
                DB::rollback();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Ocorreu uma Falha e Nao conseguimos registrar.',
                    'timer'    => 8000,
                ]);

                return;
            }






            // foreach ($this->list->Viabilities as $viability) {


            //     try {
            //         // Atualize a viabilidade
            //         $viability->update([
            //             'engineer_at' => now(),
            //             'status' => 10,
            //         ]);

            //         // Crie um novo comentário e associe-o à viabilidade
            //         $viability->Comments()->create([
            //             'user_id' => auth()->user()->id,
            //             'message' => $this->comment ?? null,
            //             'restrict' => $this->restrict ? true : false,
            //         ]);



            //         DB::commit();

            //         $this->dispatchBrowserEvent('swal', [
            //             'position' => 'center',
            //             'icon'     => 'success',
            //             'title'    => 'Confirmação Improcedente',
            //             'html'      => 'A Inviabilidade Técnica foi dada como Improcedente com sucesso.',
            //             'timer'    => 5000,
            //         ]);

            //         $this->emitUp('update_list');

            //     } catch (\Throwable $th) {
            //         DB::rollback();

            //         $this->dispatchBrowserEvent('swal', [
            //             'position' => 'center',
            //             'icon'     => 'danger',
            //             'title'    => 'Confirmação Improcedente',
            //             'html'      => 'A Inviabilidade Técnica como Improcedente, nao foi executada por algum problema. Nenhuma alteração foi realiazada..',
            //             'timer'    => 5000,
            //         ]);

            //     }
            // }
        }
    }

    public function desagree()
    {
        if (strlen(trim($this->comment)) <= 5) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Comentário Necessário',
                'html'      => 'As informações adicionais são necessárias para uma conclusão mais apurada e futuras referências.',
                'timer'    => 5000,
            ]);

            return;

        }

        if ($this->list->Viabilities->count()) {
            foreach ($this->list->Viabilities as $viability) {
                DB::beginTransaction();

                try {
                    // Atualize a viabilidade
                    $viability->update([
                        'approved' => false,
                        'engineer' => true,
                        'engineer_at' => now(),
                        'replica' => true,
                        'status' => 5,
                    ]);

                    // Crie um novo comentário e associe-o à viabilidade
                    $viability->Comments()->create([
                        'user_id' => auth()->user()->id,
                        'message' => $this->comment ?? null,
                        'restrict' => $this->restrict ? true : false,
                    ]);

                    DB::commit();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Contestação Rejeitado',
                        'html'      => 'Foi Contestado junto a pareceira o parecer da viabilidade.',
                        'timer'    => 5000,
                    ]);

                    $this->emitUp('update_list');

                } catch (\Throwable $th) {
                    DB::rollback();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'danger',
                        'title'    => 'Erro',
                        'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realiazada..',
                        'timer'    => 5000,
                    ]);

                }
            }
        }
    }



    public function render()
    {
        return view('livewire.engineer.actions.approveaction');
    }
}
