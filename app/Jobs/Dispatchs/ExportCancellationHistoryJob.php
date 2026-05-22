<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\CancellationHistoryExport;
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

class ExportCancellationHistoryJob implements ShouldQueue
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
            $dateFrom = $this->params['dateFrom'] ?? null;
            $dateTo = $this->params['dateTo'] ?? null;
            $status = $this->params['status'] ?? null;

            /** @var Builder $builder */
            $builder = CancellationRequest::query()
                ->with(['Note', 'Orders', 'Category', 'Requester', 'Assignee', 'Closer'])
                ->whereIn('status', [
                    CancellationRequest::STATUS_DONE,
                    CancellationRequest::STATUS_REJECTED,
                    CancellationRequest::STATUS_ABORTED,
                ])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($dateFrom, fn ($q) => $q->whereDate('closed_at', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('closed_at', '<=', $dateTo))
                ->when(is_array($multi) && count($multi), function ($q) use ($multi) {
                    $q->where(function ($sub) use ($multi) {
                        $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                            ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                    });
                })
                ->orderByDesc('closed_at');

            $filePath = 'exports/' . now()->format('YmdHis') . '_cancellation_history.xlsx';

            Excel::store(new CancellationHistoryExport($builder), $filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de histórico de cancelamentos está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportCancellationHistoryJob falhou', [
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
