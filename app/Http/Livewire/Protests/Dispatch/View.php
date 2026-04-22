<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Helpers\SelectOptions;
use App\Models\Comment;
use App\Models\EvidenceFile;
use App\Models\MedProtest;
use App\Models\Noteable;
use App\Models\Protest;
use App\Models\ProtestJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class View extends Component
{
    /** ===== PROPRIEDADES PRINCIPAIS ===== */
    public ?Protest $protest = null;
    public bool $readOnly = false;

    // Comentários gerais do Protest
    public ?string $comment = null;
    public ?Comment $deleteCommentId = null;

    // Editar resumo da reclamação
    public ?string $resumeEdit = null;
    public bool $showResumeEdit = false;
    public ?string $typeEdit = null;
    public bool $showTypeEdit = false;

    // Estado de expansão de jobs por MedProtest
    public array $expandedJobs = [];

    // Buffers de ação
    public ?MedProtest $protestTemp = null;
    public ?MedProtest $medProtest = null;
    public ?ProtestJob $jobTemp = null;
    public ?string $result = null;

    /** ===== LISTENERS ===== */
    protected $listeners = [
        'refreshComponent'      => '$refresh',
        'removeComment172030'   => 'removeComment',
        'FinishMedProtest172030' => 'finishMedProtes',
        'Reject172030'          => 'rejectMed',
        'confirmJob172030'      => 'confirmJob',
        'cancelJob172030'       => 'cancelJob',
        'reopenJob172030'       => 'reopenJob',
    ];

    /** ===== LIFECYCLE ===== */
    public function mount(Request $request, bool $readOnly = false): void
    {
        $this->readOnly = $readOnly;

        $routeRef = (string) $request->route('protest');

        $protestQuery = Protest::query();
        if ($routeRef !== '' && ctype_digit($routeRef)) {
            $med = MedProtest::query()
                ->select('id', 'protest_id')
                ->find((int) $routeRef);

            if ($med?->protest_id) {
                $protestQuery->whereKey($med->protest_id);
            } else {
                $protestQuery->where('nota', $routeRef);
            }
        } else {
            $protestQuery->where('nota', $routeRef);
        }

        $this->protest = $protestQuery
            ->with([
                'medProtests',
                'medProtests.notes',
                'medProtests.LastProtestJob',
                'medProtests.ProtestJobs',
                'comments' => fn ($q) => $q->orderByDesc('created_at'),
                'evidenceFiles',
            ])
            ->first();

        if (!$this->protest) {
            abort(404, 'Protesto não encontrado');
        }
    }

    /** ===== HELPERS ===== */
    protected function toast(string $status, string $message): void
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => $status,
            'menssage' => $message,
        ]);
    }

    /** =======================================================================
     *  ARQUIVOS / EVIDÊNCIAS
     *  ======================================================================= */

    public function downloadFiles(EvidenceFile $file)
    {
        try {
            $path = 'public/' . $file->path;

            if (!Storage::fileExists($path)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);
                return;
            }

            return Storage::download($path);
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao baixar arquivo: ' . $e->getMessage());
        }
    }

    public function deleteFile(EvidenceFile $file): void
    {
        if ($this->readOnly) {
            return;
        }

        try {
            $file->delete();
            $this->toast('success', 'Arquivo removido com sucesso!');
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao remover arquivo: ' . $e->getMessage());
        }
    }

    /** =======================================================================
     *  NOTAS ASSOCIADAS
     *  ======================================================================= */

    public function removeNoteFromProtest(int $id): void
    {
        if ($this->readOnly) {
            return;
        }

        try {
            $noteRelation = Noteable::find($id);

            if (!$noteRelation) {
                $this->toast('danger', 'Associação de nota não encontrada.');
                return;
            }

            $noteRelation->delete();

            $this->toast('success', 'Nota removida do protesto com sucesso!');
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao remover nota: ' . $e->getMessage());
        }
    }

    /** =======================================================================
     *  COMENTÁRIOS DO PROTEST
     *  ======================================================================= */

    public function addComment(): void
    {
        if ($this->readOnly) {
            return;
        }

        if (trim((string) $this->comment) === '') {
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
            $this->toast('success', 'Comentário adicionado com sucesso!');
        } catch (\Throwable $th) {
            $this->toast('danger', 'Ooops.... ocorreu um erro ao adicionar o comentário: ' . $th->getMessage());
        }
    }

    public function deleteComment(Comment $comment): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->deleteCommentId = $comment;

        if (!$this->deleteCommentId) {
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Comentário?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remover!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'removeComment172030',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum comentário removido.',
        ]);
    }

    public function removeComment(): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!$this->deleteCommentId) {
            return;
        }

        try {
            $this->deleteCommentId->delete();
            $this->deleteCommentId = null;

            $this->toast('success', 'Comentário removido com sucesso!');
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao remover comentário: ' . $e->getMessage());
        }
    }

    /** =======================================================================
     *  RESUMO / DESCRIÇÃO DA RECLAMAÇÃO
     *  ======================================================================= */

    public function editResume(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->resumeEdit     = $this->protest->resume;
        $this->showResumeEdit = true;
    }

    public function saveResume(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->validate([
            'resumeEdit' => 'required|string',
        ]);

        try {
            $this->protest->resume = $this->resumeEdit;
            $this->protest->save();

            $this->showResumeEdit = false;
            $this->toast('success', 'Resumo atualizado com sucesso!');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao salvar resumo: ' . $e->getMessage());
        }
    }

    public function editType(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->typeEdit     = $this->protest->type;
        $this->showTypeEdit = true;
    }

    public function saveType(): void
    {
        if ($this->readOnly) {
            return;
        }

        $allowedTypes = $this->allowedProtestTypeValues();

        $this->validate([
            'typeEdit' => 'required|in:' . implode(',', $allowedTypes),
        ]);

        try {
            $this->protest->type = $this->typeEdit;
            $this->protest->save();

            $this->showTypeEdit = false;
            $this->toast('success', 'Tipo atualizado com sucesso!');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao salvar tipo: ' . $e->getMessage());
        }
    }

    protected function allowedProtestTypeValues(): array
    {
        return array_map(
            static fn ($option) => $option->value,
            SelectOptions::getProtestCategory()
        );
    }

    /** =======================================================================
     *  MEDIDAS (MedProtest)
     *  ======================================================================= */

    public function approveMed(MedProtest $protestTemp): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->protestTemp = $protestTemp;

        if (!$this->protestTemp) {
            $this->toast('danger', 'Medida não encontrada.');
            return;
        }

        $options = [];
        foreach (MedProtest::resultOptions() as $opt) {
            $options[$opt] = ucfirst($opt);
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Deseja Encerrar a Medida?',
            'msg'           => "Você está preste de encerrar a medida?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Encerrar!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'FinishMedProtest172030',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma medida encerrada.',
            'inputType'     => 'select',
            'inputOptions'  => $options,
            'inputValue'    => $this->protestTemp->result,
            'inputPlaceholder' => 'Selecione o resultado',
        ]);
    }

    public function finishMedProtes(?string $result = null): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!$this->protestTemp) {
            return;
        }

        $selectedResult = MedProtest::normalizeResult($result);
        if (!$selectedResult) {
            $this->toast('danger', 'Selecione o resultado da medida: procedente ou improcedente.');
            return;
        }

        try {
            $this->protestTemp->update([
                'completed'    => true,
                'completed_at' => now(),
                'result'       => $selectedResult,
            ]);

            $this->protestTemp->comments()->create([
                'user_id' => auth()->id(),
                'message' => '[SISTEMA] Medida concluída por ' . auth()->user()->name . ' em ' . now()->format('d/m/Y H:i') . '.',
            ]);

            $this->toast('success', 'Medida concluída com sucesso!');
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao concluir medida: ' . $e->getMessage());
        }
    }

    public function toReject(MedProtest $medProtestId): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->medProtest = $medProtestId;

        if (!$this->medProtest) {
            $this->toast('danger', 'Medida não encontrada.');
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Deseja Rejeitar a Medida?',
            'msg'           => "Você está preste de rejeitar a medida?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Rejeitar!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'Reject172030',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma medida rejeitada.',
        ]);
    }

    public function rejectMed(): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!$this->medProtest) {
            $this->toast('danger', 'Medida não encontrada.');
            return;
        }

        try {
            $this->medProtest->update([
                'completed'    => false,
                'completed_at' => null,
            ]);

            $this->medProtest->Assignments()
                ->where('completed', true)
                ->update([
                    'completed' => false,
                    'ended_at'  => null,
                ]);

            $this->medProtest->comments()->create([
                'user_id' => auth()->id(),
                'message' => '[SISTEMA] Conclusão de medida rejeitada por ' . auth()->user()->name . ' em ' . now()->format('d/m/Y H:i') . '.',
            ]);

            $this->toast('success', 'Medida rejeitada com sucesso!');
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao rejeitar medida: ' . $e->getMessage());
        }
    }

    public function toggleJobs(int $medProtestId): void
    {
        $this->expandedJobs[$medProtestId] = !($this->expandedJobs[$medProtestId] ?? false);
    }

    /** =======================================================================
     *  JOBS (ProtestJob) – TODAS AS INTERAÇÕES COM TRY/CATCH + TORRADA
     *  ======================================================================= */

    public function toConfirmJob(ProtestJob $job): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->jobTemp = $job;

        if (!$this->jobTemp) {
            $this->toast('danger', 'Atividade nao encontrada.');
            return;
        }

        $options = [];
        foreach (MedProtest::resultOptions() as $opt) {
            $options[$opt] = ucfirst($opt);
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Deseja Confirmar a Tarefa?',
            'msg'           => 'Voce esta prestes a confirmar a tarefa?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Confirme!',
            'btnCanceltxt'  => 'Nao, Cancele!',
            'action'        => 'confirmJob172030',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma acao realizada.',
            'inputType'     => 'select',
            'inputOptions'  => $options,
            'inputPlaceholder' => 'Selecione o resultado',
        ]);
    }

    

    public function confirmJob(?string $result = null): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!$this->jobTemp) {
            return;
        }

        try {
            $this->validate(['jobTemp.id' => 'required']);

            $selectedResult = MedProtest::normalizeResult($result);
            if (!$selectedResult) {
                throw ValidationException::withMessages([
                    'result' => 'Selecione o resultado da medida: procedente ou improcedente.',
                ]);
            }

            // metodo do modelo ja registra evento
            $this->jobTemp->confirmJob(null, $selectedResult);

            $this->toast('success', 'Tarefa confirmada com sucesso!');
            $this->jobTemp = null;
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao confirmar tarefa: ' . $e->getMessage());
        }
    }

    
