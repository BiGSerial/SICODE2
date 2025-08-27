<?php

namespace App\Http\Livewire\Partner\FiveNote\Actions;

use App\Models\EvidenceFile;
use App\Models\FiveNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ViewD5 extends Component
{
    public $five;
    public $hasEvidence = false;

    public $origin = 'EMPREITEIRA';

    protected $listeners = [
        'getInfoResponse',
        'hasEvidence',
        'evidenceSaved',
        'samuca158012Encerrar' => 'toSave',
    ];

    protected $rules = [
        'five.name' => 'required|string|max:255',
    ];

    public function getInfoResponse(FiveNote $five)
    {
        $this->five = $five;

        if ($this->five) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'finishFiveModal',
            ]);
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

    public function hasEvidence(bool $has)
    {
        $this->hasEvidence = $has;
    }

    public function finishD5()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'ENCERRAR D5',
            'msg'           => "Você tem certeza que deseja encerrar o D5 {$this->five->note_d5}?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'samuca158012Encerrar',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma D5 foi encerrada.',
        ]);

    }

    public function toSave(): void
    {
        $this->emitTo('files.evidence.upload-evidence', 'saveEvidences');
    }

    public function evidenceSaved()
    {
        $this->finish();
    }


    public function finish()
    {
        DB::beginTransaction();

        try {
            $this->five->is_completed = true;
            $this->five->completed_at = now();
            $this->five->save();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OPERAÇÃO CONCLUIDA',
                'html'     => 'A operação de encerramento do D5 foi concluída com sucesso.',
                'timer'    => 5000,
            ]);

            DB::commit();

            $this->clearAll();

        } catch (\Throwable $th) {
            DB::rollBack();

            if ($files = $this->five->EvidenceFiles()->where('origin', $this->origin)->get()) {
                foreach ($files as $f) {
                    $f->delete();
                }
            }

            $this->dispatchBrowserEvent('swal', [
                 'position' => 'center',
                 'icon'     => 'error',
                 'title'    => 'OPERAÇÃO FALHOU',
                 'html'     => 'A operação de encerramento do D5 falhou.',
                 'timer'    => 5000,
             ]);
        }
    }

    public function clearAll()
    {
        $this->five = null;
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');
        $this->resetErrorBag();
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_component');

    }

    public function render()
    {
        return view('livewire.partner.five-note.actions.view-d5');
    }
}
