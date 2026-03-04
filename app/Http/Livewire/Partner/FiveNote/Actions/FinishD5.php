<?php

namespace App\Http\Livewire\Partner\FiveNote\Actions;

use App\Models\EvidenceFile;
use App\Models\FiveNote;
use App\Services\D5\D5WorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class FinishD5 extends Component
{
    public $five;
    public $hasEvidence = false;
    public $observations;
    public $editingDescription = false;
    public bool $isSaving = false;
    public int $evidenceKey = 0;

    public $origin = 'EMPREITEIRA';

    protected $listeners = [
        'getInfoResponse',
        'hasEvidence',
        'evidenceSaved',
        'samuca158012Encerrar' => 'toSave',
    ];

    protected $rules = [
        'five.name' => 'required|string|max:255',
        'five.description' => 'nullable|string|max:2000',
    ];

    public function getInfoResponse(FiveNote $five)
    {
        $this->resetState();
        $this->evidenceKey++;
        $this->five = $five;

        if ($this->five) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'finishD5Modal',
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
        if ($this->isSaving || !$this->five) {
            return;
        }

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
        if ($this->isSaving) {
            return;
        }

        $this->isSaving = true;

        if (!$this->five) {
            $this->isSaving = false;
            return;
        }

        if (!$this->hasEvidence) {
            $this->finish();
            return;
        }

        $this->emitTo('files.evidence.upload-evidence', 'saveEvidences', $this->five->id);
    }

    public function evidenceSaved()
    {
        $this->finish();
    }


    public function finish()
    {
        DB::beginTransaction();

        try {
            $fromStage = app(D5WorkflowService::class)->currentStage($this->five);

            $this->five->done(null, $this->observations);
            app(D5WorkflowService::class)->onPartnerCompleted($this->five, $fromStage, auth()->id());

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
            $this->isSaving = false;

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

    public function savePassiveDetails(): void
    {
        if (!$this->five || !$this->five->isPassive) {
            return;
        }

        $this->validate([
            'five.description' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();

        try {
            $this->five->save();

            DB::commit();

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Detalhes do passivo atualizados com sucesso!',
            ]);

            $this->editingDescription = false;
        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'Falha ao atualizar os detalhes do passivo.',
            ]);
        }
    }

    private function resetState(): void
    {
        $this->reset(['five', 'observations', 'hasEvidence', 'editingDescription', 'isSaving']);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');
    }

    public function startEditDescription(): void
    {
        if ($this->five?->isPassive) {
            $this->editingDescription = true;
        }
    }

    public function cancelEditDescription(): void
    {
        $this->editingDescription = false;
        $this->resetValidation('five.description');
        $this->resetErrorBag('five.description');
    }

    public function clearAll()
    {
        $this->resetState();
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_component');

    }

    public function render()
    {
        return view('livewire.partner.five-note.actions.finish-d5');
    }
}
