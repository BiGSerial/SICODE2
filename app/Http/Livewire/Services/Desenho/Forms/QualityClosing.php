<?php

namespace App\Http\Livewire\Services\Desenho\Forms;

use App\Enum\{QualityEventType, QualityProcessState, QualityStageKind, QualityStageType};
use App\Helpers\SelectOptions;
use App\Models\{File, QualityProcess, QualityStage};
use App\Services\Quality\{QualityActivities, QualityWorkflowException, QualityWorkflowService, SurveyInformRules};
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Formulário DEDICADO para atividades originadas pela Qualidade (nunca o encerramento normal).
 * 1º ciclo: informe de encerramento do Levantamento. 2º ciclo: ordens do orçamento (Desenho).
 * O usuário sempre devolve a rodada ao N1. Os arquivos são associados à atividade como no fluxo normal.
 */
class QualityClosing extends Component
{
    public bool $viewForm = false;

    public ?int $productionId = null;

    public ?int $processId = null;

    public ?int $stageId = null;

    public array $files = [];

    public array $selectedFileIds = [];

    public string $observation = '';

    public bool $awaitingFiles = false;

    /** Somente leitura: o usuário consulta os detalhes sem iniciar a finalização. */
    public bool $detailsOnly = false;

    // Informe do Levantamento (1º ciclo)
    public string $conclusion = '';

    public string $postes = '';

    public string $doe = '';

    public string $ma = '';

    public bool $cadastro = false;

    public string $postesC = '';

    public string $info = '';

    /** 2º ciclo: [['order_number' => '', 'total_cost' => '', 'company_cost' => '', 'client_cost' => ''], ...] */
    public array $orders = [];

    protected $listeners = [
        'openQualityClosing',
        'openQualityDetails',
        'savedFiles' => 'afterFilesSaved',
        'continue'   => 'afterFilesSaved',
    ];

    /** Abre só para LEITURA: contexto, orientações, devoluções, comentários, dados da Nota e envios anteriores. */
    public function openQualityDetails(int $productionId): void
    {
        $this->openQualityClosing($productionId, true);
    }

    /** Do modo de leitura para o formulário de finalização. */
    public function startFinalization(): void
    {
        $this->detailsOnly = false;
    }

    public function openQualityClosing(int $productionId, bool $detailsOnly = false): void
    {
        $process = QualityProcess::query()->where('production_id', $productionId)->active()->first();
        $stage   = $process?->Stages()->where('kind', QualityStageKind::EXECUTION->value)->whereIn('status', ['PENDING', 'IN_PROGRESS'])->reorder('id', 'desc')->first();

        if (!$process || $process->state !== QualityProcessState::AWAITING_DESIGNER || !$stage || (string) $stage->assigned_user_id !== (string) Auth::id()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ATIVIDADE INDISPONÍVEL',
                'html'     => 'Esta atividade da Qualidade não está despachada para você nesta rodada.',
                'timer'    => 3500,
            ]);

