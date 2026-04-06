<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\InformAdsTacitaReportExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Reports\InformAdsTacitReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportInformAdsTacitaReportJob implements ShouldQueue
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
    public int $timeout = 1200;

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = array_merge([
            'mode' => 'note',
            'date_in' => null,
            'date_out' => null,
            'search' => null,
        ], $params);
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $mode = (string) ($this->params['mode'] ?? 'note');
            $reportService = app(InformAdsTacitReportService::class);
            $rows = $reportService->exportRows($mode, $this->params);

            $stamp = now()->format('YmdHis');
            $filePath = "exports/informe_ads_tacita_{$stamp}.xlsx";
            Storage::disk('local')->makeDirectory('exports');

            Excel::store(new InformAdsTacitaReportExport($rows), $filePath, 'local');

            if (!$filePath || !Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            if ($user) {
                $modeLabel = $mode === 'order' ? 'Por ORDEM' : 'Por NOTA';

                $user->notify(new SystemNotification(
                    'Exportação Informe x ADS Tácita',
                    "Seu arquivo ({$modeLabel}) está pronto para download.",
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportInformAdsTacitaReportJob falhou', [
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'params' => $this->params,
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
                'Erro ao gerar exportação',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }
}
