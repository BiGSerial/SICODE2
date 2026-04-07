<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\DispatchSurveyStack;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportDispatchSurveyJob implements ShouldQueue
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
        $service = Service::where('uuid', $this->params['service_uuid'] ?? $this->params['service_id'] ?? null)->first();

        if (!$service) {
            throw new \RuntimeException('Serviço não encontrado para exportação.');
        }

        $filePath = 'exports/' . now()->format('Ymd_His') . '_survey_' . $service->id . '.xlsx';

        try {
            // =====================
            // 🔍 MESMA BASE DA TELA
            // =====================
            $pzoExpr = "
                CASE
                WHEN n.type_note = 1
                AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$' THEN
                    CASE
                    WHEN CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) BETWEEN 1 AND 12 THEN
                        DATE_ADD(
                            DATE_ADD(
                                MAKEDATE(
                                    CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED), 1
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

            $builder = Production::query()
                ->where('service_id', $service->uuid)
                ->where('completed', false)
                ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
                ->addSelect('productions.*')
                ->addSelect(DB::raw("$pzoExpr AS pzo"))
                ->addSelect(DB::raw("n.dt_created AS dt_created"))
                ->with([
                    'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                    'service:id,uuid,service',
                    'user:id,name',
                    'note:id,note,dt_created,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2'
                ]);

            // =====================
            // 🔍 FILTROS
            // =====================
            if (!empty($this->params['search'])) {
                $s = '%' . $this->params['search'] . '%';
                $builder->where(function ($q) use ($s) {
                    $q->where('n.note', 'like', $s)
                        ->orWhere('n.rubrica', 'like', $s)
                        ->orWhere('n.lexp', 'like', $s)
                        ->orWhere('productions.odi', 'like', $s)
                        ->orWhere('productions.odd', 'like', $s)
                        ->orWhere('productions.ods', 'like', $s)
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $s))
                        ->orWhereHas('note.orders', fn ($oq) => $oq->where('ordem', 'like', $s));
                });
            }

            if (!empty($this->params['multiSearch'])) {
                $ms = (array) $this->params['multiSearch'];
                $builder->where(function ($q) use ($ms) {
                    $q->whereHas('note', function ($query) use ($ms) {
                        $query->whereIn('note', $ms)
                            ->orWhereIn('rubrica', $ms)
                            ->orWhereIn('lexp', $ms);
                    })
                    ->orWhereHas('user', fn ($uq) => $uq->whereIn('name', $ms))
                    ->orWhereHas('note.orders', fn ($oq) => $oq->whereIn('ordem', $ms));
                });
            }

            if (!empty($this->params['note_type'])) {
                $builder->where('n.type_note', $this->params['note_type']);
            }

            // =====================
            // 📤 EXPORTAÇÃO USANDO CLASSE
            // =====================
            (new DispatchSurveyStack($builder, $service->uuid))
                ->store($filePath, 'local');

            // =====================
            // 🔔 NOTIFICAÇÃO AO USUÁRIO
            // =====================
            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de levantamento foi gerado com sucesso.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }

        } catch (Throwable $e) {
            Log::error('ExportDispatchSurveyJob falhou', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de levantamento falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
