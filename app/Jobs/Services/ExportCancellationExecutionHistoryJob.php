<?php

namespace App\Jobs\Services;

use App\Enum\CancellationRequestStatus;
use App\Exports\Services\CancellationExecutionHistoryExport;
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

class ExportCancellationExecutionHistoryJob implements ShouldQueue
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
            $visibleCloserIds = $this->params['visibleCloserIds'] ?? null;
            $requesterIds = $this->params['requesterIds'] ?? [];

            /** @var Builder $builder */
            $builder = CancellationRequest::query()
                ->with(['Note', 'Orders', 'Category', 'Requester', 'Closer'])
                ->whereIn('status', [
                    CancellationRequestStatus::DONE->value,
                    CancellationRequestStatus::REJECTED->value,
                    CancellationRequestStatus::ABORTED->value,
                ])
                ->when(is_array($visibleCloserIds), fn ($q) => $q->whereIn('closed_by', $visibleCloserIds))
                ->when(is_array($requesterIds) && count($requesterIds), fn ($q) => $q->whereIn('requested_by', $requesterIds))
                ->when($dateFrom, fn ($q) => $q->whereDate('closed_at', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('closed_at', '<=', $dateTo))
                ->when(is_array($multi) && count($multi), function ($q) use ($multi) {
                    $q->where(function ($sub) use ($multi) {
                        $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                            ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                    });
                })
                ->orderByDesc('closed_at');

            $filePath = 'exports/' . now()->format('YmdHis') . '_cancellation_execution_history.xlsx';

            Excel::store(new CancellationExecutionHistoryExport($builder), $filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de histórico de execução está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportCancellationExecutionHistoryJob falhou', [
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