            return;
        }

        $this->reset(['observation', 'orders', 'selectedFileIds', 'files', 'awaitingFiles', 'conclusion', 'postes', 'doe', 'ma', 'cadastro', 'postesC', 'info']);
        $this->resetErrorBag();
        $this->productionId = $productionId;
        $this->processId    = $process->id;
        $this->stageId      = $stage->id;
        $this->orders       = $process->phase === QualityStageType::BUDGET ? [$this->emptyOrder()] : [];
        $this->prefillSurvey($process);
        $this->refreshFiles();
        $this->detailsOnly = $detailsOnly;
        $this->viewForm    = true;
        $this->dispatchBrowserEvent('showModal', ['id' => 'quality_closing_form']);
    }

    public function refreshFiles(): void
    {
        $stage = $this->stage();

        if (!$stage) {
            return;
        }

        $since = $stage->dispatched_at ?? $stage->created_at;
        $files = File::query()->where('note_id', $this->process()->note_id)->orderByDesc('created_at')->orderByDesc('id')->get();

        $this->files = $files->map(fn (File $file) => [
            'id'         => $file->id,
            'name'       => $file->stored_name ?? $file->file_name,
            'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
            'is_new'     => $file->created_at && $file->created_at->gte($since),
        ])->all();

        // Somente o que foi anexado nesta rodada vem marcado; o restante é histórico da Nota.
        $this->selectedFileIds = collect($this->files)->where('is_new', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function addOrder(): void
    {
        $this->orders[] = $this->emptyOrder();
    }

    public function removeOrder(int $index): void
    {
        unset($this->orders[$index]);
        $this->orders = array_values($this->orders);
    }

    /** Passo 1: valida o informe e manda salvar os arquivos (como no encerramento normal). */
    public function submit(SurveyInformRules $survey): void
    {
        $process = $this->process();

        if (!$process || !$this->stage()) {
            $this->addError('workflow', 'Atividade da Qualidade não carregada.');

            return;
        }

        $this->resetErrorBag();
        $this->validate(['observation' => ['nullable', 'string', 'max:10000'], 'info' => ['nullable', 'string', 'max:10000']], [], ['observation' => 'observações', 'info' => 'informações adicionais']);

        try {
            if ($process->phase === QualityStageType::PROJECT) {
                $survey->validate($this->surveyPayload());
            }
        } catch (QualityWorkflowException $e) {
            $this->addError('workflow', $e->getMessage());

            return;
        }

        $this->awaitingFiles = true;
        $this->emitTo('files.manager.create-prod-files', 'saveFiles');
    }

    /** Passo 2: arquivos já associados à atividade; registra a rodada e envia ao N1. */
    public function afterFilesSaved(QualityWorkflowService $workflow): void
    {
        if (!$this->awaitingFiles) {
            $this->refreshFiles();

            return;
        }

        $this->awaitingFiles = false;
        $process             = $this->process();
        $this->refreshFiles();

        try {
            $workflow->startExecution($process, Auth::user());
            $workflow->submitExecution(
                $process,
                Auth::user(),
                array_map('intval', $this->selectedFileIds),
                trim($this->observation) ?: null,
                $process->phase === QualityStageType::PROJECT ? ['survey' => $this->surveyPayload()] : ['orders' => $this->orders],
            );
        } catch (QualityWorkflowException $e) {
            $this->addError('workflow', $e->getMessage());

            return;
        }

        $this->viewForm = false;
        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_accomany');
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ENVIADO AO N1',
            'html'     => 'A rodada foi registrada e encaminhada ao N1 para análise.',
            'timer'    => 2800,
        ]);
    }

    private function surveyPayload(): array
    {
        return ['conclusion' => $this->conclusion, 'postes' => $this->postes, 'doe' => $this->doe, 'ma' => $this->ma, 'cadastro' => $this->cadastro, 'postes_c' => $this->postesC, 'info' => $this->info];
    }

    /** Nas rodadas seguintes o informe volta preenchido com o que o usuário enviou antes. */
    private function prefillSurvey(QualityProcess $process): void
    {
        if ($process->phase !== QualityStageType::PROJECT) {
            return;
        }

        $last = $process->Stages()->where('kind', QualityStageKind::EXECUTION->value)->where('status', 'COMPLETED')->reorder('id', 'desc')->first();
        $data = $last?->submission_data['survey'] ?? null;

        if (!$data) {
            return;
        }

        $this->conclusion = (string) $data['conclusion'];
        $this->postes     = (string) $data['postes'];
        $this->doe        = (string) $data['doe'];
        $this->ma         = (string) $data['ma'];
        $this->cadastro   = (bool) $data['cadastro'];
        $this->postesC    = $data['cadastro'] ? (string) $data['postes_c'] : '';
        $this->info       = (string) ($data['info'] ?? '');
    }

    public function close(): void
    {
        $this->viewForm = false;
        $this->dispatchBrowserEvent('hideModal');
    }

    private function process(): ?QualityProcess
    {
        return $this->processId ? QualityProcess::with(['Note', 'Company', 'N1User', 'Production'])->find($this->processId) : null;
    }

    private function stage(): ?QualityStage
    {
        return $this->stageId ? QualityStage::find($this->stageId) : null;
    }

    private function emptyOrder(): array
    {
        return ['order_number' => '', 'total_cost' => '', 'company_cost' => '', 'client_cost' => ''];
    }

    public function render()
    {
        $process = $this->viewForm ? $this->process() : null;

        return view('livewire.services.desenho.forms.quality-closing', [
            'note'        => $process?->Note,
            'history'     => $process ? $process->Stages()->where('kind', QualityStageKind::EXECUTION->value)->where('status', 'COMPLETED')->reorder('id', 'desc')->with('Files.File')->get() : collect(),
            'serviceName' => $process ? app(QualityActivities::class)->serviceFor($process->phase)?->service : null,
            'conclusions' => SelectOptions::getSurveyConclusions(),
            'process'     => $process,
            'stage'       => $process ? $this->stage() : null,
            'rejections'  => $process ? $process->Rejections()->with(['Items.Category', 'Items.Subcategory', 'Author'])->get() : collect(),
            'comments'    => $process ? $process->Events()->where('type', QualityEventType::COMMENT_ADDED->value)->with('Actor')->get()->filter(fn ($event) => ($event->payload['visibility'] ?? 'INTERNAL') === 'ALL') : collect(),
            'forwards'    => $process ? $process->Events()->whereIn('type', [QualityEventType::RETURN_FORWARDED->value, QualityEventType::DESIGNER_ASSIGNED->value])->with('Actor')->get() : collect(),
        ]);
    }
}
