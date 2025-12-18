<?php

namespace App\Http\Livewire\Protests\Partner;

use App\Enum\ProtestJobStatus;
use App\Models\EvidenceFile;
use App\Models\ProtestJob;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class View extends Component
{
    use WithFileUploads;

    /** @var ProtestJob|null */
    public $job = null;

    /** @var \App\Models\MedProtest|null */
    public $medProtest = null;

    /** Campos de interação */
    public string $comment     = '';
    public string $conclusion  = '';   // parecer técnico
    public string $closeReason = '';   // motivo de encerramento (close_reason obrigatório)

    /** Uploads */
    public array $filesConfig = [
        'disk'         => 'public',
        'path'         => 'protest_attachments',
        'maxSize'      => (10 * 1024), // 10MB em KB (regra max: é em KB)
        'allowedTypes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'],
    ];

    /**
     * Cesta temporária de arquivos (já validados) antes de salvar de fato.
     * @var TemporaryUploadedFile[]
     */
    public $tempFiles = [];

    /**
     * Propriedade ligada ao input de arquivos.
     * @var TemporaryUploadedFile[]|null
     */
    public $files = [];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'confirmFinishJob' => 'doFinishJob',
    ];

    protected array $messages = [
        'comment.required'     => 'O comentário é obrigatório.',
        'comment.string'       => 'O comentário deve ser uma string.',
        'comment.min'          => 'O comentário deve ter pelo menos 10 caracteres.',
        'conclusion.required'  => 'O parecer final é obrigatório.',
        'conclusion.min'       => 'O parecer final deve ter pelo menos 10 caracteres.',
        'closeReason.required' => 'O motivo de encerramento é obrigatório.',
        'closeReason.min'      => 'O motivo de encerramento deve ter pelo menos 5 caracteres.',
        'files.*.mimes'        => 'Apenas arquivos PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, TXT são permitidos.',
        'files.*.max'          => 'Cada arquivo não pode ter mais de 10MB.',
        'files.max'            => 'Você pode anexar no máximo 5 arquivos de cada vez.',
    ];

    /** Carrega o JOB como raiz, e dele puxa Protest + MedProtest */
    public function mount(int $jobId): void
    {
        $this->job = ProtestJob::with([
            'protest.Notes',
            'medProtest' => function ($q) {
                $q->with([
                    'Protest',
                    'Notes',
                    'EvidenceFiles',
                    'Comments' => fn ($qq) => $qq->with('User')->orderByDesc('created_at'),
                ]);
            },
            'Comments' => fn ($q) => $q->latest(),
            'owner:id,name',
            'creator:id,name',
        ])->findOrFail($jobId);

        if (! $this->job->medProtest) {
            abort(404, 'Medida de Reclamação não associada a este Job.');
        }

        $this->medProtest = $this->job->medProtest;

         if ($this->job->status === ProtestJobStatus::OPENED) {
            $this->job->accept();
        }
    }

    /** Upload incremental (mantendo tempFiles como "cesta" de anexos) */
    public function updatedFiles(): void
    {
        try {
            $this->validate([
                'files'   => 'array|max:5',
                'files.*' => 'mimes:' . implode(',', $this->filesConfig['allowedTypes'])
                    . '|max:' . $this->filesConfig['maxSize'], // max em KB
            ]);

            foreach ($this->files as $file) {
                if (! $file instanceof TemporaryUploadedFile) {
                    continue;
                }

                $fileName = $file->getClientOriginalName();

                // Evita duplicado com mesmo nome
                foreach ($this->tempFiles as $index => $existingFile) {
                    if ($existingFile->getClientOriginalName() === $fileName) {
                        unset($this->tempFiles[$index]);
                        break;
                    }
                }

                $this->tempFiles[] = $file;
            }

            // Reorganiza indexes e limpa o input
            $this->tempFiles = array_values($this->tempFiles);
            $this->files     = [];
        } catch (ValidationException $e) {
            $this->emit('showAlert', [
                'type'    => 'error',
                'message' => 'Erro ao validar arquivos.',
                'errors'  => $e->errors(),
            ]);
            $this->reset('files');
            throw $e;
        }
    }

    /** Botão "Iniciar atividade" (delega início para o modelo ProtestJob) */
    public function startJob(): void
    {
        try {
            // Quem controla se pode ou não iniciar é o próprio modelo ProtestJob
            // (datas, status, eventos, concorrência etc.)
            $this->job->start(); // <- método de domínio do ProtestJob

            $this->job->refresh();

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Atividade iniciada. O SLA da atividade está em contagem.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Não foi possível iniciar a atividade',
                'text'     => $e->getMessage(),
                'timer'    => 6000,
            ]);
        }
    }

    /** Abre o diálogo de confirmação para finalizar o Job */
    public function finishJob(): void
    {
        $needsEvidence = (bool) ($this->need_evidence ?? false);
        $hasEvidence   = $this->medProtest->evidenceFiles()->exists();

        $this->validate([
            'closeReason' => 'required|min:5',
        ]);

        if ($needsEvidence && ! $hasEvidence) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Evidências pendentes',
                'text'     => 'Esta atividade exige anexos. Anexe pelo menos um arquivo antes de encerrar.',
                'timer'    => 6000,
            ]);
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'FINALIZAR ATIVIDADE DO JOB',
            'msg'           => 'Tem certeza que deseja finalizar esta atividade da medida de reclamação?',
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, finalizar!',
            'btnCanceltxt'  => 'Não, cancelar',
            'action'        => 'confirmFinishJob',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação cancelada.',
        ]);
    }

    /**
     * Finalização efetiva:
     * quem muda estado é EXCLUSIVAMENTE o ProtestJob::done()
     */
    public function doFinishJob(): void
    {
        $needsEvidence = (bool) ($this->medProtest->needsEvidence ?? false);

        if ($needsEvidence && ! $this->medProtest->evidenceFiles()->exists()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Evidências pendentes',
                'text'     => 'Esta atividade exige anexos. Anexe pelo menos um arquivo antes de encerrar.',
                'timer'    => 6000,
            ]);
            return;
        }

        $this->validate([

            'closeReason' => 'required|min:5',
        ]);

        // Regra de permissão ainda fica aqui (domínio de aplicação)
        if (! (auth()->id() === $this->job->owner_id || auth()->user()?->superadm)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Permissão negada',
                'text'     => 'Somente o responsável pelo Job pode encerrar a atividade.',
                'timer'    => 5000,
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            /** @var User|null $responsible */
            $responsible = $this->job->creator;

            // Comentário técnico (fica registrado na MedProtest)
            $this->medProtest->comments()->create([
                'user_id' => auth()->id(),
                'message' => '[SISTEMA] Atividade do Job concluída por ' . auth()->user()->name .
                    ' em ' . now()->format('d/m/Y H:i') .
                    ' | Motivo de encerramento: ' . trim($this->closeReason),
            ]);
            // Relatório técnico vinculado à MedProtest


            $mensagemLog = 'Atividade concluída por ' . auth()->user()->name .
                ' | Motivo: ' . trim($this->closeReason);

            $outcome =  [
                        'med_protest_id' => $this->medProtest->id,
                        'protest_job_id' => $this->job->id,
                        'finished_by'    => auth()->id(),
            ];

            // Encerramento REAL da atividade: delegado ao método de domínio do ProtestJob
            $this->job->finish($outcome, trim($this->closeReason));

            // Notifica o despachante / criador do Job
            if ($responsible instanceof User) {
                $responsible->notify(new SystemNotification(
                    titulo: 'Job de Medida de Reclamação finalizado',
                    mensagem: 'O usuário ' . auth()->user()->name . ' concluiu a atividade da reclamação ' . $this->medProtest->protest?->nota . '.',
                    link: route('protests.dispatch.view', $this->medProtest->protest?->nota),
                    status: 7,
                    extras: [
                        'med_protest_id' => $this->medProtest->id,
                        'protest_job_id' => $this->job->id,
                        'finished_by'    => auth()->id(),
                    ]
                ));
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao finalizar atividade!',
                'text'     => $th->getMessage(),
                'timer'    => 6000,
            ]);
            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Atividade finalizada com sucesso!',
            'timer'    => 5000,
        ]);

        $this->conclusion  = '';
        $this->closeReason = '';

        $this->job->refresh();
        $this->medProtest->refresh();
        $this->emitSelf('refreshComponent');
    }

    public function addComment(): void
    {
        $this->validate([
            'comment' => 'required|string|min:10',
        ]);

        $this->medProtest->comments()->create([
            'user_id' => auth()->id(),
            'message' => $this->comment,
        ]);

        $targets = collect([$this->job->creator, $this->job->owner])
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->id === auth()->id());

        foreach ($targets as $user) {
            $user->notify(new SystemNotification(
                titulo: 'Novo comentário na Medida de Reclamação',
                mensagem: 'O usuário ' . auth()->user()->name . ' comentou na medida da reclamação ' . $this->medProtest->protest?->nota . '.',
                link: route('protests.services.view', $this->job->id),
                status: 6,
                extras: [
                    'med_protest_id' => $this->medProtest->id,
                    'protest_job_id' => $this->job->id,
                    'commented_by'   => auth()->id(),
                ]
            ));
        }

        $this->comment = '';
        $this->medProtest->refresh();
        $this->emitSelf('refreshComponent');
    }

    public function removeComment(int $commentId): void
    {
        $comment = $this->medProtest->comments()->findOrFail($commentId);

        if ($comment->user_id !== auth()->id() && ! auth()->user()?->admin && ! auth()->user()?->superadm) {
            abort(403, 'Você não tem permissão para remover este comentário.');
        }

        $comment->delete();
        $this->medProtest->refresh();
        $this->emitSelf('refreshComponent');
    }

    public function downloadFiles(EvidenceFile $file)
    {
        if (Storage::fileExists('public/' . $file->path)) {
            return Storage::download('public/' . $file->path);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO INEXISTENTE!',
            'timer'    => 5000,
        ]);
        return;
    }

    public function deleteFile(EvidenceFile $file): void
    {
        if ($file) {
            $file->delete();

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Arquivo removido com sucesso!',
            ]);

            $this->medProtest->refresh();
            $this->emitSelf('refreshComponent');
        }
    }

    public function saveFiles(): void
    {
        if (empty($this->tempFiles)) {
            $this->dispatch('showAlert', [
                'type'    => 'warning',
                'message' => 'Nenhum arquivo para salvar.',
            ]);
            return;
        }

        foreach ($this->tempFiles as $file) {
            try {
                if (! $file instanceof TemporaryUploadedFile) {
                    continue;
                }

                $filename = 'evidencia_' .
                    $this->medProtest->protest->nota . '_' .
                    $this->medProtest->med_id . '_' .
                    uniqid() . '.' . $file->getClientOriginalExtension();

                $path = $file->storeAs(
                    $this->filesConfig['path'] . '/' . $this->medProtest->protest->nota,
                    $filename,
                    'public'
                );

                $this->medProtest->EvidenceFiles()->create([
                    'user_id'       => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name'   => $filename,
                    'disk'          => $this->filesConfig['disk'],
                    'path'          => $path,
                    'mime'          => $file->getClientMimeType(),
                    'extension'     => $file->getClientOriginalExtension(),
                    'size'          => $file->getSize(),
                    'sha256'        => hash_file('sha256', $file->getRealPath()),
                    'uploaded_at'   => now(),
                ]);
            } catch (\Exception $e) {
                logger()->error('Error saving file: ' . $e->getMessage(), [
                    'file'         => $file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : null,
                    'medProtestId' => $this->medProtest->id ?? null,
                ]);

                $this->dispatch('showAlert', [
                    'type'    => 'error',
                    'message' => 'Erro ao salvar o arquivo. Por favor, tente novamente.',
                ]);
            }
        }

        $this->tempFiles = [];
        $this->medProtest->refresh();
        $this->emitSelf('refreshComponent');
    }

    public function removeFile(int $index): void
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
            $this->emitSelf('refreshComponent');
        }
    }

    public function clearAllFiles(): void
    {
        $this->tempFiles = [];
        $this->reset('files');
    }

    public function getFileIconClass(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf'                => 'bg-danger text-white',
            'doc', 'docx'        => 'bg-primary text-white',
            'xls', 'xlsx'        => 'bg-success text-white',
            'jpg', 'jpeg', 'png' => 'bg-info text-white',
            'txt'                => 'bg-secondary text-white',
            default              => 'bg-dark text-white',
        };
    }

    public function getFileIcon(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf'                => 'ri-file-pdf-fill',
            'doc', 'docx'        => 'ri-file-word-fill',
            'xls', 'xlsx'        => 'ri-file-excel-fill',
            'jpg', 'jpeg', 'png' => 'ri-image-fill',
            'txt'                => 'ri-file-text-fill',
            default              => 'ri-file-fill',
        };
    }

    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        if ($bytes > 1) {
            return $bytes . ' bytes';
        }
        return '0 bytes';
    }

    public function render()
    {
        return view('livewire.protests.services.view');
    }
}
