<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Note;
use App\Services\D5\D5WorkReportAssociationResolver;

/**
 * Linhas da Lista para Pagamento: a mesma regra no Despacho e em Serviços (a lista de Serviços é o reflexo do Despacho; a única diferença
 * é que ela só auto atribui). Uma linha por Informe elegível, por D5 sem informe e por Parcial em medição, cada uma com o contexto
 * (`payment_context_key`, `dispatch_work_report_id`, `dispatch_partial_id`, `dispatch_five_note_id`) que o avaliador de bloqueio e a
 * atribuição leem. Divergir daqui é o que fazia a Medição de uma Parcial bloquear outra da mesma Nota.
 */
trait BuildsPaymentRows
{
    private function expandPaymentRows($notes)
    {
        return $notes->flatMap(function (Note $note) {
            $workForms = $note->relationLoaded('WorkForms')
                ? $note->WorkForms
                : collect([$note->WorkForm])->filter();

            // Retorno D5 é Ciclo 2 do informe: não depende das operações do
            // Ciclo 1, mas precisa manter work_report_id e five_note_id.
            $eligibleWorkForms = $workForms
                ->filter(function ($workForm) use ($note) {
                    $fiveNote = $workForm->relationLoaded('FiveNote') ? $workForm->FiveNote : null;
                    $fiveNote ??= $this->legacyD5ForWorkReport($note, $workForm);

                    // D5 devolvida nunca pode vazar para a fila por causa das
                    // operações ainda válidas do Informe.
                    if ($fiveNote?->returned) {
                        return false;
                    }

                    return $this->workReportEligibleForPayment($workForm)
                        || $fiveNote?->isEligibleForPaymentReturn();
                })
                ->values();

            // Informes (Final e Parcial) são independentes: cada Parcial em
            // medição gera a própria linha, coexistindo com a do Informe Final.
            $partialRows = $this->partialPaymentRows($note);

            if ($eligibleWorkForms->isEmpty()) {
                if ($workForms->isNotEmpty() && !$note->FiveNote?->needsPaymentAction()) {
                    return $partialRows->all();
                }

                $note->setAttribute('payment_context_key', (string) $note->id);

                // D5 sem informe (ex.: passiva) é despachada pela identidade da própria D5.
                if ($note->FiveNote?->needsPaymentAction()) {
                    $note->setAttribute('dispatch_five_note_id', (int) $note->FiveNote->id);
                }

                return $partialRows->isNotEmpty()
                    ? $partialRows->all()
                    : [$note];
            }

            $informeRows = $eligibleWorkForms->map(function ($workForm) use ($note) {
                $row = clone $note;
                // Uma D5 antiga sem Informe só acompanha este Ciclo 1 quando o
                // resolvedor temporal a aponta inequivocamente para este Informe.
                // Não cria segunda linha e nunca escolhe a D5 por ordem arbitrária.
                $fiveNote = $workForm->relationLoaded('FiveNote') ? $workForm->FiveNote : null;
                $fiveNote ??= $this->legacyD5ForWorkReport($note, $workForm);

                $row->setRelation('FiveNote', $fiveNote);
                $row->setRelation('WorkForm', $workForm);
                $row->setRelation('WorkForms', collect([$workForm]));
                $row->setAttribute('payment_context_key', $note->id . ':' . $workForm->id);
                $row->setAttribute('payment_work_report_id', (int) $workForm->id);
                $row->setAttribute('dispatch_work_report_id', (int) $workForm->id);
                $row->setAttribute('dispatch_five_note_id', $fiveNote?->id);

                return $row;
            });

            // D5 legada só vira uma demanda própria quando não existe Informe
            // Final candidato. Com Informe elegível ela integra o Ciclo 1 e não
            // pode duplicar a mesma Nota na fila.
            if ($informeRows->isEmpty() && $note->FiveNote?->needsPaymentAction()) {
                $legacyRow = clone $note;
                $legacyRow->setAttribute('payment_context_key', (string) $note->id);
                $informeRows = collect([$legacyRow])->concat($informeRows);
            }

            return $informeRows->concat($partialRows);
        })->values();
    }

    /**
     * Uma linha por Parcial em medição. Parcial não tem D5 nem Informe Final:
     * não herda a D5 nem o WorkForm da Nota.
     */
    private function partialPaymentRows(Note $note): \Illuminate\Support\Collection
    {
        if (!$note->relationLoaded('Partials')) {
            return collect();
        }

        return $note->Partials
            ->where('allow', true)
            ->where('deny', false)
            ->where('supervision', true)
            ->where('payment', false)
            ->sortByDesc(fn ($item) => [$item->created_at?->timestamp ?? 0, $item->id])
            ->map(function ($partial) use ($note) {
                $row = clone $note;
                $row->setAttribute('payment_context_key', $note->id . ':0:p' . $partial->id);
                $row->setAttribute('dispatch_partial_id', (int) $partial->id);
                $row->setRelation('FiveNote', null);
                $row->setRelation('WorkForm', null);
                $row->setRelation('WorkForms', collect());

                return $row;
            })
            ->values();
    }

    private function workReportEligibleForPayment($workForm): bool
    {
        if (!$workForm) {
            return false;
        }

        $orders = $workForm->relationLoaded('Orders')
            ? $workForm->Orders
            : $workForm->Orders()->with('Operations')->get();

        $candidates = app(\App\Services\Payment\WorkReportPaymentCandidateQuery::class);

        return $orders->contains(fn ($order) => $candidates->orderIsEligible($order));
    }

    /**
     * Fallback exclusivamente de leitura para D5s antigas que ainda não carregam
     * work_report_id. A escrita definitiva ocorre no DispatchWorkflowService,
     * dentro da transação do despacho.
     */
    private function legacyD5ForWorkReport(Note $note, $workForm): ?\App\Models\FiveNote
    {
        if (!$workForm || !$note->relationLoaded('FiveNotes')) {
            return null;
        }

        $matches = $note->FiveNotes
            ->filter(fn ($five) => !$five->work_report_id
                && !$five->isPassive
                && $five->needsPaymentAction()
                && app(D5WorkReportAssociationResolver::class)->closestFor($five)?->is($workForm))
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
