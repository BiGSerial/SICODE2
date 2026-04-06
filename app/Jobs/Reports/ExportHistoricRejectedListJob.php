<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\HistoricRejectedListExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportHistoricRejectedListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;
    public string $userId;

    public $tries   = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200; // 20 min

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $dtIn  = Carbon::parse($this->params['dt_in'])->startOfDay();
            $dtOut = Carbon::parse($this->params['dt_out'])->endOfDay();
            $searchNote = $this->params['searchNote'] ?? null;
            $reason = $this->params['reason'] ?? null;
            $companyIds = $this->params['companyIds'] ?? [];

            // ===== Builder com filtros e joins =====
            /** @var BaseBuilder $builder */
            $builder = DB::table('return_works as rw')
                ->join('work_reports as wr', 'wr.id', '=', 'rw.work_report_id')
                ->leftJoin('companies as c', 'c.id', '=', 'wr.company_id')
                ->leftJoin('notes as n', 'n.id', '=', 'wr.note_id')
                ->whereBetween('rw.created_at', [$dtIn, $dtOut])
                ->when(!empty($searchNote), function ($q) use ($searchNote) {
                    $q->where('n.note', 'like', '%' . $searchNote . '%');
                })
                ->when(!empty($reason), function ($q) use ($reason) {
                    $q->where(function ($sq) use ($reason) {
                        $sq->where('rw.category', 'like', '%' . $reason . '%')
                            ->orWhere('rw.text_obs', 'like', '%' . $reason . '%');
                    });
                })
                ->when(!empty($companyIds), function ($q) use ($companyIds) {
                    $q->whereIn('wr.company_id', $companyIds);
                })
                ->select([
                    'rw.created_at as opened_at',
                    DB::raw('COALESCE(n.note, "—")  as note_number'),
                    DB::raw('COALESCE(c.name, "—") as company_name'),
                    DB::raw('COALESCE(rw.category, "Sem categoria") as category'),
                    'rw.text_obs as observation',
                ])
                ->orderByDesc('rw.created_at');

            // ===== Nome do arquivo (disco local) =====
            $stamp   = now()->format('YmdHis');
            $file    = "historic_rejected_{$stamp}.xlsx";
            $filePath = "exports/{$file}";

            // ===== Export =====
            (new HistoricRejectedListExport($builder))
                ->store($filePath, 'local'); // requer storage:link p/ url pública

            // ===== Notifica sucesso =====
            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    "Histórico de retornos ({$dtIn->format('d/m/Y')} a {$dtOut->format('d/m/Y')}) pronto para download.",
                    Storage::url($filePath), // gera /storage/exports/...
                    4, // prioridade/nível, adapte ao seu padrão
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

        } catch (Throwable $e) {
            Log::error('ExportHistoricRejectedListJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportHistoricRejectedListJob FAILED', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de Retornos falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
