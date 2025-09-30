<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\File;
use App\Models\Reclaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use ZipArchive;

class ConfirmWorkReturn extends Component
{
    public $reclaim;
    public $service;
    public $openServiceId;
    public $selectedFiles = [];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openConfirmWorkReturn',
        'confirm_approve_return',
        'confirm_reject_return',
    ];


    public function openConfirmWorkReturn(Reclaim $reclaim)
    {

        $this->reclaim = $reclaim->load('externals', 'externals.entity', 'note.files', 'service', 'production.analise');

        if ($this->reclaim) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modalApproveReclaim',
            ]);
        }
    }

    public function downloadFile(File $file)
    {
        if ($file) {

            if (Storage::fileExists($file->path)) {
                return Storage::download($file->path, explode('.', $file->file_name)[0].".".$file->ext);
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
                $zipFile = 'Arquivos-'.$this->buscar->note."-" . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString(explode('.', $file->file_name)[0] . '.' . $file->ext, $content);
                }

                $zip->close();

                $this->selectedFiles = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        }
    }

    public function getItemProperty()
    {
        return $this->reclaim;
    }


    public function toConfirmApprove()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => "APROVAR RETORNO INTERNO?",
            'msg'           => "Ao aprovar o retorno interno vocë estará confirmando que o serviço foi concluído e está apto para continuar atividade.",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Confirmar!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'confirm_approve_return',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum Retorno Aprovado!',

        ]);
    }

    public function toRejectApprove()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => "REJEITAR RETORNO INTERNO?",
            'msg'           => "Ao rejeitar o retorno interno vocë estará confirmando que o serviço não foi concluído e não está apto para continuar atividade.",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Confirmar!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'confirm_reject_return',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum Retorno Rejeitado!',

        ]);
    }

    public function confirm_approve_return()
    {
        DB::beginTransaction();

        try {
            $this->reclaim->externals()->updateExistingPivot(
                $this->reclaim->externals->last()->id,
                [
                    'completed' => true,
                    'completed_at' => now()
                ]
            );

            $note = $this->reclaim->note->note;

            DB::commit();

            $this->emit('refresh_list');
            // $this->emit('navigateTo', $note);
            $this->dispatchBrowserEvent('hideModal');

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'RETORNO APROVADO COM SUCESSO!',
                'timer'    => 2500,
            ]);


        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO APROVAR RETORNO!',
                'text'     => $e->getMessage(),

            ]);
        }
    }

    public function confirm_reject_return()
    {
        DB::beginTransaction();

        try {
            $this->reclaim->update([
                'completed' => false,
                'completed_at' => null,
            ]);

            $production = $this->reclaim->production;
            if ($production) {
                $production->update([
                    'status' => 2,
                    'completed' => false,
                    'completed_at' => null,
                ]);
            }


            DB::commit();

            $this->emit('refresh_list');
            // $this->emit('navigateTo', $note);
            $this->dispatchBrowserEvent('hideModal');

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'RETORNO REJEITADO E RETORNADO COM SUCESSO!',
                'timer'    => 2500,
            ]);


        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO REJEITAR O RETORNO!',
                'text'     => $e->getMessage(),

            ]);
        }
    }

    public function render()
    {
        return view('livewire.services.oexterno.actions.confirm-work-return', [
            'item' => $this->item,
        ]);
    }
}
