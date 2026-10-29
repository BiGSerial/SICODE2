<?php

namespace App\Http\Livewire\Files\Manager\Concerns;

use App\Services\Files\AttachmentPolicy;
use Illuminate\Support\Facades\Validator;

/**
 * Triagem dos arquivos escolhidos num componente de anexos, conforme a AttachmentPolicy (tipos, 30 MB por
 * arquivo e 100 MB por envio): separa os válidos dos recusados, apaga os recusados do armazenamento temporário
 * e avisa o usuário com um único SweetAlert (nome do arquivo + motivo).
 */
trait ScreensAttachedFiles
{
    /** Sobrescreva com `true` nos fluxos que já aceitavam ZIP. Público porque as telas também usam. */
    public function attachmentAllowsZip(): bool
    {
        return false;
    }

    /**
     * @param  iterable<\Livewire\TemporaryUploadedFile>  $files
     * @param  array<int, array{file: \Livewire\TemporaryUploadedFile}>  $queued  itens já na fila (tempFiles)
     * @return array<int, \Livewire\TemporaryUploadedFile> arquivos aceitos
     */
    protected function screenAttachments(iterable $files, array $queued = []): array
    {
        $allowed   = AttachmentPolicy::extensions($this->attachmentAllowsZip());
        $maxFile   = AttachmentPolicy::maxFileBytes();
        $remaining = AttachmentPolicy::maxBatchBytes() - collect($queued)->sum(fn ($item) => $item['file']->getSize());
        $seen      = collect($queued)->map(fn ($item) => $item['file']->getClientOriginalName())->all();
        $accepted  = [];
        $rejected  = [];

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            $ext  = strtolower($file->getClientOriginalExtension());
            $size = $file->getSize();

            $reason = match (true) {
                !in_array($ext, $allowed, true) => 'tipo de arquivo não permitido',
                $size > $maxFile                => 'tamanho acima de ' . AttachmentPolicy::MAX_FILE_MB . ' MB por arquivo',
                in_array($name, $seen, true)    => 'já está na fila de envio',
                $size > $remaining              => 'ultrapassa o limite de ' . AttachmentPolicy::MAX_BATCH_MB . ' MB por envio',
                in_array($ext, AttachmentPolicy::CONTENT_CHECKED, true)
                    && Validator::make(['file' => $file], ['file' => 'file|mimes:' . implode(',', AttachmentPolicy::CONTENT_CHECKED)])->fails() => 'conteúdo não corresponde ao tipo do arquivo',
                default                                                                                                                         => null,
            };

            if ($reason === null) {
                $seen[] = $name;
                $remaining -= $size;
                $accepted[] = $file;

                continue;
            }

            $rejected[$name] = $reason;

            if ($file->exists()) {
                $file->delete();
            }
        }

        if ($rejected) {
            $this->notifyRejectedFiles($rejected);
        }

        return $accepted;
    }

    /** @param array<string, string> $rejected nome do arquivo => motivo */
    protected function notifyRejectedFiles(array $rejected): void
    {
        $items = collect($rejected)->map(fn ($reason, $name) => '<li class="text-start"><strong>' . e($name) . '</strong> — ' . e($reason) . '</li>')->implode('');
        $hint  = 'Formatos aceitos: ' . AttachmentPolicy::describe($this->attachmentAllowsZip()) . '. Limites: ' . AttachmentPolicy::MAX_FILE_MB . ' MB por arquivo e ' . AttachmentPolicy::MAX_BATCH_MB . ' MB por envio.';

        $this->dispatchBrowserEvent('swal', [
            'position'          => 'center',
            'icon'              => 'warning',
            'title'             => count($rejected) === 1 ? 'Arquivo não anexado' : 'Alguns arquivos não foram anexados',
            'html'              => '<ul class="mb-2 ps-3">' . $items . '</ul><div class="small text-muted">' . e($hint) . '</div>',
            'confirmButtonText' => 'Entendi',
        ]);
    }
}
