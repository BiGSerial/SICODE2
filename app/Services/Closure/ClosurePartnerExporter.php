<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureIssue, ClosureIssueEvent};
use Illuminate\Support\Collection;

/**
 * Linhas dos Excel das telas da parceira (pendências e histórico de retornos). A coluna de tempo é em dias corridos, só informativa.
 * Uma única fonte de cabeçalhos e rótulos: a tela e o arquivo nunca divergem.
 */
class ClosurePartnerExporter
{
    public const ISSUE_HEADINGS = [
        'Pendência', 'Nota', 'Ordem', 'Categoria', 'Assunto', 'Situação', 'Ciclo', 'Trava a Ordem',
        'Solicitada em', 'Último movimento em', 'Aguardando você há (dias)', 'Descrição', 'Exigências',
    ];

    public const HISTORY_HEADINGS = [
        'Respondida em', 'Respondida por', 'Nota', 'Ordem', 'Pendência', 'Categoria', 'Assunto', 'Ciclo', 'Mensagem enviada',
        'Itens atendidos', 'Itens não se aplicam', 'Itens não consigo atender', 'Anexos', 'Resultado desta resposta', 'Situação atual',
    ];

    /**
     * @param Collection<int, ClosureIssue> $issues com Order.Note, Category, Subcategory e Events
     *
     * @return array<int, array<int, mixed>>
     */
    public function issueRows(Collection $issues): array
    {
        return $issues->map(function (ClosureIssue $issue) {
            $waiting = in_array($issue->status, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], true);
            $since   = $issue->requested_at ?? $issue->opened_at;

            return [
                $issue->id,
                $issue->Order?->Note?->note,
                $issue->Order?->ordem,
                $issue->Category?->name,
                $issue->Subcategory?->name,
                ClosureEventLabels::issueStatusLabel($issue->status),
                $issue->cycle,
                $issue->blocking ? 'Sim' : 'Não',
                $since?->format('d/m/Y H:i'),
                $issue->Events->last()?->created_at?->format('d/m/Y H:i'),
                $waiting && $since ? (int) $since->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null,
                $issue->description,
                collect($issue->requirements ?? [])->map(fn ($text, $i) => ($i + 1) . '. ' . $text)->implode("\n"),
            ];
        })->values()->all();
    }

    /**
     * @param Collection<int, ClosureIssueEvent> $events eventos RESPONDED, com Actor e Issue.{Order.Note, Category, Subcategory, Events}
     *
     * @return array<int, array<int, mixed>>
     */
    public function historyRows(Collection $events): array
    {
        return $events->map(function (ClosureIssueEvent $event) {
            $issue   = $event->Issue;
            $answers = collect($event->meta['answers'] ?? []);

            return [
                $event->created_at?->format('d/m/Y H:i'),
                $event->Actor?->name,
                $issue?->Order?->Note?->note,
                $issue?->Order?->ordem,
                $issue?->id,
                $issue?->Category?->name,
                $issue?->Subcategory?->name,
                $issue?->cycle,
                $event->meta['message'] ?? null,
                $answers->where('status', ClosureIssueWorkflowService::ANSWER_DONE)->count(),
                $answers->where('status', ClosureIssueWorkflowService::ANSWER_NA)->count(),
                $answers->where('status', ClosureIssueWorkflowService::ANSWER_UNABLE)->count(),
                (int) ($event->meta['attachments'] ?? 0),
                self::outcomeOf($event),
                $issue ? ClosureEventLabels::issueStatusLabel($issue->status) : null,
            ];
        })->values()->all();
    }

    /**
     * Abas do Excel do detalhe de uma pendência. `$issue` com Order.Note, Category, Subcategory e Events.Actor.
     *
     * @return array{info: array<int, array<int, mixed>>, items: array<int, array<int, mixed>>, history: array<int, array<int, mixed>>}
     */
    public function issueDetailSheets(ClosureIssue $issue): array
    {
        $info = [
            ['Pendência', $issue->id],
            ['Nota', $issue->Order?->Note?->note],
            ['Ordem', $issue->Order?->ordem],
            ['Categoria', $issue->Category?->name],
            ['Assunto', $issue->Subcategory?->name],
            ['Situação', ClosureEventLabels::issueStatusLabel($issue->status)],
            ['Ciclo', $issue->cycle],
            ['Trava a Ordem', $issue->blocking ? 'Sim' : 'Não'],
            ['Solicitada em', ($issue->requested_at ?? $issue->opened_at)?->format('d/m/Y H:i')],
            ['Descrição', $issue->description],
        ];

        $answers = collect($issue->Events->where('type', 'RESPONDED')->last()?->meta['answers'] ?? [])->keyBy('index');
        $items   = collect($issue->requirements ?? [])->map(fn ($text, $position) => [
            $position + 1,
            $text,
            isset($answers[$position + 1]) ? ClosureIssueWorkflowService::ANSWER_LABELS[$answers[$position + 1]['status']] ?? $answers[$position + 1]['status'] : 'Sem resposta',
            $answers[$position + 1]['note'] ?? null,
        ])->values()->all();

        $previous = null;
        $history  = $issue->Events->map(function (ClosureIssueEvent $event) use (&$previous) {
            $days     = $previous ? (int) $previous->created_at->copy()->startOfDay()->diffInDays($event->created_at->copy()->startOfDay()) : null;
            $previous = $event;

            return [
                $event->created_at?->format('d/m/Y H:i'),
                ClosureEventLabels::issueLabel($event->type),
                $event->Actor?->name,
                $days === null ? null : ($days === 0 ? 'no mesmo dia' : $days . ' dia(s)'),
                $event->meta['message'] ?? null,
            ];
        })->values()->all();

        return ['info' => $info, 'items' => $items, 'history' => $history];
    }

    /** O que aconteceu com ESTA resposta: o evento seguinte da pendência (aceita, devolvida, em validação ou ainda aguardando). */
    public static function outcomeOf(ClosureIssueEvent $response): string
    {
        $next = $response->Issue?->Events->first(fn (ClosureIssueEvent $e) => $e->id > $response->id);

        return match ($next?->type) {
            'RETURNED_FOR_CORRECTION' => 'Devolvida para correção',
            'RESOLVED', 'RELEASED_BY_OPERATOR' => 'Aceita',
            'VALIDATING' => 'Em validação',
            default      => 'Aguardando validação',
        };
    }

    /**
     * @param array<string, int> $counts rótulo => quantidade
     *
     * @return array<int, array<int, mixed>>
     */
    public function summaryRows(string $group, array $counts): array
    {
        $total = array_sum($counts);

        return collect($counts)->map(fn (int $count, string $label) => [$group, $label, $count, $total > 0 ? round($count / $total * 100, 1) : 0])->values()->all();
    }
}
