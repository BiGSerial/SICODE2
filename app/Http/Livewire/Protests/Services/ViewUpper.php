<?php

namespace App\Http\Livewire\Protests\Services;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Models\Comment;
use App\Models\EvidenceFile;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\TemporaryUploadedFile;

class ViewUpper extends Component
{
    use WithFileUploads;

    /** @var ProtestJob */
    public ProtestJob $job;

    /** Se o usuário atual pode gerenciar o Job (gestor / delegado / dono) */
    public bool $canManageJob = false;

    /** Parecer / motivo de encerramento (close_reason) */
    public ?string $closeReason = null;

    /** Prioridade selecionada (enum value: low|normal|high|urgent) */
    public ?string $priority = null;

    /** value => label (enum) para o select */
    public array $priorityOptions = [];

    /** Novo responsável selecionado (UUID) */
    public ?string $newOwnerId = null;

    /** Usuários abaixo da hierarquia (e delegações) disponíveis para reatribuição */
    public Collection $availableUsers;

    /** @var TemporaryUploadedFile[] Arquivos enviados pelo input (buffer imediato do Livewire) */
    public $files = [];

    /** @var TemporaryUploadedFile[] Fila de arquivos para salvar */
    public $tempFiles = [];

    /** Configura��o de arquivos */
    public array $filesConfig = [
        'disk'         => 'public',
        'path'         => 'protest_attachments',
        'maxSize'      => 10240, // KB
        'allowedTypes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'],
    ];

    /** Comentário digitado no formulário */
    public ?string $comment = null;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    public function mount(int|string $jobId): void
    {
        $this->job = ProtestJob::query()
            ->with([
                'owner',
                'creator',
                'medProtest.protest',
                'medProtest.evidenceFiles',
                'medProtest.comments.user',
            ])
            ->findOrFail($jobId);

        $auth = auth()->user();
        $ownerId = $this->job->owner_id;

        // Pode gerenciar? Dono do Job OU alguém que enxerga o dono na hierarquia
        $this->canManageJob = $auth
            && $ownerId
            && (
                $auth->id === $ownerId
                || $auth->canSeeUser($ownerId)
            );

        // Enum -> options
        $this->priorityOptions = collect(ProtestJobPriority::cases())
            ->mapWithKeys(fn (ProtestJobPriority $p) => [$p->value => $p->label()])
            ->toArray();

        $this->priority    = $this->job->priority?->value;
        $this->closeReason = $this->job->close_reason;

        $this->availableUsers = $this->loadAvailableUsers($auth);

        if ($this->job->status === ProtestJobStatus::OPENED) {
            $this->job->accept();
        }
    }

    /**
     * Carrega usuários abaixo da hierarquia / delegações do viewer.
     */
    protected function loadAvailableUsers(?User $viewer): Collection
    {
        if (!$viewer) {
            return collect();
        }

        return $viewer
            ->descendantsQuery(
                includeSelf: false,
                includeDelegations: false,
                includeDelegatesTreesForPrincipal: false
            )
            ->where('users.id', '!=', $viewer->id)
            ->orderBy('name')
            ->get();
    }

    /* ==========================================================
     *   PRIORIDADE
     * ========================================================== */

    public function updatePriority(): void
    {
        if (!$this->canManageJob) {
            $this->addError('priority', 'Você não tem permissão para alterar a prioridade deste Job.');
            return;
        }

        $this->validate([
            'priority' => ['required', 'in:' . implode(',', array_keys($this->priorityOptions))],
        ], [
            'priority.required' => 'Selecione uma prioridade.',
        ]);

        $enum = ProtestJobPriority::from($this->priority);

        $this->job->update([
            'priority' => $enum,
        ]);

        $this->job->events()->create([
            'type'        => 'priority_changed',
            'actor_id'    => optional(auth()->user())->id,
            'meta'        => [
                'priority'       => $enum->value,
                'priority_label' => $enum->label(),
            ],
            'occurred_at' => now(),
        ]);

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Prioridade atualizada com sucesso.',
        ]);
    }

    /* ==========================================================
     *   REATRIBUIR RESPONSÁVEL
     * ========================================================== */

    public function reassignOwner(): void
    {
        if (!$this->canManageJob) {
            $this->addError('newOwnerId', 'Você não tem permissão para reatribuir este Job.');
            return;
        }

        $this->validate([
            'newOwnerId' => ['required', 'string', 'uuid'],
        ], [
            'newOwnerId.required' => 'Selecione um novo responsável.',
        ]);

        // Mesmo responsável? não faz nada
        if ($this->job->owner_id === $this->newOwnerId) {
            $this->addError('newOwnerId', 'Este usuário já é o responsável atual pelo Job.');
            return;
        }

        // Valida com base EXCLUSIVA na lista de usuários disponíveis (já filtrada pela hierarquia)
        $visibleIds = $this->availableUsers->pluck('id');

        if (!$visibleIds->contains($this->newOwnerId)) {
            $this->addError('newOwnerId', 'O usuário selecionado não está dentro da sua hierarquia visível.');
            return;
        }

        $actorId = (string) auth()->id();

        try {
            // Usa o contrato do próprio modelo ProtestJob
            $this->job->reassignTo($this->newOwnerId, $actorId);
            $this->job->refresh();

            // Reseta select e recarrega lista (se algo mudar na hierarquia)
            $this->newOwnerId     = null;
            $this->availableUsers = $this->loadAvailableUsers(auth()->user());

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Responsável reatribuído com sucesso.',
            ]);
        } catch (\Throwable $e) {
            logger()->error('Erro ao reatribuir responsável do Job.', [
                'job_id'    => $this->job->id,
                'new_owner' => $this->newOwnerId,
                'actor_id'  => $actorId,
                'message'   => $e->getMessage(),
            ]);

            $this->addError('newOwnerId', 'Não foi possível reatribuir o responsável. Tente novamente.');

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'Falha ao reatribuir o responsável. Verifique a seleção e tente novamente.',
            ]);
        }

    }

    /* ==========================================================
     *   FLUXO PADRÃO DO JOB (INICIAR / ENCERRAR)
     * ========================================================== */

    public function startJob(): void
    {
        if (!$this->canManageJob) {
            return;
        }

        $this->job->start();
        $this->job->refresh();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade iniciada.',
        ]);
    }

    public function finishJob(): void
    {
        if (!$this->canManageJob) {
            return;
        }

        $this->validate([
            'closeReason' => ['required', 'string', 'min:5'],
        ], [
            'closeReason.required' => 'Informe o parecer técnico final / motivo do encerramento.',
        ]);

        $this->job->finish(
            outcome: $this->job->outcome ?? [],
            reason: $this->closeReason
        );

        $this->job->refresh();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Job encerrado com sucesso.',
        ]);
    }

    /* ==========================================================
     *   UPLOAD DE ARQUIVOS (EVIDÊNCIAS)
     * ========================================================== */

    public function updatedFiles(): void
    {
        try {
            $this->validate([
                'files'   => 'array|max:5',
                'files.*' => 'mimes:' . implode(',', $this->filesConfig['allowedTypes'])
                    . '|max:' . ($this->filesConfig['maxSize'] ?? 10240),
            ]);

            foreach ($this->files as $file) {
                if (!$file instanceof TemporaryUploadedFile) {
                    continue;
                }

                $fileName = $file->getClientOriginalName();

                foreach ($this->tempFiles as $index => $existingFile) {
                    if ($existingFile->getClientOriginalName() === $fileName) {
                        unset($this->tempFiles[$index]);
                        break;
                    }
                }

                $this->tempFiles[] = $file;
            }

            $this->tempFiles = array_values($this->tempFiles);
            $this->files     = [];
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'Erro ao validar arquivos enviados.',
            ]);

            $this->reset('files');

            throw $e;
        }
    }

    public function removeFile(int $index): void
    {
        if (!isset($this->tempFiles[$index])) {
            return;
        }

        unset($this->tempFiles[$index]);
        $this->tempFiles = array_values($this->tempFiles);
    }

    public function clearAllFiles(): void
    {
        $this->tempFiles = [];
        $this->reset('files');
    }

    public function saveFiles(): void
    {
        $medProtest = $this->job->medProtest;

        if (!$medProtest) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'MedProtest não encontrado. Não foi possível salvar os arquivos.',
            ]);
            return;
        }

        if (empty($this->tempFiles)) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Nenhum arquivo selecionado para salvar.',
            ]);
            return;
        }

        $allowed = $this->filesConfig['allowedTypes'] ?? [];
        $maxSize = ($this->filesConfig['maxSize'] ?? 10240) * 1024; // bytes
        $disk    = $this->filesConfig['disk'] ?? 'public';
        $baseDir = $this->filesConfig['path'] ?? 'protest_attachments';
        $notaRef = $medProtest->protest->nota ?? $medProtest->id;
        $targetDir = trim($baseDir . '/' . $notaRef, '/');

        /** @var TemporaryUploadedFile $file */
        foreach ($this->tempFiles as $file) {
            $ext  = strtolower($file->getClientOriginalExtension());
            $size = $file->getSize();

            if (!in_array($ext, $allowed, true)) {
                continue;
            }

            if ($size > $maxSize) {
                continue;
            }

            $storedName = sprintf(
                'evidencia_%s_%s_%s.%s',
                $notaRef,
                $medProtest->med_id ?? $medProtest->id,
                uniqid(),
                $ext
            );

            $path = $file->storeAs($targetDir, $storedName, $disk);

            $sha256 = null;

            try {
                $sha256 = hash_file('sha256', $file->getRealPath());
            } catch (\Throwable $e) {
                // ignora hash se n�o conseguir ler o arquivo tempor�rio
            }

            $medProtest->EvidenceFiles()->create([
                'user_id'       => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'disk'          => $disk,
                'path'          => $path,
                'mime'          => $file->getClientMimeType(),
                'extension'     => $ext,
                'size'          => $size,
                'sha256'        => $sha256,
                'origin'        => 'view-upper',
                'uploaded_at'   => now(),
            ]);
        }

        $this->clearAllFiles();

        // recarrega relação de evidências
        $this->job->load('medProtest.evidenceFiles');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Arquivos salvos com sucesso.',
        ]);
    }

    public function deleteFile(int $fileId): void
    {
        $medProtest = $this->job->medProtest;

        if (!auth()->user()?->superadm || !$medProtest) {
            return;
        }

        $file = $medProtest->evidenceFiles()->where('id', $fileId)->first();

        if (!$file) {
            return;
        }

        $disk = $file->disk ?? 'public';

        if ($file->path && Storage::disk($disk)->exists($file->path)) {
            Storage::disk($disk)->delete($file->path);
        }

        $file->delete();

        $this->job->load('medProtest.evidenceFiles');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Arquivo removido com sucesso.',
        ]);
    }

    public function downloadFile(int $fileId)
    {
        $medProtest = $this->job->medProtest;

        if (!$medProtest) {
            return;
        }

        $file = $medProtest->evidenceFiles()->where('id', $fileId)->first();

        if (!$file || !$file->path) {
            return;
        }

        $disk = $file->disk ?? 'public';
        $downloadName = $file->original_name ?? $file->stored_name ?? basename($file->path);

        return Storage::disk($disk)->download(
            $file->path,
            $downloadName
        );
    }

    /* Helpers visuais para ícones / tamanho de arquivo
     * usados no Blade pelo $this->getFileIconClass(), etc.
     */

    public function getFileIconClass(string $extension): string
    {
        $ext = strtolower($extension);

        return match ($ext) {
            'pdf'         => 'bg-danger-subtle text-danger',
            'doc', 'docx' => 'bg-primary-subtle text-primary',
            'xls', 'xlsx' => 'bg-success-subtle text-success',
            'jpg', 'jpeg', 'png' => 'bg-warning-subtle text-warning',
            'txt'         => 'bg-secondary-subtle text-secondary',
            default       => 'bg-light text-muted',
        };
    }

    public function getFileIcon(string $extension): string
    {
        $ext = strtolower($extension);

        return match ($ext) {
            'pdf'                   => 'ri-file-pdf-2-line',
            'doc', 'docx'           => 'ri-file-word-2-line',
            'xls', 'xlsx'           => 'ri-file-excel-2-line',
            'jpg', 'jpeg', 'png'    => 'ri-image-2-line',
            'txt'                   => 'ri-file-text-line',
            default                 => 'ri-file-3-line',
        };
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i     = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        $precision = $i === 0 ? 0 : 2;

        return number_format($bytes, $precision, ',', '.') . ' ' . $units[$i];
    }

    /* ==========================================================
     *   COMENTÁRIOS (DISCUSSÃO DA MEDIDA)
     * ========================================================== */

    public function addComment(): void
    {
        $medProtest = $this->job->medProtest;

        if (!$medProtest) {
            $this->addError('comment', 'MedProtest não encontrado.');
            return;
        }

        $this->validate([
            'comment' => ['required', 'string', 'min:3'],
        ], [
            'comment.required' => 'Digite uma mensagem.',
            'comment.min'      => 'O comentário deve ter pelo menos 3 caracteres.',
        ]);

        /** @var Comment $comment */
        $comment = $medProtest->Comments()->create([
            'user_id' => auth()->id(),
            'message' => $this->comment,
        ]);

        $this->comment = null;

        $this->job->load('medProtest.comments.user');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Comentário registrado com sucesso.',
        ]);
    }

    public function removeComment(int $commentId): void
    {
        $medProtest = $this->job->medProtest;

        if (!$medProtest) {
            return;
        }

        /** @var Comment|null $comment */
        $comment = $medProtest->comments()
            ->where('id', $commentId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$comment) {
            return;
        }

        $comment->delete();

        $this->job->load('medProtest.comments.user');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Comentário excluído.',
        ]);
    }

    /* ==========================================================
     *   RENDER
     * ========================================================== */

    public function render()
    {
        // sempre recarrega relações principais para manter a tela viva
        $job = $this->job->fresh([
            'owner',
            'creator',
            'medProtest.protest',
            'medProtest.evidenceFiles',
            'medProtest.comments.user',
        ]);

        return view('livewire.protests.services.view-upper', [
            'job'           => $job,
            'canManageJob'  => $this->canManageJob,
            'priorityOptions' => $this->priorityOptions,
            'availableUsers'  => $this->availableUsers,
            'tempFiles'       => $this->tempFiles,
            'filesConfig'     => $this->filesConfig,
        ]);
    }
}
