<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\DispatchSupervisionStack;
use App\Models\Service;
use App\Models\User;
use App\Models\Production;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;
use Carbon\Carbon;

class ExportDispatchSupervisionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;
    public string $userId;

    public $tries = 2;
    public $backoff = [60, 180];

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $service = Service::where('uuid', $this->params['service_id'])->first();

        if (!$service) {
            throw new \RuntimeException('Serviço não encontrado para exportação.');
        }

        $disk     = Storage::disk('local');
        $filePath = 'exports/' . now()->format('Ymd_His') . '_supervision_' . $service->uuid . '.xlsx';
        $disk->makeDirectory(dirname($filePath));

        try {
            // === Base query igual à da tela ===
            $builder = Production::query()
                ->where('service_id', $service->uuid)
                ->where('completed', false)
                ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
                ->leftJoin('work_reports as wr', 'n.id', '=', 'wr.note_id')
                ->leftJoin('adsforms as af', 'wr.id', '=', 'af.work_report_id')
                ->addSelect('productions.*')
                ->addSelect(DB::raw("
                    CASE
                        WHEN n.type_note = 1 AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$'
                        THEN DATE_ADD(
                            MAKEDATE(CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED), 1),
                            INTERVAL (CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) - 1) MONTH
                        ) + INTERVAL 27 DAY
                        WHEN n.type_note = 2 THEN DATE_ADD(CURDATE(), INTERVAL COALESCE(n.days_left, 0) DAY)
                        ELSE NULL
                    END AS pzo
                "))
                ->addSelect(DB::raw('af.created_at AS dt_ads'))
                ->with([
                    'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                    'service:id,uuid,service',
                    'user:id,name',
                    'note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left',
                    'note.workform:id,company_id,note_id,informed_at,rejected',
                    'note.workform.adsform:id,work_report_id,amount,created_at',
                    'note.orders:id,note_id,moaberto'
                ]);

            // filtros simples (igual à tela)
            if (!empty($this->params['search'])) {
                $search = '%' . $this->params['search'] . '%';
                $builder->where(function ($q) use ($search) {
                    $q->where('n.note', 'like', $search)
                        ->orWhere('n.rubrica', 'like', $search)
                        ->orWhere('n.lexp', 'like', $search);
                });
            }

            if (!empty($this->params['multiSearch'])) {
                $multi = (array) $this->params['multiSearch'];
                $builder->whereHas('note', fn ($q) => $q->whereIn('note', $multi));
            }

            if (!empty($this->params['note_type'])) {
                $builder->where('n.type_note', $this->params['note_type']);
            }

            // === Executa exportação ===
            $stored = (new DispatchSupervisionStack($builder, $service->uuid))
                ->store($filePath, 'local');

            // === Notificação ===
            if ($stored && $user && $disk->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Fiscalização foi gerado com sucesso.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

        } catch (Throwable $e) {
            Log::error('ExportDispatchSupervisionJob falhou', [
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

    public function failed(Throwable $e): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de Fiscalização falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
