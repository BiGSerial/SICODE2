<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\ReturnWorkReportsExport;
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

class ExportReturnWorkReportsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;
    public string $userId;

    public $tries = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200;

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
            $dtIn = Carbon::parse($this->params['dt_in'] ?? now()->startOfMonth())->startOfDay();
            $dtOut = Carbon::parse($this->params['dt_out'] ?? now())->endOfDay();
            $search = $this->params['search'] ?? null;
            $categoryValues = $this->params['categoryValues'] ?? [];
            $companyIds = $this->params['companyIds'] ?? [];
            $serviceIds = $this->params['serviceIds'] ?? [];

            /** @var BaseBuilder $builder */
            $builder = DB::table('return_works as rw')
                ->join('work_reports as wr', 'wr.id', '=', 'rw.work_report_id')
                ->leftJoin('notes as n', 'n.id', '=', 'wr.note_id')
                ->leftJoin('companies as wr_company', 'wr_company.id', '=', 'wr.company_id')
                ->leftJoin('services as s', 's.uuid', '=', 'rw.service_id')
                ->leftJoin('users as rejector_u', 'rejector_u.id', '=', 'rw.user_id')
                ->leftJoin('users as creator_u', 'creator_u.id', '=', 'wr.user_id')
                ->leftJoin('employees as e', 'e.user_id', '=', 'creator_u.id')
                ->leftJoin('contracts as ct', 'ct.id', '=', 'e.contract_id')
                ->leftJoin('companies as creator_contract_company', 'creator_contract_company.id', '=', 'ct.company_id')
                ->leftJoin('companies as creator_user_company', 'creator_user_company.id', '=', 'creator_u.company_id')
                ->whereBetween('rw.created_at', [$dtIn, $dtOut])
                ->when(!empty($categoryValues), fn ($q) => $q->whereIn('rw.category', $categoryValues))
                ->when(!empty($serviceIds), fn ($q) => $q->whereIn('rw.service_id', $serviceIds))
                ->when(!empty($companyIds), fn ($q) => $q->whereIn('wr.company_id', $companyIds))
                ->when(!empty($search), function ($q) use ($search) {
                    $term = '%' . $search . '%';
                    $q->where(function ($sq) use ($term) {
                        $sq->where('n.note', 'like', $term)
                            ->orWhereExists(function ($orderSq) use ($term) {
                                $orderSq->selectRaw('1')
                                    ->from('order_work_report as owr')
                                    ->join('orders as o', 'o.id', '=', 'owr.order_id')
                                    ->whereColumn('owr.work_report_id', 'rw.work_report_id')
                                    ->where('o.ordem', 'like', $term);
                            });
                    });
                })
                ->select([
                    'rw.created_at as rejected_at',
                    'rw.work_report_id as workreport_id',
                    DB::raw('COALESCE(n.note, "—") as note_number'),
                    DB::raw('COALESCE(wr_company.name, "—") as contractor'),
                    DB::raw('COALESCE(s.service, "—") as service_name'),
                    DB::raw('COALESCE(rw.category, "—") as reject_category'),
                    DB::raw('COALESCE(rejector_u.name, "—") as rejector_name'),
                    DB::raw('COALESCE(NULLIF(wr.informer, ""), creator_u.name, "—") as informer_name'),
                    DB::raw('COALESCE(creator_u.name, "—") as creator_name'),
                    DB::raw('COALESCE(creator_contract_company.name, creator_user_company.name, "—") as creator_company'),
                    'wr.created_at as workreport_created_at',
                    DB::raw('COALESCE(rw.text_obs, "—") as reject_observation'),
                ])
                ->orderByDesc('rw.created_at');

            $stamp = now()->format('YmdHis');
            $file = "return_work_reports_{$stamp}.xlsx";
            $filePath = "exports/{$file}";

            (new ReturnWorkReportsExport($builder))->store($filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    "Relatório de informes rejeitados ({$dtIn->format('d/m/Y')} a {$dtOut->format('d/m/Y')}) pronto para download.",
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportReturnWorkReportsJob falhou', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportReturnWorkReportsJob FAILED', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de informes rejeitados falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
