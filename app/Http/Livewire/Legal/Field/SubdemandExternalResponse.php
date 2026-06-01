<?php

namespace App\Http\Livewire\Legal\Field;

use App\Enum\LegalDemandSubdemandStatus;
use App\Models\Legal\LegalDemandComment;
use App\Models\Legal\LegalDemandFile;
use App\Models\Legal\LegalDemandSubdemand;
use App\Services\Legal\LegalDemandSubdemandWorkflowService;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubdemandExternalResponse extends Component
{
    use WithFileUploads;

    public string $token;
    public LegalDemandSubdemand $subdemand;
    public string $executorName = '';
    public string $comment = '';
    public $uploadFiles = [];
    public array $uploadNames = [];

    public function mount(string $token): void
    {
        $this->token = $token;

        $subdemand = app(LegalDemandSubdemandWorkflowService::class)->resolveExternalByToken($token);
        if (!$subdemand) {
            redirect()->route('legal.external.expired');
            return;
        }

        $this->subdemand = $subdemand->load(['demand.legalCase', 'comments.user']);
        $this->executorName = (string) data_get($this->subdemand->metadata ?? [], 'external_contact_name', '');
    }

    public function updatedUploadFiles(): void
    {
        if (!is_array($this->uploadFiles)) {
            $this->uploadFiles = [$this->uploadFiles];
        }

        $this->validate([
            'uploadFiles.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        foreach ($this->uploadFiles as $index => $file) {
            if (!isset($this->uploadNames[$index]) || trim((string) $this->uploadNames[$index]) === '') {
                $this->uploadNames[$index] = (string) $file->getClientOriginalName();
            }
        }
    }

    public function submit(): void
    {
        $this->validate([
            'executorName' => 'required|string|min:3|max:120',
            'comment' => 'nullable|string|max:2000',
        ]);

        $comment = trim($this->comment);
        $hasFiles = !empty($this->uploadFiles);
        if ($comment === '' && !$hasFiles) {
            $this->addError('comment', 'Envie um comentário ou anexo.');
            return;
        }

        $metadata = (array) ($this->subdemand->metadata ?? []);
        $metadata['external_executor_name'] = trim($this->executorName);
        $metadata['external_last_response_at'] = now()->toDateTimeString();
        $this->subdemand->metadata = $metadata;
        $this->subdemand->save();

        if ($comment !== '') {
            LegalDemandComment::create([
                'legal_demand_id' => $this->subdemand->legal_demand_id,
                'legal_demand_subdemand_id' => $this->subdemand->id,
                'comment' => $comment,
                'visibility' => 'shared',
                'user_id' => null,
            ]);
        }

        foreach ($this->uploadFiles as $index => $file) {
            $customName = trim((string) ($this->uploadNames[$index] ?? ''));
            $originalName = (string) $file->getClientOriginalName();
            if ($customName === '') {
                $customName = $originalName;
            }

            $customName = preg_replace('/[\\\\\/]+/', '-', $customName) ?: $originalName;
            $extension = strtolower((string) $file->getClientOriginalExtension());
            if ($extension !== '' && !str_ends_with(strtolower($customName), '.' . $extension)) {
                $customName .= '.' . $extension;
            }

            $path = $file->storeAs("legal/demands/{$this->subdemand->legal_demand_id}/external/subdemand-{$this->subdemand->id}", $customName, 'public');

            LegalDemandFile::create([
                'legal_demand_id' => $this->subdemand->legal_demand_id,
                'legal_demand_subdemand_id' => $this->subdemand->id,
                'uploaded_by' => null,
                'file_name' => basename($path),
                'original_name' => $customName,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'visibility' => 'shared',
            ]);
        }

        app(LegalDemandSubdemandWorkflowService::class)->transitionStatus(
            subdemand: $this->subdemand,
            actor: null,
            toStatus: LegalDemandSubdemandStatus::AGUARDANDO_RETORNO,
            description: 'Retorno registrado por link externo.',
            payload: ['external' => true]
        );

        $this->comment = '';
        $this->uploadFiles = [];
        $this->uploadNames = [];
        $this->subdemand->refresh()->load(['demand.legalCase', 'comments.user']);

        session()->flash('success', 'Retorno enviado com sucesso.');
    }

    public function render()
    {
        return view('livewire.legal.field.subdemand-external-response');
    }
}
