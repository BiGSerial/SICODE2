<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\PostWorkProcessReportExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\Notifications\UserNotificationData;
use App\Services\Reports\PostWorkProcessReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportPostWorkProcessReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200;

    /** @param array<string, mixed> $filters */
    public function __construct(public array $filters, public string $userId)
    {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = 'exports/post_work_process_' . now()->format('YmdHis') . '.xlsx';

        try {
            $rows = app(PostWorkProcessReportService::class)->rows($this->filters);

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new PostWorkProcessReportExport($rows), $filePath, 'local');

            if (!Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            $user?->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação - Processo de Medição Pós Obra',
                message: 'Seu relatório está pronto para download.',
                link: Storage::url($filePath),
                status: 'download',
            )));
        } catch (Throwable $exception) {
            Log::error('ExportPostWorkProcessReportJob falhou', [
                'error_message' => $exception->getMessage(),
                'filters'       => $this->filters,
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro ao gerar relatório Pós Obra',
            message: 'Ocorreu um erro ao gerar o arquivo. ' . $exception->getMessage(),
            status: 'error',
        )));
    }
}
