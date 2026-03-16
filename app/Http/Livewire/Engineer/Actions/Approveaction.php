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
    public $category;



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

        if (!$this->service_s || !$this->category) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informações Faltantes',
                'html'      => 'O SERVIÇO para RETORNO e a CATEGORIA (Motivo) precisam ser selecionadas. Revise e tente novamente.',
                'timer'    => 5000,
            ]);

            return;

        }

        if ($this->list->Viabilities->count()) {



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
                            'category' => $this->category,
                        ]);

                        $return->Comments()->create([
                            'user_id' => Auth()->User()->id,
                            'message' => $this->comment
                        ]);

                        if ($return && $this->list->Viabilities->count()) {
                            $block = false;
                            $commentId = "";
                            foreach ($this->list->Viabilities as $viab) {
                                // dd($viab);
                                $viab->update([
                                    'status' => 12,
                                    'engineer' => true,
                                    'engineer_at' => date('Y-m-d H:i:s'),
                                ]);

                                if (!$block) {
                                    $viab->Reclaims()->attach($return->id);
                                    $commentId = $viab->Comments()->create([
                                        'user_id' => auth()->user()->id,
                                        'message' => '>> Responsável informou em conformidade com a viabilidade. <<',

                                    ]);

                                    $block = true;
                                } else {
                                    $viab->Reclaims()->attach($return->id);
                                    $viab->Comments()->attach($commentId);
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

                        $block = false;
                        $commentId = "";

                        foreach ($this->list->Viabilities as $viab) {
                            // dd($viab);
                            $viab->update([
                                'status' => 11,
                                'engineer' => true,
                                'engineer_at' => date('Y-m-d H:i:s'),
                            ]);

                            if (!$block) {
                                $viab->Reclaims()->attach($return->id);
                                $commentId = $viab->Comments()->create([
                                    'user_id' => auth()->user()->id,
                                    'message' => '>> Responsável informou em conformidade com a viabilidade. <<',

                                ]);

                                $block = true;
                            } else {
                                $viab->Reclaims()->attach($return->id);
                                $viab->Comments()->attach($commentId);
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

            DB::beginTransaction();

            try {

                $block = false;
                $commentId = "";

                foreach ($this->list->Viabilities as $viab) {

                    // Atualize a viabilidade
                    $viab->update([
                        'approved' => false,
                        'engineer' => true,
                        'engineer_at' => now(),
                        'replica' => true,
                        'status' => 5,
                    ]);



                    if (!$block) {

                        // Crie um novo comentário e associe-o à viabilidade
                        $commentId = $viab->Comments()->create([
                        'user_id' => auth()->user()->id,
                        'message' => $this->comment ?? null,
                        'restrict' => $this->restrict ? true : false,
                    ]);

                        $block = true;
                    } else {

                        $viab->Comments()->attach($commentId);
                    }

                }

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



    public function render()
    {
        return view('livewire.engineer.actions.approveaction');
    }
}
