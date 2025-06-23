<?php

namespace App\Http\Livewire\Services\Oexterno\Protests;

use App\Models\Comment;
use App\Models\Protest;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $protest;
    public $comment;
    public $deleteCommentId;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'removeComment172030' => 'removeComment',
    ];

    public function mount(Request $request)
    {
        $this->protest = Protest::where('nota', $request->route('protest'))
        ->with([
            'medProtests',
            'comments' => function($q){
                $q->orderBy('created_at', 'DESC');
            }
        ])->first();

        if (!$this->protest) {
            abort(404, 'Protesto não encontrado');
        }

    }

    public function removeNoteFromProtest($id)
    {
        if ($id) {
            $this->protest->Notes()->detach($id);
            $this->emit('refreshComponent');
        }
    }

    public function addComment()
    {
        if (trim($this->comment) === '') {
            session()->flash('error', 'O comentário não pode estar vazio.');
            return;
        }

        try {

            $this->protest->Comments()->create([
                'message' => $this->comment,
                'user_id' => auth()->id(),
            ]);

            $this->comment = '';
            $this->emit('refreshComponent');

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Comentário adicionado com sucesso!',
            ]);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('torrada', [
               'status'   => 'danger',
               'menssage' => 'Ooops.... ocorreu um erro ao adicionar o comentário: ',
            ]);
        }

    }

    public function removeComment()
    {
        if ($this->deleteCommentId) {
            $this->deleteCommentId->delete();
            $this->deleteCommentId = null;

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Comentário removido com sucesso!',
            ]);

            $this->emit('refreshComponent');
        }
    }


    public function deleteComment(Comment $comment)
    {
        $this->deleteCommentId = $comment;

        if ($this->deleteCommentId) {

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Remover Comentário?',
                // 'msg'   => "
                // Você deseja atribuir a NOTA/OV para você?</br></br>
                // <div class='card card-light'>
                // <div class='card-body'>
                // <p><strong>NOTA/OV estará disponível em acompanhamento como
                // sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
                // </div>
                // </div>
                // ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remover!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'removeComment172030',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum comentário Removido.',

            ]);
        }
    }

    public function render()
    {
        return view('livewire.services.oexterno.protests.view');
    }
}
