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
            // === Base Query (idêntica à tela, mas sem joins desnecessários)
            $builder = Production::query()
                ->where('service_id', $service->uuid)
                ->where('completed', false)
                ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
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
                ->with([
                    'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                    'service:id,uuid,service',
                    'user:id,name',
                    'note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left'
                ]);

            // 🔍 Aplicar filtros
            if (!empty($this->params['search'])) {
                $s = '%' . $this->params['search'] . '%';
                $builder->where(function ($q) use ($s) {
                    $q->where('n.note', 'like', $s)
                      ->orWhere('n.rubrica', 'like', $s)
                      ->orWhere('n.lexp', 'like', $s);
                });
            }

            if (!empty($this->params['multiSearch'])) {
                $ms = (array) $this->params['multiSearch'];
                $builder->whereHas('note', fn ($q) => $q->whereIn('note', $ms));
            }

            if (!empty($this->params['note_type'])) {
                $builder->where('n.type_note', $this->params['note_type']);
            }

            // === Exporta ===
            (new DispatchSurveyStack($builder, $service->uuid))
                ->store($filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Levantamento foi gerado com sucesso.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }

        } catch (Throwable $e) {
            Log::error('ExportDispatchSurveyJob falhou', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'error' => $e->getMessage(),
            ]);

            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Erro na exportação',
                    'Não foi possível gerar o relatório de Levantamento no momento. Tente novamente.',
                    null,
                    5,
                    []
                ));
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de Levantamento falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
