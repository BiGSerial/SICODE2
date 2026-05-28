<?php

namespace App\Jobs\Reports;

use App\Custom\Notestatus;
use App\Exports\ProjectReview\HistoryListExport;
use App\Models\Production;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportProjectReviewHistoryListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $filters;
    public string $userId;

    public $tries = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200;

    public function __construct(array $filters, string $userId)
    {
        $this->onQueue('exports');
        $this->filters = $filters;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $productions = $this->buildProductions();
            $summaryRows = $this->buildSummaryRows($productions);
            $detailedRows = $this->buildDetailedRows($productions);
            $commentsRows = $this->buildCommentsRows($productions);
            $auditRows = [
                ['Usuário solicitante', $user?->name ?? '---'],
                ['Email', $user?->email ?? '---'],
                ['Data/Hora da solicitação', now()->format('d/m/Y H:i:s')],
                ['Tipo de exportação', 'Análise de Projeto - Histórico'],
            ];
            $stamp = now()->format('YmdHis');
            $filePath = "exports/project_review_history_list_{$stamp}.xlsx";

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new HistoryListExport($summaryRows, $detailedRows, $commentsRows, $auditRows), $filePath, 'local');

            if (!$filePath || !Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação Histórico Análise de Projeto',
                    'Seu arquivo do histórico está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportProjectReviewHistoryListJob falhou', [
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'filters' => $this->filters,
                'attempt' => $this->attempts(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro ao gerar exportação do histórico',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Production>
     */
    private function buildProductions()
    {
        $search = trim((string) ($this->filters['search'] ?? ''));
        $companyId = (string) ($this->filters['company_id'] ?? '');
        $companyIds = collect($this->filters['company_ids'] ?? [])->filter()->values();
        $from = (string) ($this->filters['from'] ?? '');
        $to = (string) ($this->filters['to'] ?? '');

        return Production::query()
            ->with([
                'Note',
                'User',
                'Company',
                'ProjectReviewCycles' => function ($q) {
                    $q->with(['Orders', 'DecidedBy'])->latest('round_number');
                },
                'ProjectReviewMessages.User',
            ])
            ->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW, Production::STATUS_RELEASED_TO_FINISH])
            ->whereHas('ProjectReviewCycles', function ($q) {
                $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED']);
            })
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . $search . '%';
                $q->whereHas('Note', function ($n) use ($term) {
                    $n->where('note', 'like', $term)
                        ->orWhere('numPedido', 'like', $term)
                        ->orWhere('material', 'like', $term);
                });
            })
            ->when($companyId !== '', fn($q) => $q->where('company_id', $companyId))
            ->when($companyIds->isNotEmpty(), fn($q) => $q->whereIn('company_id', $companyIds->all()))
            ->when($from !== '', function ($q) use ($from) {
                $q->whereHas('ProjectReviewCycles', fn($cq) => $cq->whereDate('submitted_at', '>=', $from));
            })
            ->when($to !== '', function ($q) use ($to) {
                $q->whereHas('ProjectReviewCycles', fn($cq) => $cq->whereDate('submitted_at', '<=', $to));
            })
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Production> $productions
     * @return array<int, array<int, mixed>>
     */
    private function buildCommentsRows($productions): array
    {
        $rows = [];

        foreach ($productions as $production) {
            $note = $production->Note->note ?? '---';
            $company = $production->Company->name ?? '---';
            $designer = $production->User->name ?? '---';

            $messages = collect($production->ProjectReviewMessages ?? collect())
                ->sortBy([
                    ['cycle_id', 'asc'],
                    ['created_at', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            foreach ($messages as $message) {
                $author = $message->User->name ?? '---';
                $authorRole = $this->resolveAuthorRole($message->User);
                $cycle = collect($production->ProjectReviewCycles ?? collect())
                    ->firstWhere('id', $message->cycle_id);
                $round = $cycle?->round_number ? 'R' . $cycle->round_number : '---';
                $decision = $this->decisionLabel($cycle?->decision);

                $rows[] = [
                    $note,
                    $company,
                    $designer,
                    $round,
                    $decision,
                    $author,
                    $authorRole,
                    trim((string) ($message->message ?? '')),
                    $message->created_at ? date('d/m/Y H:i:s', strtotime((string) $message->created_at)) : '---',
                ];
            }
        }

        return $rows;
    }

    private function decisionLabel(?string $decision): string
    {
        return match ((string) $decision) {
            'PENDING' => 'Pendente',
            'APPROVED' => 'Aprovado',
            'APPROVED_WITH_REMARKS' => 'Aprovado com ressalvas',
            'REJECTED' => 'Reprovado',
            default => '---',
        };
    }

    private function resolveAuthorRole($user): string
    {
        if (!$user) {
            return '---';
        }

        if ($user->analyst) {
            return 'Analista';
        }

        if ($user->engineer) {
            return 'Engenheiro';
        }

        if ($user->responsible) {
            return 'Responsável';
        }

        if ($user->management) {
            return 'Gerência';
        }

        if ($user->admin || $user->superadm) {
            return 'Administração';
        }

        if ($user->contract) {
            return 'Terceirizado';
        }

        return 'Usuário';
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Production> $productions
     * @return array<int, array<int, mixed>>
     */
    private function buildSummaryRows($productions): array
    {
        return $productions->map(function ($production) {
            $cyclesDesc = collect($production->ProjectReviewCycles)->sortByDesc('round_number')->values();
            $cycle = $cyclesDesc->first();
            $cyclesAsc = collect($production->ProjectReviewCycles)->sortBy('round_number')->values();

            $orders = $cycle?->Orders ?? collect();
            if ($orders->isEmpty()) {
                $orders = $production->ProjectReviewCycles->first(function ($c) {
                    return $c->Orders->count() > 0;
                })?->Orders ?? collect();
            }

            $orders = collect($orders)
                ->sortBy(fn($o) => [(string) ($o->order_number ?? ''), (int) ($o->id ?? 0)])
                ->values();

            $ordersText = $orders->pluck('order_number')->filter()->implode(' | ');
            $totalsText = $orders->map(fn($o) => number_format((float) $o->total_cost, 2, ',', '.'))->implode(' | ');
            $companyCostsText = $orders->map(fn($o) => number_format((float) $o->company_cost, 2, ',', '.'))->implode(' | ');
            $clientCostsText = $orders->map(fn($o) => number_format((float) $o->client_cost, 2, ',', '.'))->implode(' | ');

            $plannedTotal = 0.0;
            $revisedTotal = 0.0;
            $prefixSeries = $this->buildPrefixSeriesFromCycles($cyclesAsc);
            $hasPrefixData = false;

            foreach (['170', '190', '150', '200'] as $prefix) {
                $series = collect($prefixSeries[$prefix] ?? [])->values();
                if ($series->isEmpty()) {
                    continue;
                }

                $hasPrefixData = true;
                $first = (array) $series->first();
                $last = (array) $series->last();
                $plannedTotal += (float) ($first['total'] ?? 0);
                $revisedTotal += (float) ($last['total'] ?? 0);
            }

            if (!$hasPrefixData) {
                $firstCycle = $cyclesAsc->first();
                $lastCycle = $cyclesAsc->last();
                $plannedCosts = $this->sumCycleCostsByPrefixRule(collect($firstCycle?->Orders ?? collect()));
                $revisedCosts = $this->sumCycleCostsByPrefixRule(collect($lastCycle?->Orders ?? collect()));
                $plannedTotal = (float) ($plannedCosts['total'] ?? 0);
                $revisedTotal = (float) ($revisedCosts['total'] ?? 0);
            }

            if ($plannedTotal > 0) {
                $variationPercent = (($revisedTotal - $plannedTotal) / $plannedTotal) * 100;
                if (abs($variationPercent) < 0.00001) {
                    $variationText = 'Sem mudança';
                } else {
                    $variationText = ($variationPercent > 0 ? 'Aumento ' : 'Diminuição ')
                        . number_format(abs($variationPercent), 2, ',', '.')
                        . '%';
                }
            } else {
                $variationText = 'Sem mudança';
            }

            return [
                $production->Note->note ?? '---',
                $production->User->name ?? '---',
                $production->Company->name ?? '---',
                $ordersText !== '' ? $ordersText : '---',
                $totalsText !== '' ? $totalsText : '---',
                $companyCostsText !== '' ? $companyCostsText : '---',
                $clientCostsText !== '' ? $clientCostsText : '---',
                $variationText,
                Notestatus::status((int) $production->status)->status,
                $cycle?->DecidedBy?->name ?? '---',
                $cycle?->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---',
            ];
        })->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Production> $productions
     * @return array<int, array<int, mixed>>
     */
    private function buildDetailedRows($productions): array
    {
        $rows = [];

        foreach ($productions as $production) {
            $cycles = collect($production->ProjectReviewCycles)
                ->sortBy('round_number')
                ->values();

            $previousByOrder = [];

            foreach ($cycles as $cycle) {
                $orders = collect($cycle->Orders ?? collect())
                    ->sortBy(fn($o) => [(string) ($o->order_number ?? ''), (int) ($o->id ?? 0)])
                    ->values();

                foreach ($orders as $order) {
                    $orderNumber = trim((string) ($order->order_number ?? ''));
                    if ($orderNumber === '') {
                        continue;
                    }

                    $currentTotal = (float) ($order->total_cost ?? 0);
                    $currentCompany = (float) ($order->company_cost ?? 0);
                    $currentClient = (float) ($order->client_cost ?? 0);

                    $previous = $previousByOrder[$orderNumber] ?? null;

                    $variationTotal = $this->formatVariationPercent(
                        $currentTotal,
                        is_array($previous) ? (float) ($previous['total'] ?? 0) : null
                    );
                    $variationCompany = $this->formatVariationPercent(
                        $currentCompany,
                        is_array($previous) ? (float) ($previous['company'] ?? 0) : null
                    );
                    $variationClient = $this->formatVariationPercent(
                        $currentClient,
                        is_array($previous) ? (float) ($previous['client'] ?? 0) : null
                    );

                    $rows[] = [
                        $production->Note->note ?? '---',
                        $production->User->name ?? '---',
                        $production->Company->name ?? '---',
                        $orderNumber,
                        number_format($currentTotal, 2, ',', '.'),
                        number_format($currentCompany, 2, ',', '.'),
                        number_format($currentClient, 2, ',', '.'),
                        is_null($previous) ? 'INICIAL' : 'REVISADO',
                        $variationTotal,
                        $variationCompany,
                        $variationClient,
                        $cycle->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---',
                        $cycle->DecidedBy?->name ?? '---',
                    ];

                    $previousByOrder[$orderNumber] = [
                        'total' => $currentTotal,
                        'company' => $currentCompany,
                        'client' => $currentClient,
                    ];
                }
            }
        }

        return $rows;
    }

    private function formatVariationPercent(float $current, ?float $previous): string
    {
        if (is_null($previous) || $previous <= 0) {
            return '0,00%';
        }

        $variation = (($current - $previous) / $previous) * 100;

        if (abs($variation) < 0.00001) {
            return '0,00%';
        }

        return ($variation > 0 ? '+' : '-') . number_format(abs($variation), 2, ',', '.') . '%';
    }

    private function sumCycleCostsByPrefixRule($orders): array
    {
        $orders = collect($orders);
        $latestByPrefix = [];
        $fallback = [
            'total' => 0.0,
            'company' => 0.0,
            'client' => 0.0,
        ];

        foreach ($orders as $order) {
            $total = (float) ($order->total_cost ?? 0);
            $company = (float) ($order->company_cost ?? 0);
            $client = (float) ($order->client_cost ?? 0);

            $fallback['total'] += $total;
            $fallback['company'] += $company;
            $fallback['client'] += $client;

            $prefix = $this->extractOrderPrefix((string) ($order->order_number ?? ''));
            if (!is_null($prefix)) {
                $latestByPrefix[$prefix] = [
                    'total' => $total,
                    'company' => $company,
                    'client' => $client,
                ];
            }
        }

        if (!count($latestByPrefix)) {
            return $fallback;
        }

        return [
            'total' => (float) collect($latestByPrefix)->sum('total'),
            'company' => (float) collect($latestByPrefix)->sum('company'),
            'client' => (float) collect($latestByPrefix)->sum('client'),
        ];
    }

    private function buildPrefixSeriesFromCycles($cycles): array
    {
        $series = collect(['170', '190', '150', '200'])
            ->mapWithKeys(fn ($prefix) => [$prefix => []])
            ->all();

        foreach (collect($cycles) as $cycle) {
            $latestByPrefix = [];

            foreach (collect($cycle->Orders ?? collect()) as $order) {
                $prefix = $this->extractOrderPrefix((string) ($order->order_number ?? ''));
                if (is_null($prefix)) {
                    continue;
                }

                $latestByPrefix[$prefix] = [
                    'total' => (float) ($order->total_cost ?? 0),
                    'company' => (float) ($order->company_cost ?? 0),
                    'client' => (float) ($order->client_cost ?? 0),
                ];
            }

            foreach (['170', '190', '150', '200'] as $prefix) {
                if (array_key_exists($prefix, $latestByPrefix)) {
                    $series[$prefix][] = $latestByPrefix[$prefix];
                }
            }
        }

        return $series;
    }

    private function extractOrderPrefix(string $orderNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $orderNumber);
        if (!$digits || strlen($digits) < 3) {
            return null;
        }

        $prefix = substr($digits, 0, 3);
        return in_array($prefix, ['170', '190', '150', '200'], true) ? $prefix : null;
    }
}