public function toCancelJob(ProtestJob $job): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->jobTemp = $job;

        if (!$this->jobTemp) {
            $this->toast('danger', 'Atividade não encontrada.');
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Deseja Cancelar a Tarefa?',
            'msg'           => "Você está prestes a cancelar a tarefa?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Cancele!',
            'btnCanceltxt'  => 'Não!',
            'action'        => 'cancelJob172030',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma ação realizada.',
        ]);
    }

    public function cancelJob(): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!$this->jobTemp) {
            return;
        }

        try {
            // garante estado atual
            $this->jobTemp->refresh();

            $this->jobTemp->cancel(
                "Atividade cancelada pelo usuário " . auth()->user()->name
            );

            $this->toast('success', 'Tarefa cancelada com sucesso!');
            $this->jobTemp = null;
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao cancelar tarefa: ' . $e->getMessage());
        }
    }


    public function toReopen(ProtestJob $job): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->jobTemp = $job;

        if (!$this->jobTemp) {
            $this->toast('danger', 'Atividade não encontrada.');
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Deseja Reabrir a Tarefa?',
            'msg'           => "Você está prestes a reabrir a tarefa?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Reabra!',
            'btnCanceltxt'  => 'Não!',
            'action'        => 'reopenJob172030',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma ação realizada.',
        ]);
    }

    public function reopenJob(): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!$this->jobTemp) {
            return;
        }

        try {
            // garante estado atual
            $this->jobTemp->refresh();

            $this->jobTemp->reopen(
                "Atividade reaberta pelo usuário " . auth()->user()->name
            );

            $this->toast('success', 'Tarefa reaberta com sucesso!');
            $this->jobTemp = null;
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao reabrir tarefa: ' . $e->getMessage());
        }
    }

    public function deleteJob(int $jobId): void
    {
        if ($this->readOnly) {
            return;
        }

        try {
            $job = ProtestJob::find($jobId);

            if (!$job) {
                $this->toast('danger', 'Atividade não encontrada.');
                return;
            }

            $job->delete();

            $this->toast('success', 'Atividade deletada com sucesso!');
            $this->emit('refreshComponent');
        } catch (\Throwable $e) {
            $this->toast('danger', 'Erro ao deletar atividade: ' . $e->getMessage());
        }
    }

    /** =======================================================================
     *  RENDER
     *  ======================================================================= */

    public function render()
    {
        return view('livewire.protests.dispatch.view', [
            'protestCategories' => SelectOptions::getProtestCategory(),
            'readOnly' => $this->readOnly,
        ]);
    }
}
