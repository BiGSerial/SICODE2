<?php

namespace App\Http\Livewire\Quality;

use App\Enum\{QualityEventType, QualityProcessStatus};
use App\Models\QualityProcess;
use App\Services\Quality\{QualityWorkflowException, QualityWorkflowService};
use Livewire\Component;

/** Discussão N1 ↔ N2 da obra: conversa com avatares, envio sem recarregar a página e polling. */
class ProcessChat extends Component
{
    public int $processId;

    public string $text = '';

    public string $visibility = 'INTERNAL';

    public int $limit = 30;

    public function mount(int $processId): void
    {
        $this->processId = $processId;
        $this->process();
    }

    private function process(): QualityProcess
    {
        $process = QualityProcess::findOrFail($this->processId);
        abort_unless(auth()->user()->can('view', $process), 403);

        return $process;
    }

    public function loadOlder(): void
    {
        $this->limit += 30;
    }

    public function send(QualityWorkflowService $workflow): void
    {
        $process = $this->process();
        $this->resetErrorBag();
        $this->validate(['text' => ['required', 'string', 'max:5000'], 'visibility' => ['in:INTERNAL,ALL']], ['text.required' => 'Escreva a mensagem.']);

        try {
            $workflow->addComment($process, auth()->user(), $this->text, $this->visibility);
        } catch (QualityWorkflowException $e) {
            $this->addError('text', $e->getMessage());

            return;
        }

        $this->reset('text');
        $this->dispatchBrowserEvent('quality-chat-sent');
    }

    public function render()
    {
        $process  = $this->process();
        $messages = $process->Events()->reorder()->with('Actor')->where('type', QualityEventType::COMMENT_ADDED->value)->orderByDesc('id');
        $total    = (clone $messages)->count();

        return view('livewire.quality.process-chat', [
            'messages' => $messages->limit($this->limit)->get()->reverse()->values(),
            'total'    => $total,
            'canPost'  => $process->status === QualityProcessStatus::ACTIVE && auth()->user()->can('comment', $process),
        ]);
    }
}
