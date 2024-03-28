<?php

namespace App\Http\Livewire\Engineer\Actions;

use App\Models\Comment;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Approveaction extends Component
{
    public $list;
    public $comment;
    public $restrict;

    protected $listeners = [
        'toApprove' => 'toAprove',
        'toDisaproved' => 'toDisaproved',
    ];


    public function approved()
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
                        'approved' => true,
                        'engineer' => true,
                        'engineer_at' => now(),
                        'status' => 5,
                    ]);

                    // Crie um novo comentário e associe-o à viabilidade
                    $viability->Comments()->create([
                        'user_id' => auth()->user()->id,
                        'message' => $this->comment ?: null,
                        'restrict' => $this->restrict ? true : false,
                    ]);

                    DB::commit();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Confirmação Improcedente',
                        'html'      => 'A Inviabilidade Técnica foi dada como Improcedente com sucesso.',
                        'timer'    => 5000,
                    ]);

                    $this->emitUp('update_list');

                } catch (\Throwable $th) {
                    DB::rollback();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'danger',
                        'title'    => 'Confirmação Improcedente',
                        'html'      => 'A Inviabilidade Técnica como Improcedente, nao foi executada por algum problema. Nenhuma alteração foi realiazada..',
                        'timer'    => 5000,
                    ]);

                }
            }
        }
    }

    public function desapproved()
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
                        'status' => 10,
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
                        'title'    => 'Confirmação Procedente',
                        'html'      => 'A Inviabilidade Técnica foi dada como Procedente.',
                        'timer'    => 5000,
                    ]);

                    $this->emitUp('update_list');

                } catch (\Throwable $th) {
                    DB::rollback();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'danger',
                        'title'    => 'Confirmação Procedente',
                        'html'      => 'A Inviabilidade Técnica como Procedente, nao foi executada por algum problema no sistema. Nenhuma alteração foi realiazada..',
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
