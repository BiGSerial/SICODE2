<?php

namespace App\Jobs\Services;

use App\Exports\Services\CancellationExecutionOrdersExport;
use App\Models\CancellationRequest;
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

class ExportCancellationExecutionOrdersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;

    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(array $params)
    {
        $this->onQueue('exports');
        $this->params = $params;
    }

    public function handle(): void
    {
        $user = User::find($this->params['user_id'] ?? null);
        $filePath = null;

        try {
            $ids = $this->params['ids'] ?? [];
            $builder = CancellationRequest::query()
                ->with(['Note', 'Orders', 'Category', 'Requester', 'Assignee'])
                ->whereIn('id', $ids);

            $filePath = 'exports/' . now()->format('YmdHis') . '_cancellation_execution_orders.xlsx';

            Excel::store(new CancellationExecutionOrdersExport($builder), $filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de cancelamento por ordem está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportCancellationExecutionOrdersJob falhou', [
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
