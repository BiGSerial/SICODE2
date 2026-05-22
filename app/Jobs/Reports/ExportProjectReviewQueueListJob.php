<?php

namespace App\Jobs\Reports;

use App\Custom\Notestatus;
use App\Exports\ProjectReview\QueueListExport;
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

class ExportProjectReviewQueueListJob implements ShouldQueue
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
            $rows = $this->buildRows();
            $stamp = now()->format('YmdHis');
            $filePath = "exports/project_review_queue_list_{$stamp}.xlsx";

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new QueueListExport($rows), $filePath, 'local');

            if (!$filePath || !Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação Lista Análise de Projeto',
                    'Seu arquivo da lista para analisar está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportProjectReviewQueueListJob falhou', [
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
                'Erro ao gerar exportação da lista',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function buildRows(): array
    {
        $search = trim((string) ($this->filters['search'] ?? ''));
        $companyId = (string) ($this->filters['company_id'] ?? '');
        $costShareFilter = (string) ($this->filters['cost_share_filter'] ?? '');
        $tab = (string) ($this->filters['tab'] ?? 'pending');

        $query = Production::query()
            ->with([
                'Note',
                'User',
                'Company',
                'ProjectReviewCycles' => function ($q) {
                    $q->with(['Orders', 'DecidedBy'])->latest('round_number');
                },
            ])
            ->withCount([
                'ProjectReviewCycles as rejected_cycles_count' => function ($q) {
                    $q->where('decision', 'REJECTED');
                },
                'Notetimelines as rejected_status_timeline_count' => function ($q) {
                    $q->where('status', Production::STATUS_REJECTED_PROJECT_REVIEW);
                },
            ])
            ->withMax('ProjectReviewCycles as latest_round_number', 'round_number');

        if ($tab === 'pending') {
            $query->where('status', Production::STATUS_IN_PROJECT_REVIEW);
        } else {
            $query->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW, Production::STATUS_RELEASED_TO_FINISH])
                ->whereHas('ProjectReviewCycles', function ($q) {
                    $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED']);
                });
        }

        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->whereHas('Note', function ($q) use ($term) {
                $q->where('note', 'like', $term)
                    ->orWhere('numPedido', 'like', $term)
                    ->orWhere('material', 'like', $term);
            });
        }

        if ($companyId !== '') {
            $query->where('company_id', $companyId);
        }

        if (in_array($costShareFilter, ['client_51', 'company_51', 'both_51'], true)) {
            $query->whereHas('ProjectReviewCycles.Orders', function ($orderQuery) use ($costShareFilter) {
                $ratioExprClient = '(project_review_orders.client_cost / NULLIF(project_review_orders.total_cost, 0))';
                $ratioExprCompany = '(project_review_orders.company_cost / NULLIF(project_review_orders.total_cost, 0))';

                $orderQuery->where('project_review_orders.total_cost', '>', 0);

                if ($costShareFilter === 'client_51') {
                    $orderQuery->whereRaw("{$ratioExprClient} >= 0.51");
                    return;
                }

                if ($costShareFilter === 'company_51') {
                    $orderQuery->whereRaw("{$ratioExprCompany} >= 0.51");
                    return;
                }

                $orderQuery->where(function ($q) use ($ratioExprClient, $ratioExprCompany) {
                    $q->whereRaw("{$ratioExprClient} >= 0.51")
                        ->orWhereRaw("{$ratioExprCompany} >= 0.51");
                });
            });
        }

        return $query
            ->orderByRaw("
                CASE
                    WHEN (
                        SELECT notes.type_note
                        FROM notes
                        WHERE notes.id = productions.note_id
                        LIMIT 1
                    ) = 2 THEN 0
                    ELSE 1
                END ASC
            ")
            ->orderByRaw("
                COALESCE((
                    SELECT notes.days_left
                    FROM notes
                    WHERE notes.id = productions.note_id
                    LIMIT 1
                ), 2147483647) ASC
            ")
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($production) {
            $cycle = collect($production->ProjectReviewCycles)->sortByDesc('round_number')->first();
            $orders = $cycle?->Orders ?? collect();
            if ($orders->isEmpty()) {
                $orders = $production->ProjectReviewCycles->first(function ($c) {
                    return $c->Orders->count() > 0;
                })?->Orders ?? collect();
            }
            $orders = collect($orders)
                ->sortBy(fn ($o) => [(string) ($o->order_number ?? ''), (int) ($o->id ?? 0)])
                ->values();

            $ordersText = $orders->pluck('order_number')->filter()->implode(' | ');
            $totalsText = $orders->map(fn ($o) => number_format((float) $o->total_cost, 2, ',', '.'))->implode(' | ');
            $companyCostsText = $orders->map(fn ($o) => number_format((float) $o->company_cost, 2, ',', '.'))->implode(' | ');
            $clientCostsText = $orders->map(fn ($o) => number_format((float) $o->client_cost, 2, ',', '.'))->implode(' | ');

            $latestDecidedCycle = collect($production->ProjectReviewCycles)
                ->first(function ($c) {
                    return !is_null($c->decided_at);
                });

            $latestRound = (int) ($production->latest_round_number ?? ($cycle?->round_number ?? 1));
            $rejectedCount = (int) ($production->rejected_cycles_count ?? 0);
            $rejectedTimelineCount = (int) ($production->rejected_status_timeline_count ?? 0);
            $isReturnToReview = ($latestRound > 1)
                || ($rejectedCount > 0)
                || ($rejectedTimelineCount > 0)
                || collect($production->ProjectReviewCycles)->contains(fn ($c) => $c->decision === 'REJECTED');

            return [
                $production->Note->note ?? '---',
                $production->User->name ?? '---',
                $production->Company->name ?? '---',
                $ordersText !== '' ? $ordersText : '---',
                $totalsText !== '' ? $totalsText : '---',
                $companyCostsText !== '' ? $companyCostsText : '---',
                $clientCostsText !== '' ? $clientCostsText : '---',
                Notestatus::status((int) $production->status)->status,
                $isReturnToReview ? 'Retorno' : 'Inicial',
                $cycle?->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---',
                $latestDecidedCycle?->DecidedBy?->name ?? '---',
                $latestDecidedCycle?->analyst_note ?? '---',
            ];
            })->all();
    }
}
