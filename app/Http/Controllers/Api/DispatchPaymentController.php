<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Note, Service};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchPaymentController extends Controller
{
    public function index(Request $request, string $service)
    {
        $validated = $request->validate([
            'search'       => ['nullable','string','max:255'],
            'multi'        => ['nullable','array'],
            'multi.*'      => ['string','max:50'],
            'typeNote'     => ['nullable','string','max:50'],
            'not_assigned' => ['nullable','boolean'],
            'filter_d5'    => ['nullable','boolean'],
            'per_page'     => ['nullable','integer','min:1','max:500'],
            'page'         => ['nullable','integer','min:1'],
            // novos “toggles”
            'light'        => ['nullable','boolean'],   // usa simplePaginate
            'eval'         => ['nullable','boolean'],   // roda BlockEvaluator
            'include'      => ['nullable','string'],    // ex: wf,orders,ops,prod,five
        ]);

        $serviceModel = Service::where('uuid', $service)->firstOrFail();

        $search      = $validated['search'] ?? null;
        $multi       = $validated['multi'] ?? [];
        $typeNote    = $validated['typeNote'] ?? null;
        $notAssigned = (bool)($validated['not_assigned'] ?? false);
        $filterD5    = (bool)($validated['filter_d5'] ?? false);
        $perPage     = (int)($validated['per_page'] ?? 50); // default menor já ajuda
        $useSimple   = (bool)($validated['light'] ?? false);
        $withEval    = (bool)($validated['eval']  ?? false);
        $includes    = collect(explode(',', $validated['include'] ?? ''))
                        ->map(fn ($s) => trim($s))->filter()->values()->all();

        $safeSearch = $search ? str_replace(['%','_'], ['\\%','\\_'], $search) : null;

        // Query base SEM eager loading (mais barata)
        $base = Note::query()
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

        // latest_partials (último id que cumpre as flags)
        $latestPartialMax = DB::table('partials')
            ->select('note_id', DB::raw('MAX(id) AS max_id'))
            ->where('allow', 1)->where('deny', 0)->where('supervision', 1)
            ->groupBy('note_id');

        $latestPartials = DB::table('partials as p')
            ->joinSub($latestPartialMax, 'm', fn ($j) => $j->on('p.id', '=', 'm.max_id'))
            ->select('p.note_id', 'p.supervision_at');

        // latest production POR SERVIÇO (filtro recolocado!)
        $latestProdMax = DB::table('productions')
            ->select('note_id', DB::raw('MAX(id) AS max_id'))
            ->where('service_id', $serviceModel->uuid)
            ->groupBy('note_id');

        $latestProd = DB::table('productions as p')
            ->joinSub($latestProdMax, 'm', fn ($j) => $j->on('p.id', '=', 'm.max_id'))
            ->select([
                'p.note_id',
                'p.id          AS latest_prod_id',
                'p.user_id     AS latest_user_id',
                'p.completed   AS latest_completed',
                'p.status      AS latest_status',
                'p.partial     AS latest_partial',
                'p.confirmed   AS latest_confirmed',
                'p.dfive       AS latest_dfive',
                'p.created_at  AS latest_created_at',
                'p.completed_at AS latest_completed_at',
                'p.dhstats     AS latest_dhstats',
                'p.dt_note     AS latest_dt_note',
                'p.status_note AS latest_status_note',
            ]);

        $base->leftJoinSub($latestOps, 'latest_ops', fn ($j) => $j->on('notes.id', '=', 'latest_ops.note_id'));
        $base->leftJoinSub($latestPartials, 'latest_partials', fn ($j) => $j->on('notes.id', '=', 'latest_partials.note_id'));
        $base->leftJoinSub($latestProd, 'lp', fn ($j) => $j->on('notes.id', '=', 'lp.note_id'));

        // indicador se existe work_report (evita EXISTS por linha)
        $wrNotes = DB::table('work_reports')->select('note_id')->distinct();
        $base->leftJoinSub($wrNotes, 'wr', fn ($j) => $j->on('wr.note_id', '=', 'notes.id'));

        // fimLancado
        $base->addSelect(DB::raw("
        CASE WHEN wr.note_id IS NOT NULL
             THEN latest_ops.latest_fimLancado
             ELSE latest_partials.supervision_at
        END AS fimLancado
    "));

        // sort_bucket (usa joins em vez de EXISTS)
        $base->addSelect(DB::raw("
        CASE
          WHEN latest_partials.supervision_at IS NOT NULL AND wr.note_id IS NULL
            THEN 0
          WHEN EXISTS (
                SELECT 1 FROM five_notes fn
                WHERE fn.note_id = notes.id
                  AND fn.is_supervisioned = 1
                  AND fn.is_completed    = 1
                  AND fn.is_archived     = 0
          )
            THEN 1
          WHEN wr.note_id IS NOT NULL
            THEN 2
          ELSE 3
        END AS sort_bucket
    "));

        // filtros
        if ($notAssigned) {
            $base->where(function ($q) {
                $q->whereNull('lp.latest_prod_id')
                  ->orWhereNull('lp.latest_user_id')
                  ->orWhere('lp.latest_user_id', 0);
            });
        }

        if (!empty($multi)) {
            $ms = array_values(array_filter($multi, fn ($v) => $v !== ''));
            $base->where(function ($q) use ($ms) {
                $q->whereIn('notes.note', $ms)
                  ->orWhereExists(function ($sq) use ($ms) {
                      $sq->select(DB::raw(1))
                         ->from('orders')
                         ->whereColumn('orders.note_id', 'notes.id')
                         ->whereIn('orders.ordem', $ms);
                  });
            });
        } elseif ($safeSearch) {
            $like = "%{$safeSearch}%";
            $base->where(function ($q) use ($like) {
                $q->where('notes.note', 'like', $like)
                  ->orWhereExists(function ($sq) use ($like) {
                      $sq->select(DB::raw(1))
                         ->from('orders')
                         ->whereColumn('orders.note_id', 'notes.id')
                         ->where('orders.ordem', 'like', $like);
                  });
            });
        }

        if ($typeNote) {
            $base->where('notes.type_note', $typeNote);
        }

        if ($filterD5) {
            $base->whereExists(function ($sq) {
                $sq->select(DB::raw(1))
                   ->from('five_notes as fn')
                   ->whereColumn('fn.note_id', 'notes.id');
            });
        }

        $base->orderBy('sort_bucket', 'ASC')
             ->orderByRaw('(fimLancado IS NULL) DESC')
             ->orderBy('fimLancado', 'ASC');

        // paginação (rápida com simplePaginate quando ?light=1)
        $page = $useSimple
            ? $base->simplePaginate($perPage)->appends($request->query())
            : $base->paginate($perPage)->appends($request->query());

        // inclui relações somente sob demanda
        $collection = $page->getCollection();
        if (!empty($includes)) {
            $relations = [];
            if (in_array('wf', $includes, true)) {
                $relations[] = 'WorkForm.Company';
            }
            if (in_array('orders', $includes, true)) {
                $relations[] = 'WorkForm.Orders';
                $relations[] = 'Partials.Orders';
            }
            if (in_array('ops', $includes, true)) {
                $relations[] = 'WorkForm.Orders.Operations';
                $relations[] = 'Partials.Orders.Operations';
            }
            if (in_array('five', $includes, true)) {
                $relations[] = 'FiveNote';
            }
            if (in_array('prod', $includes, true)) {
                // produção limitada ao serviço
                $collection->load(['Productions' => fn ($q) => $q->where('service_id', $serviceModel->uuid)
                                                               ->with('User')->latest()]);
            }
            if (!empty($relations)) {
                $collection->load($relations);
            }
        }

        // avaliação opcional (expensive) – só quando ?eval=1
        if ($withEval) {
            $evaluator = app(\App\Services\Payment\BlockEvaluator::class);
            $collection = $collection->map(function ($note) use ($evaluator, $serviceModel) {
                $ev = $evaluator->evaluate($note, $serviceModel);
                $note->setAttribute('eval', [
                    'service_id' => $serviceModel->uuid,
                    'block'      => (bool)($ev['block'] ?? false),
                    'color'      => (string)($ev['color'] ?? ''),
                    'command'    => (bool)($ev['command'] ?? false),
                    'reason'     => $ev['reason'] ?? null,
                    'by'         => optional($ev['production']?->User)->name,
                    'prod_id'    => $ev['production']->id ?? null,
                    'dt_note'    => optional($ev['production'])->dt_note?->toISOString(),
                ]);
                return $note;
            });
            $page->setCollection($collection);
        }

        return response()->json([
            'data'  => $page->items(),
            'meta'  => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $useSimple ? null : $page->total(),
                'last_page'    => $useSimple ? null : $page->lastPage(),
                'simple'       => $useSimple,
            ],
            'links' => [
                'first' => $useSimple ? null : $page->url(1),
                'last'  => $useSimple ? null : $page->url($useSimple ? null : $page->lastPage()),
                'prev'  => $page->previousPageUrl(),
                'next'  => $page->nextPageUrl(),
            ],
        ]);
    }

}
