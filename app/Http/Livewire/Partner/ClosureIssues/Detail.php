<?php

namespace App\Http\Livewire\Partner\ClosureIssues;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Closure\{ClosureIssue, ClosureWorkMessage};
use App\Models\Company;
use App\Services\Closure\{ClosureIssueWorkflowException, ClosureIssueWorkflowService};
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\{Component, WithFileUploads};

/**
 * Pendência de Encerramento vista pela parceira: lê TUDO o que o Encerramento pediu (descrição, exigências, motivo da devolução,
 * histórico com datas e tempo entre etapas) e devolve um retorno rico: resposta por exigência + mensagem + evidências.
 * O superadm só observa (somente leitura): não responde em nome da parceira.
 */
class Detail extends Component
{
    use AuthorizesPartnerAccess;
    use WithFileUploads;

    public int $issueId;

    /** @var array<int, array{status: string, note: string}> resposta por exigência (índice = posição da exigência) */
    public array $answers = [];

    public string $message = '';

    public $attachments = [];

    public function mount(int $issueId): void
    {
        $this->authorizePartnerAccess('closure.issues.list');
        $this->issueId = $issueId;

        $issue = $this->issue();

        foreach (array_values($issue->requirements ?? []) as $position => $_) {
            $this->answers[$position] = ['status' => '', 'note' => ''];
        }
    }

    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'xlsx', 'xls', 'csv', 'doc', 'docx', 'txt', 'zip'];

    private const MAX_KB = 10240;

    /** Valida cada arquivo assim que chega: o que não serve é descartado na hora, com o motivo, em vez de só falhar no envio. */
    public function updatedAttachments(): void
    {
        $this->resetErrorBag('attachments.*');
        $kept = [];

        foreach (is_array($this->attachments) ? $this->attachments : [] as $file) {
            $name = $file->getClientOriginalName();

            if (!in_array(strtolower($file->getClientOriginalExtension()), self::ALLOWED_EXTENSIONS, true)) {
                $this->addError('attachments.' . count($kept), "“{$name}” não foi anexado: formato não permitido (use PDF, imagem, Excel, Word, CSV, TXT ou ZIP).");
            } elseif ($file->getSize() > self::MAX_KB * 1024) {
                $this->addError('attachments.' . count($kept), "“{$name}” não foi anexado: passa de 10 MB (" . number_format($file->getSize() / 1048576, 1, ',', '.') . ' MB).');
            } else {
                $kept[] = $file;
            }
        }

        $this->attachments = $kept;
    }

    public function removeAttachment(int $index): void
    {
        $files = is_array($this->attachments) ? $this->attachments : [];
        unset($files[$index]);
        $this->attachments = array_values($files);
    }

    public function submit(): void
    {
        $this->authorizePartnerAccess('closure.issues.respond');
        abort_if($this->observing(), 403);

        $issue = $this->issue();

        abort_unless(in_array($issue->status, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], true), 422);

        $this->validate([
            'message'       => 'nullable|string|max:5000',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,webp,xlsx,xls,csv,doc,docx,txt,zip|max:10240',
        ], [
            'attachments.*.mimes' => 'Formato de arquivo não permitido.',
            'attachments.*.max'   => 'Cada arquivo pode ter no máximo 10 MB.',
        ]);

        try {
            app(ClosureIssueWorkflowService::class)->respond(
                $issue,
                $this->responseCompany($issue),
                auth()->user(),
                $this->message,
                $issue->requirements ? $this->answers : null,
                is_array($this->attachments) ? $this->attachments : [],
            );
        } catch (ClosureIssueWorkflowException $exception) {
            $this->addError('form', $exception->getMessage());

            return;
        }

        $this->reset('message', 'attachments');
        session()->flash('success', 'Retorno enviado. O Encerramento foi avisado e vai validar.');
    }

    /** Excel do detalhe desta pendência (pedido, exigências com resposta e histórico). */
    public function exportIssue()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $issue = $this->issue();
        $issue->loadMissing(['Order.Note', 'Category', 'Subcategory', 'Events.Actor']);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Closure\ClosureIssueDetailExport('Pendência #' . $issue->id, app(\App\Services\Closure\ClosurePartnerExporter::class)->issueDetailSheets($issue)),
            'pendencia-' . $issue->id . '.xlsx',
        );
    }

    public function observing(): bool
    {
        return (bool) auth()->user()?->superadm;
    }

    public function render()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $issue = $this->issue();
        $issue->loadMissing(['Order.Note', 'Category', 'Subcategory', 'DestinationCompany', 'Events.Actor']);

        $messages = ClosureWorkMessage::query()
            ->with(['Author:id,name', 'EvidenceFiles'])
            ->visibleToPartner()
            ->where('closure_issue_id', $issue->id)
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $waiting    = in_array($issue->status, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], true);
        $lastReply  = $issue->Events->where('type', 'RESPONDED')->last();
        $lastReturn = $issue->status === ClosureIssue::STATUS_RETURNED ? $issue->Events->where('type', 'RETURNED_FOR_CORRECTION')->last() : null;

        return view('livewire.partner.closure-issues.detail', [
            'issue'      => $issue,
            'messages'   => $messages,
            'files'      => $messages->flatMap(fn (ClosureWorkMessage $m) => $m->EvidenceFiles->map(fn ($file) => ['file' => $file, 'by' => $m->Author?->name, 'at' => $m->created_at])),
            'waiting'    => $waiting,
            'canRespond' => $waiting && !$this->observing() && PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'),
            'observing'  => $this->observing(),
            'lastReply'  => $lastReply,
            'replyFiles' => $lastReply ? \App\Models\EvidenceFile::query()->where('origin', ClosureIssueWorkflowService::REPLY_ORIGIN_PREFIX . $lastReply->id)->orderBy('id')->get() : collect(),
            'lastReturn' => $lastReturn,
        ]);
    }

    private function issue(): ClosureIssue
    {
        $query = ClosureIssue::query();
        $this->applyPartnerCompanyScope($query, 'destination_company_id');

        return $query->whereKey($this->issueId)->firstOrFail();
    }

    private function responseCompany(ClosureIssue $issue): Company
    {
        $user    = auth()->user();
        $visible = $user->superadm ? null : PartnerAccessGate::visibleCompanyIdsFor($user);

        return Company::query()
            ->when($visible !== null, fn ($query) => $query->whereIn('id', $visible))
            ->whereKey($issue->destination_company_id)
            ->firstOrFail();
    }
}
