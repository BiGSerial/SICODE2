<?php

namespace App\Jobs\Services;

use App\Exports\ProductionServiceExport;
use App\Models\Production;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportLevantamentoProductionListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;

    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(array $params)
    {
        $this->onQueue('exports');
        $this->params = $params;
    }

    public function handle(): void
    {
        $user = User::find($this->params['user_id'] ?? null);
        $filePath = null;

        try {
            $pzoExpr = "
                CASE
                    WHEN n.type_note = 1
                    AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$' THEN
                        CASE
                            WHEN CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) BETWEEN 1 AND 12 THEN
                                DATE_ADD(
                                    DATE_ADD(
                                        MAKEDATE(
                                            CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED),
                                            1
                                        ),
                                        INTERVAL (CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) - 1) MONTH
                                    ),
                                    INTERVAL 27 DAY
                                )
                            ELSE NULL
                        END
                    WHEN n.type_note = 2 THEN
                        DATE_ADD(CURDATE(), INTERVAL COALESCE(n.days_left, 0) DAY)
                    ELSE NULL
                END
            ";

            $rows = Production::query()
                ->select([
                    'productions.id',
                    'productions.note_id',
                    'productions.status',
                    'productions.priority',
                    'productions.att_at',
                    'productions.completed',
                    'productions.block',
                    'productions.block_wpa',
                    'productions.transferred',
                    'n.note',
                    'n.material',
                    'n.group1',
                    'n.group2',
                    'n.rubrica',
                    'n.lexp',
                    'n.days_left',
                    'n.mmgd',
                    'n.dt_created',
                    DB::raw("$pzoExpr AS pzo"),
                ])
                ->join('notes as n', 'n.id', '=', 'productions.note_id')
                ->where('productions.service_id', $this->params['service_uuid'])
                ->where('productions.user_id', $this->params['user_id'])
                ->where('productions.completed', false)
                ->when($this->params['search'] ?? null, function ($q, $s) {
                    $q->where(function ($sub) use ($s) {
                        $sub->where('n.note', 'like', "%{$s}%")
                            ->orWhere('n.material', 'like', "%{$s}%");
                    });
                })
                ->addSelect('n.dt_created as dt_created')
                ->orderByDesc('productions.priority')
                ->orderBy('n.dt_created')
                ->orderBy('productions.id', 'DESC')
                ->with([
                    'Wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                    'Service:id,uuid,service',
                    'User:id,name',
                    'Note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,dt_created,material,group2',
                ])
                ->get();

            Storage::disk('local')->makeDirectory('exports');
            $filePath = 'exports/' . now()->format('YmdHis') . '_levantamento_production_services.xlsx';

            Excel::store(new ProductionServiceExport($rows), $filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Levantamento está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportLevantamentoProductionListJob falhou', [
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação falhou',
                    'Não foi possível gerar o relatório de Levantamento. Tente novamente.',
                    null,
                    5,
                    []
                ));
            }
        }
    }
}
