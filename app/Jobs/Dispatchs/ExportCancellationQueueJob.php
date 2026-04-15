<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\CancellationQueueExport;
use App\Models\CancellationRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportCancellationQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;
    public string $userId;

    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $multi = $this->params['multiSearch'] ?? [];
            $status = $this->params['status'] ?? null;
            $categoryId = $this->params['categoryId'] ?? null;
            $noteSearch = $this->params['noteSearch'] ?? null;
            $orderSearch = $this->params['orderSearch'] ?? null;
            $requesterSearch = $this->params['requesterSearch'] ?? null;
            $dateFrom = $this->params['dateFrom'] ?? null;
            $dateTo = $this->params['dateTo'] ?? null;
            $onlyUnassigned = (bool) ($this->params['onlyUnassigned'] ?? false);

            /** @var Builder $builder */
            $builder = CancellationRequest::query()
                ->with(['Note', 'Orders', 'Category', 'Requester', 'Assignee'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
                ->when($noteSearch, function ($q) use ($noteSearch) {
                    $q->whereHas('Note', fn ($note) => $note->where('note', 'like', '%' . $noteSearch . '%'));
                })
                ->when($orderSearch, function ($q) use ($orderSearch) {
                    $q->whereHas('Orders', fn ($order) => $order->where('ordem', 'like', '%' . $orderSearch . '%'));
                })
                ->when($requesterSearch, function ($q) use ($requesterSearch) {
                    $q->whereHas('Requester', fn ($requester) => $requester->where('name', 'like', '%' . $requesterSearch . '%'));
                })
                ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->when($onlyUnassigned, fn ($q) => $q->whereNull('assigned_to'))
                ->when(is_array($multi) && count($multi), function ($q) use ($multi) {
                    $q->where(function ($sub) use ($multi) {
                        $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                            ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                    });
                })
                ->orderBy('created_at');

            $filePath = 'exports/' . now()->format('YmdHis') . '_cancellation_queue.xlsx';

            Excel::store(new CancellationQueueExport($builder), $filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório da fila de cancelamentos está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportCancellationQueueJob falhou', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação falhou',
                    'Não foi possível gerar o relatório. Tente novamente.',
                    null,
                    5,
                    []
                ));
            }
        }
    }
}

