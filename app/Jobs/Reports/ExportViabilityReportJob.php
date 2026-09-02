<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\viabilityQueryExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Reports\ViabilityReportQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Storage};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportViabilityReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;

    public string $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1200;

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(ViabilityReportQueryService $queryService): void
    {
        $user     = User::find($this->userId);
        $filePath = null;
        $disk     = Storage::disk('local');

        try {
            $params = $queryService->normalizeParams($this->params);
            $query  = $queryService->exportQuery($params);

            $stamp    = now()->format('YmdHis');
            $filePath = "exports/viability_report_{$stamp}.xlsx";
            $disk->makeDirectory('exports');

            Excel::store(new viabilityQueryExport($query), $filePath, 'local');

            if (!$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado no disco esperado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportacao de Viabilidades',
                    'Seu relatorio de viabilidade esta pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportViabilityReportJob falhou', [
                'user_id'       => $this->userId,
                'params'        => $this->params,
                'attempt'       => $this->attempts(),
                'error_message' => $exception->getMessage(),
                'trace'         => $exception->getTraceAsString(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro na exportacao de Viabilidades',
                'Nao foi possivel gerar o relatorio de viabilidade. ' . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }
}
