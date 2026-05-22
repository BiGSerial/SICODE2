<?php

namespace App\Jobs\Reports;

use App\Exports\Oexterno\ExternalReclaimsExport;
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

class ExportExternalReclaimsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;
    public string $userId;
    public ?User $user;
    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = array_merge([
            'dt_in' => null,
            'dt_out' => null,
            'status' => [],
            'entityTypeIds' => [],
            'entityIds' => [],
            'rubrics' => [],
        ], $params);

        $this->userId = $userId;
        $this->user = User::find($userId);
    }

    public function handle(): void
    {
        $user = $this->user ?? User::find($this->userId);
        $filePath = null;
        $disk = Storage::disk('local');

        try {
            $fileName = 'exports/' . date('YmdHis') . '-ExternalReclaims.xlsx';
            $filePath = $fileName;
            $disk->makeDirectory('exports');

            Excel::store(new ExternalReclaimsExport($this->params), $fileName, 'local');

            if (!$disk->exists($fileName)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportacao de Reclaims Externos',
                    'Seu arquivo esta pronto para download.',
                    Storage::url($fileName),
                    4,
                    []
                ));
            }
        } catch (\Throwable $exception) {
            Log::error('ExportExternalReclaimsJob falhou', [
                'job' => static::class,
                'user_id' => $this->userId,
                'message' => $exception->getMessage(),
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
