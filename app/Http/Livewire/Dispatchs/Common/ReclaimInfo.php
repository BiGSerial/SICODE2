<?php

namespace App\Http\Livewire\Dispatchs\Common;

use App\Models\{File, Note, Production, Reclaim};
use App\Services\Files\FileStorageService;
use Livewire\Component;
use ZipArchive;

class ReclaimInfo extends Component
{
    public ?Reclaim $reclaim = null;

    public ?Production $production = null;

    public $selectedFiles = [];

    public $setDays;

    public $newComment;

    protected $listeners = [
        'getInfoResponse',
        'getInfoByProduction',
        'refreshDays'      => '$refresh',
        'refreshComponent' => '$refresh',
    ];

    public function getInfoResponse(Reclaim $reclaim)
    {
        $this->reclaim = $reclaim;
        $this->loadReclaimDetails();

        if ($this->reclaim) {
            $this->dispatchBrowserEvent('reclaimInfoLoaded', [
                'id' => 'responserInfo',
            ]);
        }
    }

    public function getInfoByProduction($productionId): void
    {
        $production = Production::query()
            ->select(['id', 'note_id', 'service_id'])
            ->find($productionId);

        if (!$production) {
            $this->warnMissingReclaim();

            return;
        }

        $this->reclaim = Reclaim::query()
            ->where('production_id', $production->id)
            ->latest('id')
            ->first();

        if (!$this->reclaim) {
            $this->reclaim = Reclaim::query()
                ->where('note_id', $production->note_id)
                ->where('service_id', $production->service_id)
                ->latest('id')
                ->first();
        }

        $this->loadReclaimDetails();

        if ($this->reclaim) {
            $this->dispatchBrowserEvent('reclaimInfoLoaded', [
                'id' => 'responserInfo',
            ]);

            return;
        }

        $this->warnMissingReclaim();
    }

    private function loadReclaimDetails(): void
    {
        $this->reclaim?->loadMissing([
            'Note.Orders',
            'Note.Viabilities.Orders',
            'Note.Files.Service',
            'Viabilities.Form',
            'Comments.User',
            'Subcategory.Category',
            'Waiting',
            'Approvals',
            'Externals',
        ]);
    }

    private function warnMissingReclaim(): void
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'warning',
            'title'    => 'Não encontramos retorno interno para esta produção.',
            'timer'    => 2500,
        ]);
    }

    public function addComment()
    {
        if (trim($this->newComment)) {
            $this->reclaim->Comments()->create([
                'user_id' => auth()->user()->id,
                'message' => $this->newComment,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'COMENTÁRIO ADICIONADO',
                'timer'    => 2500,
            ]);

            $this->emitSelf('refreshComponent');
            $this->reset([
                'newComment',
            ]);
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
                $zipFile = 'Arquivos-' . $this->reclaim->Note->note . "-" . hash('crc32', time()) . '.zip';
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

    // public function addDays()
    // {
    //     if ($this->setDays == 0) {
    //         return;
    //     }

    //     if (($this->note->Viabilities->last()->Days->sum('days') + $this->setDays) > 15 || ($this->note->Viabilities->last()->Days->sum('days') + $this->setDays) < 0) {
    //         $this->dispatchBrowserEvent('swal', [
    //             'position' => 'center',
    //             'icon'     => 'warning',
    //             'title'    => 'PRAZO INDISPONÌVEL',
    //             'msg'    => 'O PRAZO NAO PODE SER MAIOR QUE 15 DIAS, NEM MENOR QUE 0 DIAS.',
    //             'timer'    => 5000,
    //         ]);

    //         return;
    //     }

    //     try {
    //         foreach ($this->note->Viabilities->where('completed', false) as $viab) {
    //             $viab->Days()->create([
    //                 'days' => $this->setDays,
    //                 'user_id' => auth()->user()->id,
    //             ]);

    //             $viab->save();
    //         }
    //     } catch (\Throwable $th) {
    //         $this->dispatchBrowserEvent('swal', [
    //             'position' => 'center',
    //             'icon'     => 'error',
    //             'title'    => 'ERRO',
    //             'html'    => 'Não conseguimos atualizar o prazo... nenhum dia foi adicionado.',
    //             'timer'    => 5000,
    //         ]);

    //         return;
    //     }

    //     $this->dispatchBrowserEvent('swal', [
    //         'position' => 'center',
    //         'icon'     => 'success',
    //         'title'    => 'NOVO PRAZO',
    //         'html'    => 'Foram alterado o prazo para entrega da viabilidade.',
    //         'timer'    => 2500,
    //     ]);

    //     $this->emitSelf('refreshDays');
    //     $this->emitUp('refresh_main');

    //     $this->note = $this->note->fresh();
    //     $this->setDays = 0;
    // }

    public function render()
    {
        return view('livewire.dispatchs.common.reclaim-info', [
            'reclaim' => $this->reclaim,
        ]);
    }
}
