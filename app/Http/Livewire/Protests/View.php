<?php

namespace App\Http\Livewire\Protests;

use App\Models\MedProtest;
use Livewire\Component;

class View extends Component
{
    public $medProtest;
    public $comment;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    protected $messages = [
        'comment.required' => 'O comentário é obrigatório.',
        'comment.string' => 'O comentário deve ser uma string.',
        'comment.min' => 'O comentário deve ter pelo menos 10 caracteres.',
    ];

    public function mount($medProtestId)
    {
        $this->medProtest = MedProtest::with([
            'Protest',
            'Comments.User',
            'Notes',
            'Assignments.User'
        ])->findOrFail($medProtestId);

        if (!$this->medProtest) {
            abort(404, 'Medida de Reclamação não encontrada');
        }
    }

    public function addComment()
    {
        $this->validate([
            'comment' => 'required|string|min:10',
        ]);

        $this->medProtest->Comments()->create([
            'user_id' => auth()->id(),
            'message' => $this->comment,
        ]);

        $this->comment = '';
        $this->emit('refreshComponent');
    }

    public function removeComment($commentId)
    {
        $comment = $this->medProtest->Comments()->findOrFail($commentId);

        if ($comment->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para remover este comentário.');
        }

        $comment->delete();
        $this->emit('refreshComponent');
    }

    public function render()
    {
        return view('livewire.protests.view', [
            'medProtest' => $this->medProtest,
        ]);
    }
}
