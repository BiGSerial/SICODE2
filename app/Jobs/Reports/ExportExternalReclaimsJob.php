<?php

namespace App\Jobs\Reports;

use App\Exports\Oexterno\ExternalReclaimsExport;
use App\Models\Notify;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
        try {
            $fileName = 'exports/' . date('YmdHis') . '-ExternalReclaims.xlsx';

            Excel::store(new ExternalReclaimsExport($this->params), $fileName, 'public');

            Notify::create([
                'user_id' => $this->userId,
                'title' => 'Exportacao de Reclaims Externos',
                'info' => 'Seu arquivo esta pronto para download.',
                'link' => $fileName,
                'status' => 4,
                'readed' => false,
            ]);
        } catch (\Throwable $exception) {
            Log::error('ExportExternalReclaimsJob falhou', [
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'params' => $this->params,
            ]);

            Notify::create([
                'user_id' => $this->userId,
                'title' => 'Erro ao gerar exportacao',
                'info' => "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                'link' => '',
                'status' => 5,
                'readed' => false,
            ]);
        }
    }
}
