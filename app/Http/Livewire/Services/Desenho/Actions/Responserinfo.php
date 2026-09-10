<?php

namespace App\Http\Livewire\Services\Desenho\Actions;

use App\Models\{File, Note, Production};
use App\Services\Files\FileStorageService;
use Livewire\Component;
use ZipArchive;

class Responserinfo extends Component
{
    public ?Note $note = null;

    public ?Production $production = null;

    public $selectedFiles = [];

    public $setDays;

    public $newComment;

    protected $listeners = [
        'getInfoResponse',
        'refreshDays'      => '$refresh',
        'refreshCompanent' => '$refresh',
    ];

    public function getInfoResponse(Production $production)
    {
        $this->production = $production;

        if ($this->production) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'responserInfo',
            ]);
        }
    }

    public function addComment()
    {
        if (trim($this->newComment)) {
            $this->production->Reclaim->Comments()->create([
                'message' => $this->newComment,
                'user_id' => auth()->user()->id,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'COMENTÁRIO ADICIONADO',
                'html'     => 'Seu comentário foi adicionado com sucesso.',
                'timer'    => 2500,
            ]);

            $this->newComment = '';
            $this->emitSelf('refreshCompanent');
        }
    }

    public function downloadFile(File $file)
    {
        if ($file) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, explode('.', $file->file_name)[0] . "." . $file->ext);
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
    }

    public function zipFiles()
    {
        if (!count($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM ARQUIVO SELECIONADO',
                'timer'    => 5000,
            ]);

            return;
        }

        if (count($this->selectedFiles)) {

            $files = File::WhereIn('id', $this->selectedFiles)->get();

            if ($files) {
                $zipFile = 'Arquivos-' . $this->note->note . "-" . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                $storage    = app(FileStorageService::class);
                $tempCopies = [];

                foreach ($files as $file) {
                    $tempCopy = $storage->temporaryLocalCopy($file);

                    if (!$tempCopy) {
                        continue;
                    }

                    if (!$storage->matchesStoredChecksum($file, $tempCopy)) {
                        $zip->close();

                        foreach (array_merge($tempCopies, [$tempCopy]) as $copy) {
                            if (is_file($copy)) {
                                @unlink($copy);
                            }
                        }

                        if (file_exists($zipFile)) {
                            @unlink($zipFile);
                        }

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Checksum divergente!',
                            'html'     => 'O arquivo ' . e($file->original_name ?: $file->file_name) . ' não confere com o hash gravado no servidor.',
                            'timer'    => 5000,
                        ]);

                        return;
                    }

                    $zip->addFile($tempCopy, explode('.', $file->file_name)[0] . '.' . $file->ext);
                    $tempCopies[] = $tempCopy;
                }

                $zip->close();

                foreach ($tempCopies as $tempCopy) {
                    if (is_file($tempCopy)) {
                        @unlink($tempCopy);
                    }
                }

                $this->selectedFiles = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        }
    }

    public function addDays()
    {
        if ($this->setDays == 0) {
            return;
        }

        if (($this->note->Viabilities->last()->Days->sum('days') + $this->setDays) > 15 || ($this->note->Viabilities->last()->Days->sum('days') + $this->setDays) < 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'PRAZO INDISPONÌVEL',
                'msg'      => 'O PRAZO NAO PODE SER MAIOR QUE 15 DIAS, NEM MENOR QUE 0 DIAS.',
                'timer'    => 5000,
            ]);

            return;
        }

        try {
            foreach ($this->note->Viabilities->where('completed', false) as $viab) {
                $viab->Days()->create([
                    'days'    => $this->setDays,
                    'user_id' => auth()->user()->id,
                ]);

                $viab->save();
            }
        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Não conseguimos atualizar o prazo... nenhum dia foi adicionado.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'NOVO PRAZO',
            'html'     => 'Foram alterado o prazo para entrega da viabilidade.',
            'timer'    => 2500,
        ]);

        $this->emitSelf('refreshDays');
        $this->emitUp('refresh_main');

        $this->note    = $this->note->fresh();
        $this->setDays = 0;
    }

    public function render()
    {
        return view('livewire.services.desenho.actions.responserinfo', [
            'note' => $this->note,
        ]);
    }
}
