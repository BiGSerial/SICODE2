<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\EvidenceFile;
use App\Models\MedProtest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class View extends Component
{
    use WithFileUploads;

    public $medProtest;
    public $comment;
    public $conclusion = '';

    public $filesConfig = [
        'disk' => 'public',
        'path' => 'protest_attachments',
        'maxSize' => (10 * 1024),
        'allowedTypes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'],

    ];
    /**
     * @var array<TemporaryUploadedFile>
     */
    public $tempFiles = [];
    /**
     * @var array<TemporaryUploadedFile>|null
     */
    public $files = []; // This will hold the files selected for upload

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'confirmFinishMedProtest' => 'finish',
    ];

    protected $messages = [
        'comment.required' => 'O comentário é obrigatório.',
        'comment.string' => 'O comentário deve ser uma string.',
        'comment.min' => 'O comentário deve ter pelo menos 10 caracteres.',
        'conclusion.required' => 'O parecer final é obrigatório.',
        'conclusion.min'     => 'O parecer final deve ter pelo menos 10 caracteres.',
        'files.*.mimes' => 'Apenas arquivos PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, TXT são permitidos.',
        'files.*.max' => 'Cada arquivo não pode ter mais de 10MB.',
        'files.max' => 'Você pode anexar no máximo 5 arquivos de cada vez.',
    ];

    // This method is called automatically by Livewire when the 'files' property is updated
    public function updatedFiles()
    {
        try {
            // Validate the newly added files
            $this->validate([
                'files.*' => 'mimes:'.implode(',', $this->filesConfig['allowedTypes']).'|max:'.$this->filesConfig['maxSize'],
            ]);

            foreach ($this->files as $file) {
                $fileName = $file->getClientOriginalName();

                // Check if a file with the same name already exists in tempFiles
                foreach ($this->tempFiles as $index => $existingFile) {
                    if ($existingFile->getClientOriginalName() === $fileName) {
                        // Remove the existing file
                        unset($this->tempFiles[$index]);
                        break;
                    }
                }

                // Add the new file
                $this->tempFiles[] = $file;
            }

            // Reindex the array to maintain sequential indices
            $this->tempFiles = array_values($this->tempFiles);
            $this->files = []; // Clear the input files after adding to tempFiles

        } catch (ValidationException $e) {
            $this->emit('showAlert', ['type' => 'error', 'message' => 'Erro ao validar arquivos.', 'errors' => $e->errors()]);
            $this->reset('files'); // Clear the files that caused the validation error
            throw $e; // Re-throw to show validation messages
        }
    }

    public function finishMedProtest()
    {
        // Validações rápidas de UX: antes de abrir o confirm
        $needsEvidence = (bool) ($this->medProtest->needsEvidence ?? false);
        $hasEvidence   = $this->medProtest->evidenceFiles()->count() > 0;
        $hasConclusion = mb_strlen(trim((string) $this->conclusion)) >= 10;

        if ($needsEvidence && !$hasEvidence) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Evidências pendentes',
                'text'     => 'Esta medida exige anexos. Anexe pelo menos um arquivo antes de encerrar.',
                'timer'    => 6000,
            ]);
            return;
        }

        if (!$hasConclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Parecer final obrigatório',
                'text'     => 'Informe o parecer final (mínimo 10 caracteres) para concluir o laudo.',
                'timer'    => 6000,
            ]);
            return;
        }

        // Abre o confirm padrão que você já usa
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'FINALIZAR MEDIDA DE RECLAMAÇÃO',
            'msg'           => "Você tem certeza que deseja finalizar esta medida de reclamação?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Finalizar!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirmFinishMedProtest',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',
        ]);
    }

    public function finish()
    {
        // validações de segurança no servidor
        $this->validate([
            'conclusion' => 'required|min:10',
        ]);

        $needsEvidence = (bool) ($this->medProtest->needsEvidence ?? false);
        if ($needsEvidence && !$this->medProtest->evidenceFiles()->exists()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center','icon' => 'error',
                'title' => 'Evidências pendentes',
                'text' => 'Esta medida exige anexos. Anexe pelo menos um arquivo antes de encerrar.',
                'timer' => 6000,
            ]);
            return;
        }

        $userAssigned = $this->medProtest->Assignments()
            ->where('completed', false)
            ->where('user', true)
            ->where('monitoring', false)
            ->first();

        if (!$userAssigned) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center','icon' => 'error',
                'title' => 'Você não está designado para esta medida de reclamação!',
                'timer' => 5000,
            ]);
            return;
        }

        DB::beginTransaction();


        try {

            $responsible = $this->medProtest->Assignments()
                ->where('user', false)
                ->where('responsible', true)
                ->first()?->User;
            // Finaliza assignment do usuário
            $userAssigned->update([
                'completed' => true,
                'ended_at'  => now(),
            ]);

            // Se não precisa de confirmação: encerra a MedProtest
            if (!$this->medProtest->needsConfirmation) {
                $this->medProtest->update([
                    'completed'     => true,
                    'completed_at'  => now(),
                    'statusSist'    => 'ENCERRADO',
                ]);

                // amarra outros assignments
                $this->medProtest->Assignments()
                    ->where('completed', false)
                    ->update([
                        'completed' => true,
                        'ended_at'  => now(),
                    ]);
            }

            // cria/atualiza o relatório técnico (hasOne)
            $this->medProtest->technicalReport()->updateOrCreate(
                ['med_protest_id' => $this->medProtest->id],
                [
                    'title'           => 'Parecer Técnico - Reclamação '.$this->medProtest->protest?->nota,
                    'initial_content' => null,
                    'content'         => trim($this->conclusion),
                    'report_date'     => now()->toDateString(),
                    'user_id'         => auth()->id(),
                ]
            );


            if ($responsible && $responsible instanceof User) {
                $responsible->notify(new SystemNotification(
                    titulo: 'Medida de Reclamação finalizada',
                    mensagem: 'O usuário '.auth()->user()->name.' concluiu a medida da reclamação '.$this->medProtest->protest?->nota.'.',
                    link: route('protests.dispatch.view', $this->medProtest->protest?->nota), // ou outra rota que você tiver
                    status: 7,
                    extras: [
                        'med_protest_id' => $this->medProtest->id,
                        'finished_by'    => auth()->id(),
                    ]
                ));
            }



        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center','icon' => 'error',
                'title' => 'Erro ao finalizar medida de reclamação!',
                'text' => $th->getMessage(),
                'timer' => 6000,
            ]);
            return;
        }

        DB::commit();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center','icon' => 'success',
            'title' => 'Medida de Reclamação finalizada com sucesso!',
            'timer' => 5000,
        ]);

        // limpa o parecer depois de finalizar
        $this->conclusion = '';
        $this->emitSelf('refreshComponent');
    }


    public function mount($medProtestId)
    {
        $this->medProtest = MedProtest::with([
            'Protest.Notes',
            'Comments' => function ($query) {
                $query->with('User')->orderBy('created_at', 'desc');
            },
            'Notes',
            'Assignments.User',

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

        $this->medProtest->comments()->create([
            'user_id' => auth()->id(),
            'message' => $this->comment,
        ]);

        if ($responsible = $this->medProtest->Assignments()
            ->where('user', false)
            ->where('responsible', true)
            ->first()?->User) {
            $responsible->notify(new SystemNotification(
                titulo: 'Novo comentário na Medida de Reclamação',
                mensagem: 'O usuário '.auth()->user()->name.' comentou na medida da reclamação '.$this->medProtest->protest?->nota.'.',
                link: route('protests.dispatch.view', $this->medProtest->protest?->nota), // ou outra rota que você tiver
                status: 6,
                extras: [
                'med_protest_id' => $this->medProtest->id,
                'commented_by'   => auth()->id(),
                ]
            ));
        }

        $this->comment = '';
        $this->emit('refreshComponent'); // Refresh the component to show the new comment

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

    public function removeComment($commentId)
    {
        $comment = $this->medProtest->comments()->findOrFail($commentId);

        if ($comment->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para remover este comentário.');
        }

        $comment->delete();
        $this->emit('refreshComponent'); // Refresh the component to remove the comment

    }

    public function saveFiles()
    {
        if (empty($this->tempFiles)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Nenhum arquivo para salvar.']);
            return;
        }

        $savedFiles = [];
        foreach ($this->tempFiles as $file) {
            try {
                // Generate a unique filename to prevent conflicts
                $filename =  'evidencia_'.$this->medProtest->protest->nota. '_' . $this->medProtest->med_id . '_'.uniqid(). ".". $file->getClientOriginalExtension();
                $path = $file->storeAs($this->filesConfig['path'] . "/" . $this->medProtest->protest->nota, $filename, 'public');

                // Store file information in the database
                $this->medProtest->EvidenceFiles()->create([
                    'user_id' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $filename,
                    'disk' => $this->filesConfig['disk'],
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_at' => now(),
                ])->save();
                $savedFiles[] = $file->getClientOriginalName();
            } catch (\Exception $e) {
                // Log the error and notify the user
                logger()->error('Error saving file: ' . $e->getMessage(), ['file' => $file->getClientOriginalName(), 'medProtestId' => $this->medProtest->id]);
                $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Erro ao salvar o arquivo ' . $file->getClientOriginalName() . '. Por favor, tente novamente.']);
            }
        }

        $this->tempFiles = []; // Clear temporary files after saving
        $this->emit('refreshComponent'); // Refresh the component to display saved attachments

    }

    public function removeFile($index)
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
            $this->emitSelf('refreshComponent');
        }
    }

    public function clearAllFiles()
    {
        $this->tempFiles = [];
        $this->reset('files'); // Also clear any current files in the Livewire property

    }

    // Helper function to get file icon class based on extension
    public function getFileIconClass($extension)
    {
        return match ($extension) {
            'pdf' => 'bg-danger text-white',
            'doc', 'docx' => 'bg-primary text-white',
            'xls', 'xlsx' => 'bg-success text-white',
            'jpg', 'jpeg', 'png' => 'bg-info text-white',
            'txt' => 'bg-secondary text-white',
            default => 'bg-dark text-white',
        };
    }

    // Helper function to get file icon based on extension
    public function getFileIcon($extension)
    {
        return match ($extension) {
            'pdf' => 'ri-file-pdf-fill',
            'doc', 'docx' => 'ri-file-word-fill',
            'xls', 'xlsx' => 'ri-file-excel-fill',
            'jpg', 'jpeg', 'png' => 'ri-image-fill',
            'txt' => 'ri-file-text-fill',
            default => 'ri-file-fill',
        };
    }

    // Helper function to format file size
    public function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } else {
            return '0 bytes';
        }
    }




    public function render()
    {
        return view('livewire.protests.services.view', [
            'medProtest' => $this->medProtest,
        ]);
    }
}
