<?php

namespace App\Jobs\Reports;

use App\Exports\Workreports\WorkreportsListExport;
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

class ExportWorkreportsJob implements ShouldQueue
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
            'date_in' => null,
            'date_out' => null,
            'dateBy' => 'first_informed',
            'search' => null,
            'multiSearch' => [],
            'filters' => [],
        ], $params);

        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;
        $disk = Storage::disk('local');

        try {
            $stamp = now()->format('YmdHis');
            $filePath = "exports/workreports_{$stamp}.xlsx";
            $disk->makeDirectory('exports');

            Excel::store(new WorkreportsListExport($this->params), $filePath, 'local');

            if (!$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportacao de Obras Informadas',
                    'Seu arquivo esta pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (\Throwable $exception) {
            Log::error('ExportWorkreportsJob falhou', [
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'params' => $this->params,
                'attempt' => $this->attempts(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro ao gerar exportacao',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }
}
