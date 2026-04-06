<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\ProductionsExportList;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ExportProductionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;
    public string $userId;

    public $tries   = 2;
    public $backoff = [30, 120];
    public int $timeout = 1800; // 30 min para exportacoes grandes

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user         = User::find($this->userId);
        $includeOpen  = (bool)($this->params['complete'] ?? false); // incluir não concluídos?
        $wantD5       = (bool)($this->params['d5'] ?? false);
        $filePath     = null;
        $serviceLabel = '';

        $disk = Storage::disk('local');

        try {
            $query = Production::query()

                ->select([
                    'productions.id',
                    'productions.user_id',
                    'productions.company_id',
                    'productions.service_id',
                    'productions.dispatch_by',
                    'productions.note_id',
                    'productions.att_by',
                    'productions.dt_note',
                    'productions.dispatch_at',
                    'productions.att_at',
                    'productions.completed_at',
                    'productions.odi',
                    'productions.odd',
                    'productions.ods',
                    'productions.eo',
                    'productions.iproject',
                    'productions.cad',
                    'productions.cadastro',
                    'productions.postes_c',
                    'productions.postes_u',
                    'productions.stopped',
                    'productions.d5',
                    'productions.confirmed',
                    'productions.status',
                    'productions.completed',
                    'productions.partial',
                    'productions.partial_at',
                    'productions.dfive',
                    'productions.supervision_by_partner_photos',
                ])
                ->where('productions.rejected', false)
                // completed?
                ->when(!$includeOpen, fn ($q) => $q->where('productions.completed', true))
                // empresa
                ->when(($this->params['company'] ?? null), fn ($q, $company) => $q->where('productions.company_id', $company))
                // serviços
                ->when(!empty($this->params['service'] ?? []), fn ($q) => $q->whereIn('productions.service_id', $this->params['service']))
                // mês/ano
                ->when(($this->params['monthYear'] ?? null), function ($q, $ym) use ($includeOpen) {
                    $start = date('Y-m-01 00:00:00', strtotime($ym));
                    $end   = date('Y-m-t 23:59:59', strtotime($ym));
                    $q->where(function ($w) use ($includeOpen, $start, $end) {
                        $w->whereBetween('productions.completed_at', [$start, $end]);
                        if ($includeOpen) {
                            $w->orWhere('productions.completed', false);
                        }
                    });
                })
                // dt_init
                ->when(($this->params['dt_init'] ?? null), function ($q, $dt) use ($includeOpen) {
                    $start = date('Y-m-d 00:00:00', strtotime($dt));
                    $q->where(function ($w) use ($includeOpen, $start) {
                        $w->where('productions.completed_at', '>=', $start);
                        if ($includeOpen) {
                            $w->orWhere('productions.completed', false);
                        }
                    });
                })
                // dt_end
                ->when(($this->params['dt_end'] ?? null), function ($q, $dt) use ($includeOpen) {
                    $end = date('Y-m-d 23:59:59', strtotime($dt));
                    $q->where(function ($w) use ($includeOpen, $end) {
                        $w->where('productions.completed_at', '<=', $end);
                        if ($includeOpen) {
                            $w->orWhere('productions.completed', false);
                        }
                    });
                })
                // D5: se não marcado, exclui D5; se marcado, inclui todos (D5 e não-D5), como no componente
                ->when(!$wantD5, fn ($q) => $q->where('productions.d5', false))
                // search simples
                ->when(strlen(trim($this->params['search'] ?? '')) > 0, function ($q) {
                    $search = trim($this->params['search']);
                    $wildcard = (str_contains($search, '*') || str_contains($search, '%'))
                        ? str_replace('*', '%', $search)
                        : $search;
                    $type = str_contains($wildcard, '%') ? 'like' : '=';
                    $q->where(function ($w) use ($wildcard, $type) {
                        $w->whereRelation('note', 'note', $type, $wildcard)
                          ->orWhereRelation('note.orders', 'ordem', $type, $wildcard)
                          ->orWhereRelation('note', 'material', $type, $wildcard);
                    });
                })
                // multisearch
                ->when(!empty($this->params['multisearch'] ?? []), function ($q) {
                    $arr = array_values(array_filter($this->params['multisearch']));
                    $q->where(function ($w) use ($arr) {
                        $w->whereRelation('Note', function ($qs) use ($arr) {
                            $qs->whereIn('note', $arr)
                               ->orWhereIn('material', $arr);
                        })
                          ->orWhereRelation('Note.Orders', function ($qs) use ($arr) {
                              $qs->whereIn('ordem', $arr);
                          });
                    });
                })
                ->with([
                    'Dispatcher:id,name',
                    'Dispatcher.Employee.Contract.company:id,name',
                    'Att:id,name',
                    'Att.Employee.Contract.company:id,name',
                    'User:id,name',
                    'Company:id,name',
                    'Service:uuid,service',
                    'Note:id,note,material,group2,group5,lexp,postes,nexp,doe,rubrica,type_note',
                    'Note.RamalForm:id,note_id,created_at',
                    'Note.WorkForm' => fn ($q) => $q->with(['Orders:id']),
                    'Note.WorkForm' => fn ($q) => $q->with(['Adsform:id,created_at']),
                    'Analise',
                    'Reclaim:id,category',

                ])
                ->orderBy('productions.completed_at');

            $rowEstimate = (clone $query)->toBase()->count();

            // Nome do serviço (se 1 selecionado)
            $serviceLabel = '';
            if (!empty($this->params['service']) && count($this->params['service']) === 1) {
                $serviceLabel = Service::whereIn('uuid', $this->params['service'])->first()?->service ?? '';
            }

            $suffix = '';
            if ($serviceLabel !== '') {
                $slug = Str::slug($serviceLabel, '_');
                if ($slug !== '') {
                    $suffix = '_' . $slug;
                }
            }

            $filePath = 'exports/' . now()->format('YmdHis') . $suffix . '_productions.xlsx';

            // Exporta
            $disk->makeDirectory('exports');
            $stored = (new ProductionsExportList($query, $rowEstimate))->store($filePath, 'local');

            // Notifica sucesso
            if ($stored && $user && $disk->exists($filePath)) {
                $serviceText = $serviceLabel ? (' para ' . $serviceLabel) : '';
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Produções' . $serviceText . ' está pronto para download.<br><br>Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

        } catch (Throwable $e) {
            Log::error('ExportProductionJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);

            if ($filePath && isset($disk) && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportProductionJob FAILED', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de Produções falhou após novas tentativas. Nossa equipe já foi informada. Tente novamente mais tarde.',
                null,
                5,
                []
            ));
        }
    }
}
