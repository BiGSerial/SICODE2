<?php

namespace App\Services\Payment;

use App\Models\{FiveNote, Note, Partial, Service, WorkReport};
use Illuminate\Support\Str;

/**
 * Porta única para decidir se um contexto pode entrar no despacho de
 * Medição/Pagamento. O DispatchModal permanece genérico e só o consulta para
 * este serviço.
 */
class MeasurementEligibilityService
{
    public function appliesTo(Service $service): bool
    {
        return in_array(Str::lower(Str::ascii((string) $service->service)), ['pagamento', 'medicao'], true);
    }

    /** @param array{note_id:int,work_report_id?:int|null,partial_id?:int|null,five_note_id?:int|null} $context */
    public function modalContextIsAllowed(array $context): bool
    {
        $noteId       = (int) ($context['note_id'] ?? 0);
        $workReportId = (int) ($context['work_report_id'] ?? 0);
        $partialId    = (int) ($context['partial_id'] ?? 0);
        $fiveNoteId   = (int) ($context['five_note_id'] ?? 0);

        if ($noteId < 1 || ($workReportId && $partialId)) {
            return false;
        }

        if ($workReportId) {
            return WorkReport::query()->whereKey($workReportId)->where('note_id', $noteId)->where('canceled', false)->exists();
        }

        if ($partialId) {
            return Partial::query()->whereKey($partialId)->where('note_id', $noteId)->exists();
        }

        return $fiveNoteId > 0 && $this->passiveD5ReturnForNote(Note::find($noteId), $fiveNoteId) !== null;
    }

    /**
     * Compatibilidade para emissores legados do modal que ainda enviam somente
     * note_id. Recupera o contexto apenas quando existe um único Parcial ativo;
     * nunca escolhe o primeiro entre várias demandas da mesma Nota.
     */
    public function resolveUniquePartialContext(int $noteId): ?int
    {
        $partials = Partial::query()
            ->where('note_id', $noteId)
            ->where('allow', true)
            ->where('deny', false)
            ->where('supervision', true)
            ->where('payment', false)
            ->limit(2)
            ->pluck('id');

        return $partials->count() === 1 ? (int) $partials->first() : null;
    }

    public function resolveUniqueFinalContext(int $noteId): ?int
    {
        $reports = WorkReport::query()
            ->where('note_id', $noteId)
            ->where('canceled', false)
            ->with('Orders.Operations')
            ->limit(2)
            ->get()
            ->filter(fn (WorkReport $report) => $report->Orders->contains(
                fn ($order) => app(WorkReportPaymentCandidateQuery::class)->orderIsEligible($order)
            ))
            ->values();

        return $reports->count() === 1 ? (int) $reports->first()->id : null;
    }

    /** @return array{work_report_id:int,five_note_id:int}|null */
    public function resolveUniqueD5ReturnContext(int $noteId): ?array
    {
        $matches = FiveNote::query()
            ->where('note_id', $noteId)
            ->whereNotNull('work_report_id')
            ->eligibleForPaymentReturn()
            ->limit(2)
            ->get(['id', 'work_report_id']);

        if ($matches->count() !== 1) {
            return null;
        }

        $fiveNote = $matches->first();

        return ['work_report_id' => (int) $fiveNote->work_report_id, 'five_note_id' => (int) $fiveNote->id];
    }

    /** @return array{five_note_id:int}|null */
    public function resolveUniquePassiveD5ReturnContext(int $noteId): ?array
    {
        $matches = FiveNote::query()
            ->where('note_id', $noteId)
            ->whereNull('work_report_id')
            ->where('isPassive', true)
            ->eligibleForPaymentReturn()
            ->limit(2)
            ->get(['id']);

        return $matches->count() === 1 ? ['five_note_id' => (int) $matches->first()->id] : null;
    }

    public function passiveD5ReturnForNote(?Note $note, ?int $fiveNoteId = null): ?FiveNote
    {
        if (!$note) {
            return null;
        }

        $query = FiveNote::query()
            ->where('note_id', $note->id)
            ->whereNull('work_report_id')
            ->where('isPassive', true)
            ->eligibleForPaymentReturn();

        if ($fiveNoteId) {
            return $query->whereKey($fiveNoteId)->first();
        }

        // Sem ID explícito não há identidade segura. Só toleramos o legado se
        // existir uma única D5 passiva elegível para a Nota.
        $matches = $query->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
