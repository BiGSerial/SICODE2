<?php

namespace App\Http\Livewire\Services\Oexterno\Protests;

use App\Models\Comment;
use App\Models\EvidenceFile;
use App\Models\MedProtest;
use App\Models\Noteable;
use App\Models\Protest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public ?Protest $protest = null;
    public $comment;
    public $deleteCommentId;
    public $files;
    public $protestTemp;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'removeComment172030' => 'removeComment',
        'FinishMedProtest172030' => 'finishMedProtes',
    ];

    public function mount(Request $request)
    {
        $this->protest = Protest::where('nota', $request->route('protest'))
        ->with([
            'medProtests.notes',
            'medProtests.assignments',
            'comments' => function ($q) {
                $q->orderBy('created_at', 'DESC');
            },
            'evidenceFiles'
        ])->first();

        if (!$this->protest) {
            abort(404, 'Protesto não encontrado');
        }

    }

    public function dowloadFile(EvidenceFile $file)
    {
        // dd(Storage::fileExists('public/'.$file->path));

        if (Storage::fileExists('public/'.$file->path)) {
            return Storage::download('public/'.$file->path);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO INEXISTENTE!',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function deleteFile(EvidenceFile $file)
    {
        if ($file) {
            $file->delete();
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Arquivo removido com sucesso!',
            ]);
            $this->emit('refreshComponent');
        }
    }

    public function removeNoteFromProtest($id)
    {
        if ($id) {
            $noteRelation = Noteable::find($id);


            if ($noteRelation) {
                $noteRelation->delete();
            } else {
                $this->dispatchBrowserEvent('torrada', [
                    'status'   => 'danger',
                    'menssage' => 'Associação de nota não encontrada.',
                ]);
                return;
            }

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Nota removida do protesto com sucesso!',
            ]);

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

    public function approveMed(MedProtest $protestTemp)
    {

        // dd($protestTemp);

        if ($protestTemp) {
            $this->protestTemp = $protestTemp;

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Deseja Encerrar a Medida?',
                'msg'   => "
                Você está preste de encerrar a medida?
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Encerrar!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'FinishMedProtest172030',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum comentário Removido.',

            ]);
        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Medida não encontrada.',
            ]);
        }
    }

    public function finishMedProtes()
    {
        if ($this->protestTemp) {

            $this->protestTemp->update([
                'completed' => true,
                'completed_at' => now(),
            ]);

            $this->protestTemp->Assignments()->where('completed', false)->update([
                'completed' => true,
                'ended_at' => now(),
            ]);

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Medida encerrada com sucesso!',
            ]);

            $this->emit('refreshComponent');

        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Medida não encontrada.',
            ]);
        }
    }

    public function rejectMed($medProtestId)
    {
        $medProtest = $this->protest->MedProtests()->find($medProtestId);

        if ($medProtest) {
            $medProtest->update(['completed' => false, 'completed_at' => null]);

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Medida rejeitada com sucesso!',
            ]);

            $this->emit('refreshComponent');
        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Medida não encontrada.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.services.oexterno.protests.view');
    }
}
