<?php

namespace App\Jobs\Protests;

use App\Enum\ProtestType;
use App\Exports\Protests\ProtestsExportList;
use App\Models\Protest;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Traits\AppliesQueryFilters;
use App\Traits\WildcardFormmater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProtestExportListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use AppliesQueryFilters;
    use WildcardFormmater;

    public $params;
    public $userId;
    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct($params, $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;
        $disk = Storage::disk('local');

        try {
            $showOnlyBtzero = (bool) ($this->params['showOnlyBtzero'] ?? false);
            $hideBtzero = (bool) ($this->params['hideBtzero'] ?? true);
            if ($showOnlyBtzero) {
                $hideBtzero = false;
            }

            $query = Protest::query()
                ->select('protests.*')
                ->selectRaw("
                    CASE
                        WHEN protests.tipoNota = 'NA' THEN protests.dtConclusaoDesej
                        ELSE (
                            SELECT mp.dtFimMedidaDesej
                            FROM med_protests mp
                            WHERE mp.protest_id = protests.id
                              AND mp.statusSist = 'MEDA'
                            ORDER BY mp.dtCriacaoMedida DESC
                            LIMIT 1
                        )
                    END AS vencimento,
                    CASE
                        WHEN protests.tipoNota = 'NA' THEN protests.dtAberturaNota
                        ELSE (
                            SELECT mp2.dtCriacaoMedida
                            FROM med_protests mp2
                            WHERE mp2.protest_id = protests.id
                              AND mp2.statusSist = 'MEDA'
                            ORDER BY mp2.dtCriacaoMedida DESC
                            LIMIT 1
                        )
                    END AS abertura
                ")
                ->with([
                    'medProtests' => function ($q) use ($showOnlyBtzero, $hideBtzero) {
                        $q->where('statusSist', 'MEDA')
                            ->whereDoesntHave('ProtestJobs')
                            ->when($showOnlyBtzero, function ($typeQuery) {
                                $typeQuery->where('protest_type', ProtestType::BTZERO->value);
                            }, function ($typeQuery) use ($hideBtzero) {
                                if ($hideBtzero) {
                                    $typeQuery->where(function ($sub) {
                                        $sub->whereNull('protest_type')
                                            ->orWhere('protest_type', '!=', ProtestType::BTZERO->value);
                                    });
                                }
                            })
                            ->orderByDesc('dtCriacaoMedida')
                            ->with(['ProtestJobs' => fn ($job) => $job->orderByDesc('created_at')]);
                    },
                    'Notes',
                ]);

            $filtersMap = [
                'city' => ['type' => 'in', 'column' => 'cidade'],
                'type' => ['type' => 'equals', 'column' => 'tipoNota'],
                'desired_between' => ['type' => 'between_dates', 'column' => 'dtConclusaoDesej'],
            ];

            $this->applyFilters($query, $this->params['filtersState'] ?? [], $filtersMap);

            $isSearching = filled($this->params['search'] ?? null) || !empty($this->params['multisearch']);

            if (!$isSearching) {
                $query->whereHas('medProtests', function ($q) use ($showOnlyBtzero, $hideBtzero) {
                    $q->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs')
                        ->when($showOnlyBtzero, function ($typeQuery) {
                            $typeQuery->where('protest_type', ProtestType::BTZERO->value);
                        }, function ($typeQuery) use ($hideBtzero) {
                            if ($hideBtzero) {
                                $typeQuery->where(function ($sub) {
                                    $sub->whereNull('protest_type')
                                        ->orWhere('protest_type', '!=', ProtestType::BTZERO->value);
                                });
                            }
                        });
                });
            }

            if (!$showOnlyBtzero && $hideBtzero) {
                $query->whereDoesntHave('medProtests', function ($q) {
                    $q->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs')
                        ->where('protest_type', ProtestType::BTZERO->value);
                });
            }

            if (!empty($this->params['search'])) {
                $formatted = $this->formatWithWildcard($this->params['search']);
                $query->where(function ($q) use ($formatted) {
                    $q->where('nota', $formatted->type, $formatted->search)
                        ->orWhere('txtGrpCodificacao', $formatted->type, $formatted->search)
                        ->orWhereHas('Notes', function ($noteQuery) use ($formatted) {
                            $noteQuery->where('note', $formatted->type, $formatted->search)
                                ->orWhere('material', $formatted->type, $formatted->search);
                        });
                });
            }

            if (!empty($this->params['multisearch'])) {
                $values = (array) $this->params['multisearch'];
                $query->where(function ($sub) use ($values) {
                    $sub->whereIn('nota', $values)
                        ->orWhereHas('Notes', function ($noteQuery) use ($values) {
                            $noteQuery->whereIn('note', $values);
                        });
                });
            }

            $query->orderByRaw('ISNULL(vencimento), vencimento ASC');

            $filePath = 'exports/' . now()->format('YmdHis') . '-exportProtestsList.xlsx';

            $disk->makeDirectory('exports');
            (new ProtestsExportList($query))->store($filePath, 'local');

            if ($user && $disk->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Reclamação está pronto para download.<br><br>Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ProtestExportListJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro na exportação',
                'Seu relatório de Reclamação não pôde ser gerado.',
                null,
                5,
                []
            ));
        }
    }

    protected function filtersMap(): array
    {
        return [
            'city' => ['type' => 'in', 'column' => 'cidade'],
            'type' => ['type' => 'equals', 'column' => 'tipoNota'],
            'desired_between' => ['type' => 'between_dates', 'column' => 'dtConclusaoDesej'],
        ];
    }
}
