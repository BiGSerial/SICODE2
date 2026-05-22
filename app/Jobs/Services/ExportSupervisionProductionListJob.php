<?php

namespace App\Jobs\Services;

use App\Exports\ProductionServiceExport;
use App\Models\Production;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportSupervisionProductionListJob implements ShouldQueue
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
        $user = User::find($this->params['request_user_id'] ?? null);
        $filePath = null;

        try {
            $daysAssignedExpr = "DATEDIFF(CURDATE(), productions.att_at)";
            $daysLeftExpr = "IFNULL(DATEDIFF(CURDATE(), work_reports.informed_at), 0)";

            $targetUserId = $this->params['target_user_id'] ?? $this->params['request_user_id'];

            $rows = Production::query()
                ->with([
                    'Note:id,note,material,mmgd,rubrica,lexp,postes,dt_status',
                    'Note.WorkForm:id,note_id,informed_at,rejected',
                    'Note.WorkForm.Orders' => fn ($q) => $q->select('orders.id', 'orders.ordem'),
                    'Note.OldAds:id,note_id',
                    'Note.Adsform:id,note_id',
                    'Wpas:id,production_id,dd,created_at',
                    'Note.Files:id,service_id,note_id,file_name,path,ext',
                ])
                ->leftJoin('work_reports', 'work_reports.note_id', '=', 'productions.note_id')
                ->where('productions.service_id', $this->params['service_uuid'])
                ->where('productions.user_id', $targetUserId)
                ->where('productions.completed', false)
                ->when($this->params['search'] ?? null, function (Builder $q, $search) {
                    $q->where(function (Builder $sub) use ($search) {
                        $sub->whereRelation('Note', 'note', 'like', "%{$search}%")
                            ->orWhereRelation('Note', 'material', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('priority')
                ->orderByDesc('partial')
                ->orderBy('work_dt_created', 'ASC')
                ->orderBy('att_at', 'DESC')
                ->orderBy('status', 'ASC')
                ->orderBy('productions.id', 'DESC')
                ->select([
                    'productions.id',
                    'productions.service_id',
                    'productions.user_id',
                    'productions.note_id',
                    'productions.status',
                    'productions.priority',
                    'productions.partial',
                    'productions.dfive',
                    'productions.block',
                    'productions.block_wpa',
                    'productions.completed',
                    'productions.att_at',
                    'productions.transferred',
                    'work_reports.created_at as work_dt_created',
                ])
                ->selectRaw("$daysAssignedExpr as days_assigned")
                ->selectRaw("$daysLeftExpr as days_left")
                ->get();

            Storage::disk('local')->makeDirectory('exports');
            $filePath = 'exports/' . now()->format('YmdHis') . '_supervision_production_services.xlsx';

            Excel::store(new ProductionServiceExport($rows), $filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Supervisão está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportSupervisionProductionListJob falhou', [
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
                    'Não foi possível gerar o relatório de Supervisão. Tente novamente.',
                    null,
                    5,
                    []
                ));
            }
        }
    }
}
