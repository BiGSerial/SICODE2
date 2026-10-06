<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Supervision\SupervisionDispatchListQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Storage};

class ExportSupervisionList implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $params;

    public $timeout = 1800;

    public $tries = 2;

    public $backoff = [30, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(array $params)
    {
        $this->onQueue('exports');
        $this->params = $params;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mesma consulta da lista de despacho: uma linha por informe, parcial ou D5 passiva.
        $query = app(SupervisionDispatchListQuery::class)->build([
            'serviceUuid'         => $this->params['serviceUuid'],
            'user'                => User::find($this->params['user_id'] ?? null),
            'search'              => $this->params['search'] ?? '',
            'multiSearch'         => $this->params['multiSearch'] ?? [],
            'rubrica_s'           => $this->params['rubrica_s'] ?? [],
            'typeNote'            => $this->params['typeNote'] ?? null,
            'not_assigned'        => !empty($this->params['not_assigned']),
            'filterD5'            => !empty($this->params['filterD5']),
            'filter'              => $this->params['filter'] ?? [],
            'bulkSearchAnyStatus' => !empty($this->params['bulkSearchAnyStatus']),
        ]);

        $filePath = null;
        $disk     = Storage::disk('local');

        try {
            $stamp    = now()->format('YmdHis');
            $filePath = "exports/{$stamp}-exportSupervisionList.xlsx";
            $disk->makeDirectory('exports');

            $stored = (new \App\Exports\Dispatchs\SupervisionExportList(
                $query,
                $this->params['serviceUuid'],
                $this->params['selected_work_report_ids'] ?? [],
                $this->params['selected_partial_ids'] ?? [],
                $this->params['selected_five_note_ids'] ?? [],
            ))
                ->store($filePath, 'local');

            if (!$stored || !$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            // Notifica o usuário
            $user = User::find($this->params['user_id']);

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de lista de Fiscalização está pronto para download. <br><br> Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (\Throwable $exception) {
            Log::error('ExportSupervisionList falhou', [
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

    public function failed(\Throwable $exception): void
    {
        if ($user = User::find($this->params['user_id'] ?? null)) {
            $user->notify(new SystemNotification(
                'Erro na exportação',
                'Seu relatório de Fiscalização não pôde ser gerado.',
                null,
                5,
                []
            ));
        }
    }
}
