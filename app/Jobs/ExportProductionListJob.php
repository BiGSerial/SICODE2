<?php

namespace App\Jobs;

use App\Exports\Reports\ProductionsExportList;
use App\Models\Notify;
use App\Models\Production;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ExportProductionListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $params;
    public $userId;
    public $user;
    public $tries = 2;
    public $backoff = [30, 120];

    /**
     * Create a new job instance.
     */
    public function __construct($params, $userId)
    {
        $this->onQueue('exports');
        $this->params = array_merge([
            'complete' => false,
            'monthYear' => null,
            'd5' => false,
            'service' => [],
            'dt_init' => null,
            'dt_end' => null,
            'company' => null,
        ], $params);

        $this->user = User::find($userId);
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $filePath = null;
        $disk = Storage::disk('local');

        try {

            $query = Production::query();
            $query->where('rejected', false);

            if (!$this->params['complete']) {
                $column = 'completed_at';
                $query->where('completed', true);
            } else {
                $column = 'completed_at';
            }

            if ($this->user->contract) {
                $query->where('company_id', $this->user->employee->contract->company_id);
            }

            if ($this->params['monthYear']) {
                $startDate = Carbon::parse($this->params['monthYear'])->startOfMonth();
                $endDate = Carbon::parse($this->params['monthYear'])->endOfMonth();
                if ($this->user->employee && $this->user->employee->contract) {
                    $endDate = Carbon::parse($this->params['monthYear'])->endOfMonth();
                    $query->where(function ($q) use ($column, $startDate, $endDate) {
                        if ($this->params['complete']) {
                            $q->whereBetween($column, [$startDate, $endDate])
                                ->orWhere('completed', false);
                        } else {
                            $q->whereBetween($column, [$startDate, $endDate]);
                        }
                    });
                }

                if (!$this->params['d5']) {
                    $query->where('d5', false);
                }

                if ($this->params['service']) {
                    $query->whereIn('service_id', $this->params['service']);
                }

                if ($this->params['dt_init']) {
                    $query->where(function ($q) use ($column) {
                        if ($this->params['complete']) {
                            $q->where($column, '>=', date('Y-m-d 0:00:00', strtotime($this->params['dt_init'])))
                                ->orWhere('completed', false);
                        } else {
                            $q->where($column, '>=', date('Y-m-d 0:00:00', strtotime($this->params['dt_init'])));
                        }
                    });
                }

                if ($this->params['dt_end']) {
                    $query->where(function ($q) use ($column) {
                        if ($this->params['complete']) {
                            $q->where($column, '<=', date('Y-m-d 23:59:59', strtotime($this->params['dt_end'])))
                                ->orWhere('completed', false);
                        } else {
                            $q->where($column, '<=', date('Y-m-d 23:59:59', strtotime($this->params['dt_end'])));
                        }
                    });
                }

                if ($this->params['company']) {
                    $query->where('company_id', $this->params['company']);
                }

                $query->with('User', 'Company', 'Service', 'Note', 'Analise')
                    ->orderBy('completed_at');
            }

            $fileName = 'exports/' . date('YmdHis') . '-ExportProductionJob.xlsx';
            $filePath = $fileName;

            $disk->makeDirectory('exports');
            $stored = Excel::store(new ProductionsExportList($query), $fileName, 'local');

            if (!$stored || !$disk->exists($fileName)) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            // Criar uma notificação para o usuário
            Notify::create([
                'user_id' => $this->userId,  // Ou passe o ID do usuário para o Job
                'title' => 'Relatório de Produção Concluído',
                'info' => 'Seu relatório está pronto para download.',
                'link' => Storage::url($fileName),
                'status' => 4,
                'readed' => false,
            ]);

        } catch (\Throwable $exception) {
            Log::error("Job ExportProductionListJob falhou", [
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
        Notify::create([
            'user_id' => $this->userId,
            'title' => 'Erro ao Gerar Relatório',
            'info' => "Ocorreu um erro ao gerar o relatório. Tente novamente mais tarde.\n" . $exception->getMessage(),
            'link' => '',
            'status' => 5,
            'readed' => false,
        ]);
    }
}
