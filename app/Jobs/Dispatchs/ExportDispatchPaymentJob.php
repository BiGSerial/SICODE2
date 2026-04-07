<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\DispatchPaymentMain;
use App\Models\Note;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Payment\NoteFilter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExportDispatchPaymentJob implements ShouldQueue
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

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user        = User::find($this->userId);
        $filePath    = null;
        $serviceUuid = (string)($this->params['service_uuid'] ?? '');
        $service     = Service::where('uuid', $serviceUuid)->first();

        try {
            if (!$service) {
                throw new \RuntimeException('Serviço inválido para export.');
            }

            // --- Injeta filtros no NoteFilter via sessão (o NoteFilter lê de $_SESSION['filter'][$group]) ---
            if (\PHP_SESSION_ACTIVE !== session_status()) {
                @session_start();
            }
            $_SESSION['filter']['payments'] = [
                'company' => $this->params['company_ids'] ?? null,
                'rubrica' => $this->params['rubricas']    ?? null,
                'city'    => $this->params['cities']      ?? null,
            ];

            /** @var NoteFilter $noteFilter */
            $noteFilter = app(NoteFilter::class);

            // === Base da consulta (espelha seu baseQuery()) ===
            $base = $noteFilter
                ->filter($this->params['search'] ?? null, 'payments')
                ->select([
                    'notes.id',
                    'notes.note',
                    'notes.lexp',
                    'notes.mesalization',
                    'notes.days_left',
                    'notes.type_note',
                    'notes.nstats',
                    'notes.dt_status',
                    DB::raw('(SELECT COALESCE(SUM(o.moaberto),0) FROM orders o WHERE o.note_id = notes.id) AS total_moaberto'),
                ]);

            // latest_ops
            $latestOps = DB::table('operation_resps')
                ->select('note_id', DB::raw('MAX(fimLancado) AS latest_fimLancado'))
                ->groupBy('note_id');

            // latest_partials
            $latestPartialBase = DB::table('partials as p')
                ->selectRaw("
                    p.note_id,
                    p.supervision_at,
                    ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.id DESC) AS rn
                ")
                ->where('p.allow', 1)
                ->where('p.deny', 0)
                ->where('p.supervision', 1);
            $latestPartials = DB::query()
                ->fromSub($latestPartialBase, 't')
                ->select('t.note_id', 't.supervision_at')
                ->where('t.rn', 1);

            // latest production por serviço
            $latestProdBase = DB::table('productions as p')
                ->selectRaw("
                    p.note_id,
                    p.id            AS latest_prod_id,
                    p.user_id       AS latest_user_id,
                    p.completed     AS latest_completed,
                    p.status        AS latest_status,
                    p.partial       AS latest_partial,
                    p.confirmed     AS latest_confirmed,
                    p.dfive         AS latest_dfive,
                    p.created_at    AS latest_created_at,
                    p.completed_at  AS latest_completed_at,
                    p.dhstats       AS latest_dhstats,
                    p.dt_note       AS latest_dt_note,
                    p.status_note   AS latest_status_note,
                    ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.created_at DESC, p.id DESC) AS rn
                ")
                ->where('p.service_id', $service->uuid);
            $latestProd = DB::query()
                ->fromSub($latestProdBase, 'u')
                ->select([
                    'u.note_id',
                    'u.latest_prod_id',
                    'u.latest_user_id',
                    'u.latest_completed',
                    'u.latest_status',
                    'u.latest_partial',
                    'u.latest_confirmed',
                    'u.latest_dfive',
                    'u.latest_created_at',
                    'u.latest_completed_at',
                    'u.latest_dhstats',
                    'u.latest_dt_note',
                    'u.latest_status_note',
                ])
                ->where('u.rn', 1);

            // JOINs
            $base->leftJoinSub($latestOps, 'latest_ops', fn ($j) => $j->on('notes.id', '=', 'latest_ops.note_id'));
            $base->leftJoinSub($latestPartials, 'latest_partials', fn ($j) => $j->on('notes.id', '=', 'latest_partials.note_id'));
            $base->leftJoinSub($latestProd, 'lp', fn ($j) => $j->on('notes.id', '=', 'lp.note_id'));

            // fimLancado
            $base->addSelect(DB::raw("
                CASE
                  WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                    THEN latest_ops.latest_fimLancado
                  ELSE latest_partials.supervision_at
                END AS fimLancado
            "));

            // sort_bucket
            $base->addSelect(DB::raw("
                CASE
                  WHEN latest_partials.supervision_at IS NOT NULL
                       AND NOT EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                    THEN 0
                  WHEN EXISTS (
                        SELECT 1 FROM five_notes as fn
                        WHERE fn.note_id = notes.id
                          AND fn.is_supervisioned = 1
                          AND fn.is_completed    = 1
                          AND fn.is_archived     = 0
                    )
                    THEN 1
                  WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                    THEN 2
                  ELSE 3
                END AS sort_bucket
            "));

            // Filtros dinâmicos
            if (!empty($this->params['not_assigned'])) {
                $base->where(function ($q) {
                    $q->whereNull('lp.latest_prod_id')
                      ->orWhereNull('lp.latest_user_id')
                      ->orWhere('lp.latest_user_id', 0);
                });
            }

            // multiSearch / search
            if (!empty($this->params['multiSearch'])) {
                $ms = array_values(array_filter($this->params['multiSearch']));
                $base->where(function ($q) use ($ms) {
                    $q->whereIn('notes.note', $ms)
                      ->orWhereExists(function ($sq) use ($ms) {
                          $sq->select(DB::raw(1))
                             ->from('orders')
                             ->whereColumn('orders.note_id', 'notes.id')
                             ->whereIn('orders.ordem', $ms);
                      });
                });
            } elseif (!empty($this->params['search'])) {
                $s = '%' . $this->params['search'] . '%';
                $base->where(function ($q) use ($s) {
                    $q->where('notes.note', 'like', $s)
                      ->orWhereExists(function ($sq) use ($s) {
                          $sq->select(DB::raw(1))
                             ->from('orders')
                             ->whereColumn('orders.note_id', 'notes.id')
                             ->where('orders.ordem', 'like', $s);
                      });
                });
            }

            if (!empty($this->params['typeNote'])) {
                $base->where('notes.type_note', $this->params['typeNote']);
            }

            // exibir apenas quem tem D5 (se solicitado)
            if (!empty($this->params['filter_d5'])) {
                $base->whereExists(function ($sq) {
                    $sq->select(DB::raw(1))
                       ->from('five_notes as fn')
                       ->whereColumn('fn.note_id', 'notes.id');
                });
            }

            // Ordenação
            $base->orderBy('sort_bucket', 'ASC')
                 ->orderByRaw('(fimLancado IS NULL) DESC')
                 ->orderBy('fimLancado', 'ASC');

            // === Builder final (sem paginate) ===
            /** @var Builder $builder */
            $builder = $base;

            // Caminho/arquivo
            $serviceSuffix = '_' . preg_replace('/\s+/', '_', mb_strtolower($service->service));
            $filePath      = 'exports/' . now()->format('YmdHis') . "{$serviceSuffix}_dispatch_payment.xlsx";

            // Exporta (armazenando em disco local)
            (new DispatchPaymentMain($builder, $service->uuid))->store($filePath, 'local');

            // Notifica sucesso
            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Pagamentos/Despachos está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

        } catch (Throwable $e) {
            Log::error('ExportDispatchPaymentJob falhou', [
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
        Log::critical('ExportDispatchPaymentJob FAILED', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório de Pagamentos falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
