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

    public function __construct(array $params, string $userId)
    {
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

        try {
            $fileName = 'exports/' . date('YmdHis') . '-ExternalReclaims.xlsx';

            Excel::store(new ExternalReclaimsExport($this->params), $fileName, 'local');

            if (!Storage::disk('local')->exists($fileName)) {
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
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'params' => $this->params,
            ]);

            if ($user) {
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
}
