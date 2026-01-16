<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\ReturnInternReportExport;
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

class ExportInternalReclaimsJob implements ShouldQueue
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
            'search' => null,
            'originFilters' => [],
            'serviceIds' => [],
            'category' => null,
            'dispatcherUserId' => null,
            'productionUserId' => null,
            'companyId' => null,
            'productionStatus' => '',
            'completedFilter' => '',
            'resolutionMin' => '',
            'resolutionMax' => '',
        ], $params);

        $this->userId = $userId;
        $this->user = User::find($userId);
    }

    public function handle(): void
    {
        $user = $this->user ?? User::find($this->userId);

        try {
            $fileName = 'exports/' . date('YmdHis') . '-ReturnIntern.xlsx';

            Excel::store(new ReturnInternReportExport($this->params), $fileName, 'local');

            if (!Storage::disk('local')->exists($fileName)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportacao de Retornos Internos',
                    'Seu arquivo esta pronto para download.',
                    Storage::url($fileName),
                    4,
                    []
                ));
            }
        } catch (\Throwable $exception) {
            Log::error('ExportInternalReclaimsJob falhou', [
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
