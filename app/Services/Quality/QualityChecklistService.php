<?php

namespace App\Services\Quality;

use App\Enum\{QualityStageLevel};
use App\Models\{QualityChecklistEntry, QualityChecklistRun, QualityChecklistTemplate, QualityProcess, QualityStage, User};
use Illuminate\Support\Facades\DB;

class QualityChecklistService
{
    public function openN1Run(QualityProcess $process, QualityStage $stage, User $user): QualityChecklistRun
    {
        return DB::transaction(function () use ($process, $stage, $user) {
            $run = QualityChecklistRun::query()->firstOrCreate([
                'quality_process_id' => $process->id, 'quality_stage_id' => $stage->id,
            ], [
                'quality_checklist_template_id' => QualityChecklistTemplate::query()->where('active', true)->latest('id')->value('id'),
                'round_number'                  => $process->round_number, 'level' => QualityStageLevel::N1, 'created_by' => $user->id,
            ]);

            if (!$run->Entries()->exists()) {
                $template = QualityChecklistTemplate::query()->with(['Sections.Items' => fn ($q) => $q->where('active', true)])->findOrFail($run->quality_checklist_template_id);

                foreach ($template->Sections as $section) {
                    foreach ($section->Items as $item) {
                        $run->Entries()->create(['template_item_id' => $item->id, 'section_name' => $section->name, 'code' => $item->code, 'question' => $item->question, 'severity' => $item->severity_sp, 'severity_qa' => $item->severity_qa, 'sort_order' => $item->sort_order]);
                    }
                }
            }

            return $run->fresh(['Entries']);
        });
    }

    public function save(QualityChecklistRun $run, array $entries): void
    {
        DB::transaction(function () use ($run, $entries) {
            foreach ($entries as $id => $data) {
                $entry  = $run->Entries()->lockForUpdate()->findOrFail($id);
                $status = $data['status'] ?? $entry->status;

                if (!in_array($status, ['PENDING', 'OK', 'NA', 'ISSUE'], true)) {
                    continue;
                }

                if ($status === 'ISSUE' && trim((string) ($data['observation'] ?? '')) === '') {
                    throw new QualityWorkflowException("Descreva o apontamento do item {$entry->code}.");
                }
                $entry->update(['status' => $status, 'observation' => $status === 'ISSUE' ? trim($data['observation']) : null]);
            }
        });
    }

    public function markSentForStage(QualityProcess $process, QualityStage $stage): void
    {
        QualityChecklistRun::query()->where('quality_process_id', $process->id)->where('quality_stage_id', $stage->id)->update(['sent_at' => now()]);
    }

    public function latestForDesigner(QualityProcess $process): ?QualityChecklistRun
    {
        return QualityChecklistRun::query()->where('quality_process_id', $process->id)->whereNotNull('sent_at')->with('Entries')->latest('id')->first();
    }

    public function resolve(QualityChecklistEntry $entry, User $user, string $resolution): void
    {
        if ($entry->status !== 'ISSUE') {
            throw new QualityWorkflowException('Este apontamento não está disponível para resolução.');
        }
        $entry->update(['status' => 'RESOLVED', 'resolution' => trim($resolution), 'resolved_by' => $user->id, 'resolved_at' => now()]);
    }

    public function validate(QualityChecklistEntry $entry, User $user, bool $accepted): void
    {
        if ($entry->status !== 'RESOLVED') {
            throw new QualityWorkflowException('Somente apontamentos resolvidos podem ser validados.');
        }
        $entry->update(['status' => $accepted ? 'VALIDATED' : 'ISSUE', 'validated_by' => $user->id, 'validated_at' => now()]);
    }

    /** Não deixa o N1 aprovar enquanto houver apontamentos enviados sem validação da correção. */
    public function assertNoOpenSentIssues(QualityProcess $process): void
    {
        $open = QualityChecklistEntry::query()
            ->whereIn('status', ['ISSUE', 'RESOLVED'])
            ->whereHas('Run', fn ($q) => $q->where('quality_process_id', $process->id)->whereNotNull('sent_at'))
            ->exists();

        if ($open) {
            throw new QualityWorkflowException('Existem apontamentos do checklist aguardando correção ou validação.');
        }
    }
}
