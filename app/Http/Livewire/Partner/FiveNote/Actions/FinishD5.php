<?php

namespace App\Http\Livewire\Partner\FiveNote\Actions;

use App\Models\{EvidenceFile, FiveNote};
use App\Services\D5\D5WorkflowService;
use App\Services\Files\EvidenceFileService;
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FinishD5 extends Component
{
    use \App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;

    public $five;

    public $hasEvidence = false;

    public $observations;

    public $editingDescription = false;

    public bool $isSaving = false;

    public int $savingStartedAt = 0;

    // Segundos sem resposta do upload/encerramento antes de liberar o botão e avisar o usuário.
    private const SAVING_TIMEOUT_SECONDS = 90;

    public int $evidenceKey = 0;

    public int $partnerEvidencePage = 1;

    public int $inspectionEvidencePage = 1;

    public int $legacyEvidencePage = 1;

    public int $evidencePerPage = 6;

    public string $evidenceTab = 'partner';

    public $origin = 'EMPREITEIRA';

    protected $listeners = [
        'getInfoResponse',
        'hasEvidence',
        'evidenceSaved',
        'evidenceSaveFailed',
        'samuca158012Encerrar' => 'toSave',
    ];

    protected $rules = [
        'five.name'        => 'required|string|max:255',
        'five.description' => 'nullable|string|max:2000',
    ];

    public function getInfoResponse(FiveNote $five)
    {
        $this->authorizePartnerAccess('d5_notes.finish');

        $this->resetState();
        $this->evidenceKey++;
        $this->five = $this->scopedFiveNote($five->id);

        if ($this->five) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'finishD5Modal',
            ]);
        }
    }

    public function dowloadFile(EvidenceFile $file)
    {
        $service = app(EvidenceFileService::class);

        if ($service->exists($file)) {
            return $service->download($file);
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
        $this->authorizePartnerAccess('d5_notes.finish');

        if ($this->isSaving) {
            $this->notifyFailure('Encerramento em andamento', 'A D5 já está sendo encerrada. Aguarde a conclusão.', 'info');

            return;
        }

        if (!$this->five) {
            $this->notifyFailure('D5 não carregada', 'Feche a janela e abra a D5 novamente para encerrar.');

            return;
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            // O erro de campo fica na coluna lateral, fora da área visível do modal.
            // Sem este aviso o botão parecia não fazer nada.
            $this->notifyFailure(
                'Preencha os campos obrigatórios',
                implode('<br>', array_unique($e->validator->errors()->all())),
                'warning',
            );
            $this->dispatchBrowserEvent('finish-d5-validation-failed');

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'        => 'ENCERRAR D5',
            'msg'          => "Você tem certeza que deseja encerrar o D5 {$this->five->note_d5}?",
            'icon'         => 'question',
            'btnOktxt'     => 'Sim, Continue!',
            'btnCanceltxt' => 'Não, Cancele',
            'action'       => 'samuca158012Encerrar',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma D5 foi encerrada.',
        ]);

    }

    private function notifyFailure(string $title, string $html, string $icon = 'error'): void
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => $icon,
            'title'    => $title,
            'html'     => $html,
        ]);
    }

    public function toSave(): void
    {
        if ($this->isSaving) {
            return;
        }

        $this->isSaving        = true;
        $this->savingStartedAt = time();

        Log::info('D5 encerramento: iniciado.', [
            'five_note_id' => $this->five?->id,
            'user_id'      => auth()->id(),
            'has_evidence' => (bool) $this->hasEvidence,
        ]);

        if (!$this->five) {
            $this->isSaving = false;
            $this->notifyFailure('D5 não carregada', 'Feche a janela e abra a D5 novamente para encerrar.');

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
        Log::info('D5 encerramento: evidências salvas.', [
            'five_note_id' => $this->five?->id,
            'elapsed_s'    => $this->savingStartedAt ? time() - $this->savingStartedAt : null,
        ]);

        $this->finish();
    }

    /**
     * Chamado por polling enquanto isSaving: se o upload/encerramento não responder,
     * libera o botão e avisa, em vez de deixar o usuário preso em "Encerrando...".
     */
    public function checkSavingTimeout(): void
    {
        if (!$this->isSaving || !$this->savingStartedAt) {
            return;
        }

        $elapsed = time() - $this->savingStartedAt;

        if ($elapsed < self::SAVING_TIMEOUT_SECONDS) {
            return;
        }

        Log::warning('D5 encerramento: sem resposta, liberando botão.', [
            'five_note_id' => $this->five?->id,
            'user_id'      => auth()->id(),
            'elapsed_s'    => $elapsed,
            'has_evidence' => (bool) $this->hasEvidence,
        ]);

        $this->isSaving        = false;
        $this->savingStartedAt = 0;

        $this->notifyFailure(
            'O encerramento não respondeu',
            'O envio das evidências ou o encerramento demorou mais que o esperado e foi interrompido. Verifique se a D5 foi encerrada antes de tentar novamente; se persistir, informe o suporte (D5 ' . e($this->five?->note_d5) . ').'
        );
    }

    public function evidenceSaveFailed(): void
    {
        $this->isSaving        = false;
        $this->savingStartedAt = 0;

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Não foi possível salvar as evidências',
            'html'     => 'Revise os arquivos anexados e tente encerrar a D5 novamente.',
        ]);
    }

    public function previousEvidencePage(string $group): void
    {
        $property          = $this->evidencePageProperty($group);
        $this->{$property} = max(1, $this->{$property} - 1);
    }

    public function selectEvidenceTab(string $group): void
    {
        $this->evidencePageProperty($group);
        $this->evidenceTab = $group;
    }

    public function nextEvidencePage(string $group, int $lastPage): void
    {
        $property          = $this->evidencePageProperty($group);
        $this->{$property} = min($lastPage, $this->{$property} + 1);
    }

    public function finish(): void
    {
        try {
            $this->authorizePartnerAccess('d5_notes.finish');

            if (!$this->five) {
                throw new \RuntimeException('D5 não encontrada para encerramento.');
            }

            // O nome digitado vive só no estado do componente: capturar antes de
            // recarregar a D5, senão o reload o descarta e a D5 conclui sem responsável.
            $responsible = trim((string) $this->five->name);

            // Recarrega a D5 dentro do tratamento de erro. Antes, uma alteração de
            // escopo/permissão nesta etapa gerava uma exceção sem retorno visual e
            // mantinha o botão de encerramento bloqueado.
            $this->five = $this->scopedFiveNote($this->five->id);

            DB::beginTransaction();
            $fromStage = app(D5WorkflowService::class)->currentStage($this->five);

            $this->five->done($responsible !== '' ? $responsible : null, $this->observations);
            app(D5WorkflowService::class)->onPartnerCompleted($this->five, $fromStage, auth()->id());

            // A conclusao da parceira libera a D5 para a fila da fiscalizacao.
            // Recalcula o status consolidado do informe imediatamente.
            $statusRefresher = app(\App\Services\WorkReports\WorkReportCurrentStatusRefresher::class);

            if ($this->five->work_report_id) {
                $statusRefresher->refresh((int) $this->five->work_report_id);
            } else {
                foreach ($this->five->productions ?? collect() as $production) {
                    $statusRefresher->refreshByProductionId((int) $production->id);
                }
            }

            DB::commit();

            Log::info('D5 encerramento: concluído.', [
                'five_note_id' => $this->five->id,
                'user_id'      => auth()->id(),
                'elapsed_s'    => $this->savingStartedAt ? time() - $this->savingStartedAt : null,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OPERAÇÃO CONCLUIDA',
                'html'     => 'A operação de encerramento do D5 foi concluída com sucesso.',
                'timer'    => 5000,
            ]);

            $this->clearAll();

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->isSaving        = false;
            $this->savingStartedAt = 0;

            Log::error('Falha ao encerrar D5 pelo parceiro.', [
                'five_note_id' => $this->five?->id,
                'user_id'      => auth()->id(),
                'exception'    => $th,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OPERAÇÃO FALHOU',
                'html'     => 'Não foi possível encerrar a D5. Tente novamente. Se o problema continuar, entre em contato com o suporte e informe: ' . class_basename($th) . ' — ' . e(\Illuminate\Support\Str::limit($th->getMessage(), 160)),
            ]);
        }
    }

    public function savePassiveDetails(): void
    {
        $this->authorizePartnerAccess('d5_notes.finish');

        if (!$this->five || !$this->five->isPassive) {
            return;
        }

        $this->five = $this->scopedFiveNote($this->five->id);

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
        $this->reset([
            'five',
            'observations',
            'hasEvidence',
            'editingDescription',
            'isSaving',
            'partnerEvidencePage',
            'inspectionEvidencePage',
            'legacyEvidencePage',
            'evidenceTab',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');
    }

    private function scopedFiveNote(int $id): FiveNote
    {
        $query = FiveNote::query()
            ->whereKey($id)
            ->with([
                'WorkReport:id,selected_final_scopes',
                'productions:id,note_id',
                'productions.WorkReportFlowProductions:id,production_id,work_report_id,final_scope,is_current',
            ]);

        $this->applyPartnerCompanyScope($query);

        return $query->firstOrFail();
    }

    private function evidencePageProperty(string $group): string
    {
        return match ($group) {
            'partner'    => 'partnerEvidencePage',
            'inspection' => 'inspectionEvidencePage',
            'legacy'     => 'legacyEvidencePage',
            default      => throw new \InvalidArgumentException('Grupo de evidências inválido.'),
        };
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
