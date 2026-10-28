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

            $this->carryOverFromPreviousRun($process, $run);

            return $run->fresh(['Entries']);
        });
    }

    /**
     * Um checklist novo no mesmo ciclo parte do que já foi apontado, respondido e validado no anterior:
     * o checklist mais recente é o único que usuário e N1 enxergam, então nada pode ficar para trás.
     */
    private function carryOverFromPreviousRun(QualityProcess $process, QualityChecklistRun $run): void
    {
        $previous = QualityChecklistRun::query()->where('quality_process_id', $process->id)->whereNotNull('sent_at')
            ->whereIn('quality_stage_id', $this->cycleStageIds($process))->where('id', '<', $run->id)->latest('id')->with('Entries')->first();

        if (!$previous) {
            return;
        }

        $carried = $previous->Entries->whereIn('status', ['ISSUE', 'RESOLVED', 'VALIDATED'])->keyBy('code');

        foreach ($run->Entries()->whereIn('code', $carried->keys())->where('status', 'PENDING')->get() as $entry) {
            $old = $carried[$entry->code];
            $entry->update($old->only(['status', 'observation', 'resolution', 'resolved_by', 'resolved_at', 'validated_by', 'validated_at']));
        }
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
        return QualityChecklistRun::query()->where('quality_process_id', $process->id)->whereNotNull('sent_at')
            ->whereIn('quality_stage_id', $this->cycleStageIds($process))->with('Entries')->latest('id')->first();
    }

    /** Apontamentos de um ciclo não podem vazar para o outro (a numeração de rodadas recomeça por ciclo). */
    private function cycleStageIds(QualityProcess $process): array
    {
        return $process->Stages()->where('type', $process->phase->value)->pluck('id')->all();
    }

    public function resolve(QualityChecklistEntry $entry, User $user, string $resolution): void
    {
        if ($entry->status !== 'ISSUE') {
            throw new QualityWorkflowException('Este apontamento não está disponível para resolução.');
        }
        $entry->update(['status' => 'RESOLVED', 'resolution' => trim($resolution), 'resolved_by' => $user->id, 'resolved_at' => now()]);
    }

    /** O usuário desfaz a resposta enquanto o N1 ainda não validou. */
    public function reopenResolution(QualityChecklistEntry $entry): void
    {
        if ($entry->status !== 'RESOLVED') {
            throw new QualityWorkflowException('Somente respostas ainda não validadas podem ser refeitas.');
        }
        $entry->update(['status' => 'ISSUE', 'resolved_by' => null, 'resolved_at' => null]);
    }

    /** Apontamentos enviados ao usuário (somente o que compete a ele): abertos, respondidos e validados. */
    public function entriesForDesigner(QualityProcess $process)
    {
        return $this->latestForDesigner($process)?->Entries->whereIn('status', ['ISSUE', 'RESOLVED', 'VALIDATED'])->sortBy('sort_order')->values() ?? collect();
    }

    public function validate(QualityChecklistEntry $entry, User $user, bool $accepted): void
    {
        if ($entry->status !== 'RESOLVED') {
            throw new QualityWorkflowException('Somente apontamentos resolvidos podem ser validados.');
        }
        $entry->update(['status' => $accepted ? 'VALIDATED' : 'ISSUE', 'validated_by' => $user->id, 'validated_at' => now()]);
    }

    /** Não deixa o N1 aprovar enquanto o checklist atual do ciclo (o último enviado ao usuário) tiver apontamentos sem validação. */
    public function assertNoOpenSentIssues(QualityProcess $process): void
    {
        $run = QualityChecklistRun::query()->where('quality_process_id', $process->id)->whereNotNull('sent_at')
            ->whereIn('quality_stage_id', $this->cycleStageIds($process))->latest('id')->first();

        if ($run && $run->Entries()->whereIn('status', ['ISSUE', 'RESOLVED'])->exists()) {
            throw new QualityWorkflowException('Existem apontamentos do checklist aguardando correção ou validação.');
        }
    }
}
