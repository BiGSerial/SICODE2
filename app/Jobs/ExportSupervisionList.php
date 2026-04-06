<?php

namespace App\Jobs;

use App\Exports\Dispatchs\SupervisionExportList;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Repositories\SupervisionRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    public function handle(SupervisionRepository $supervisionRepository): void
    {
        // Gera o nome do arquivo
        $query = $supervisionRepository->getBaseQuery();

        if (!empty($this->params['search'])) {
            $search = $this->params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', '%' . trim($search) . '%')
                    ->orWhere('material', 'like', '%' . trim($search) . '%')
                    ->orWhere('numPedido', 'like', '%' . trim($search) . '%')
                    ->orWhere('group1', 'like', '%' . trim($search) . '%')
                    ->orWhere('group2', 'like', '%' . trim($search) . '%')
                    ->orWhere('group4', 'like', '%' . trim($search) . '%')
                    ->orWhere('group5', 'like', '%' . trim($search) . '%')
                    ->orWhereRelation('Orders', 'ordem', 'like', '%' . trim($search) . '%');
            });
        }

        if (!empty($this->params['multiSearch'])) {
            $multiSearch = $this->params['multiSearch'];
            $query->where(function ($q) use ($multiSearch) {
                $q->whereIn('note', $multiSearch)
                    ->orWhereRelation('Orders', function ($q) use ($multiSearch) {
                        $q->whereIn('ordem', $multiSearch);
                    });
            });
        }

        if (!empty($this->params['rubrica_s'])) {
            $rubrica_s = $this->params['rubrica_s'];
            $query->where(function ($q) use ($rubrica_s) {
                $q->whereIn('rubrica', $rubrica_s)
                    ->orWhereNull('rubrica');
            });
        }

        if (!empty($this->params['typeNote'])) {
            $typeNote = $this->params['typeNote'];
            $query->where(function ($q) use ($typeNote) {
                $q->where('type_note', $typeNote)
                    ->orWhereNull('type_note');
            });
        }

        if (!empty($this->params['not_assigned'])) {
            $serviceUuid = $this->params['serviceUuid'];
            $query->where(function ($q) use ($serviceUuid) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) use ($serviceUuid) {
                        $subquery->where('service_id', $serviceUuid)
                            ->where('confirmed', false);
                    });
            });
        }

        if (!empty($this->params['filterD5'])) {
            $query->where(function ($q) {
                $q->whereHas('FiveNote');
            });
        }

        if (!empty($this->params['filter']['rubrica'])) {
            $query->whereIn('rubrica', $this->params['filter']['rubrica']);
        }

        if (!empty($this->params['filter']['city']) && is_array($this->params['filter']['city'])) {
            $city = collect($this->params['filter']['city'])
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->values()
                ->all();

            if (!empty($city)) {
                $query->whereRelation('City', function ($q) use ($city) {
                    $q->whereIn('rdMunicipio', $city);
                });
            }
        }

        $query->with([
            'orders' => function ($q) {
                $q->where('statusSist', 'not like', 'ENT%')->where('statusSist', 'not like', 'ENC%');
            },'Productions.User', 'Wpas', 'Partials', 'TempAdsInfos', 'OldAds', 'FiveNote'
        ])
        ->select('notes.*', 'work_reports.created_at as work_dt_created')
        ->orderBy('work_dt_created', 'ASC')
        ->orderBy('id', 'ASC');


        $filePath = null;
        $disk = Storage::disk('local');

        try {
            $stamp = now()->format('YmdHis');
            $filePath = "exports/{$stamp}-exportSupervisionList.xlsx";
            $disk->makeDirectory('exports');

            $stored = (new \App\Exports\Dispatchs\SupervisionExportList($query, $this->params['serviceUuid']))
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
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
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
