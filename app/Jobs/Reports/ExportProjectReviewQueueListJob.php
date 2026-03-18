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
        $tab = (string) ($this->filters['tab'] ?? 'pending');

        $query = Production::query()
            ->with([
                'Note',
                'User',
                'Company',
                'ProjectReviewCycles' => function ($q) {
                    $q->with(['Orders', 'DecidedBy'])->latest('round_number');
                },
            ]);

        if ($tab === 'pending') {
            $query->where('status', Production::STATUS_IN_PROJECT_REVIEW)
                ->where('completed', false);
        } else {
            $query->whereIn('status', [5, Production::STATUS_REJECTED_PROJECT_REVIEW])
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

        return $query->orderByDesc('id')->get()->map(function ($production) {
            $cycle = collect($production->ProjectReviewCycles)->sortByDesc('round_number')->first();
            $orders = $cycle?->Orders ?? collect();
            if ($orders->isEmpty()) {
                $orders = $production->ProjectReviewCycles->first(function ($c) {
                    return $c->Orders->count() > 0;
                })?->Orders ?? collect();
            }

            $ordersText = $orders->pluck('order_number')->filter()->implode(' | ');
            $totalsText = $orders->map(fn ($o) => number_format((float) $o->total_cost, 2, ',', '.'))->implode(' | ');
            $companyCostsText = $orders->map(fn ($o) => number_format((float) $o->company_cost, 2, ',', '.'))->implode(' | ');
            $clientCostsText = $orders->map(fn ($o) => number_format((float) $o->client_cost, 2, ',', '.'))->implode(' | ');

            $proportionality = '---';
            if (!is_null($cycle?->proportionality_ok)) {
                $proportionality = $cycle->proportionality_ok ? 'Sim' : 'Não';
                if (!is_null($cycle->proportionality_value)) {
                    $proportionality .= ' (' . number_format((float) $cycle->proportionality_value, 2, ',', '.') . '%)';
                }
            }

            $latestDecidedCycle = collect($production->ProjectReviewCycles)
                ->first(function ($c) {
                    return !is_null($c->decided_at);
                });

            return [
                $production->Note->note ?? '---',
                $production->User->name ?? '---',
                $production->Company->name ?? '---',
                $ordersText !== '' ? $ordersText : '---',
                $totalsText !== '' ? $totalsText : '---',
                $companyCostsText !== '' ? $companyCostsText : '---',
                $clientCostsText !== '' ? $clientCostsText : '---',
                $proportionality,
                Notestatus::status((int) $production->status)->status,
                $cycle?->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---',
                $latestDecidedCycle?->DecidedBy?->name ?? '---',
                $latestDecidedCycle?->analyst_note ?? '---',
            ];
        })->all();
    }
}
