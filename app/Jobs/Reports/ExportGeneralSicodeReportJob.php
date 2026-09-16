<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\GeneralSicodeReportExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Reports\GeneralSicodeReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportGeneralSicodeReportJob implements ShouldQueue
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

    public function handle(GeneralSicodeReportService $service): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $report = $service->report($this->filters);
            $stamp = now()->format('YmdHis');
            $filePath = "exports/general_sicode_report_{$stamp}.xlsx";

            $auditRows = [
                ['Usuário solicitante', $user?->name ?? '---'],
                ['Email', $user?->email ?? '---'],
                ['Data/Hora da solicitação', now()->format('d/m/Y H:i:s')],
                ['Tipo de exportação', 'Relatório Geral SICODE'],
                ['Aplicação origem', $report['application'] ?? config('app.name', 'SICODE')],
            ];

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new GeneralSicodeReportExport($report, $this->filters, $auditRows), $filePath, 'local');

            if (!$filePath || !Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação - Relatório Geral SICODE',
                    'Seu arquivo consolidado do SICODE está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportGeneralSicodeReportJob falhou', [
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
                'Erro ao gerar Relatório Geral SICODE',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }
}
