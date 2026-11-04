<?php

namespace App\Services\Supervision;

use App\Models\{Production, User, WorkReportFlowProduction};
use App\Support\SicodeRules;
use App\Traits\WildcardFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Consulta única do Controle de Fiscalização: a tela (`Dispatchs\Supervision\Stack`) e o Excel (`ExportDispatchSupervisionJob`)
 * leem daqui, então o arquivo traz exatamente o que a tela mostraria com os mesmos filtros.
 *
 * Critérios (`$criteria`, só valores simples para poder ir para a fila): `service` (uuid da atividade), `search`, `multiSearch`,
 * `note_type`, `users` (ids), `status` (códigos), `cityCodes` (null = sem filtro de localidade).
 */
class SupervisionControlQuery
{
    use WildcardFormatter;

    /** @return array{service: string, search: string, multiSearch: array, note_type: string, users: array, status: array, cityCodes: ?array} */
    public function normalize(array $criteria): array
    {
        return [
            'service'     => (string) ($criteria['service'] ?? ''),
            'search'      => trim((string) ($criteria['search'] ?? '')),
            'multiSearch' => array_values(array_filter((array) ($criteria['multiSearch'] ?? []))),
            'note_type'   => (string) ($criteria['note_type'] ?? ''),
            'users'       => array_values(array_map('strval', (array) ($criteria['users'] ?? []))),
            'status'      => array_values(array_map('strval', (array) ($criteria['status'] ?? []))),
            'cityCodes'   => isset($criteria['cityCodes']) ? array_values((array) $criteria['cityCodes']) : null,
        ];
    }

    /**
     * Recorte da lista e dos cartões de status. `$withStatus = false` ignora o filtro de status (os cartões contam todos).
     * `$user` é quem vê: parceira só enxerga as empresas do contrato dela.
     */
    public function base(array $criteria, ?User $user, bool $withStatus = true): Builder
    {
        $c = $this->normalize($criteria);

        $dispatchableWorkReportIds = app(WorkReportSupervisionCandidateQuery::class)->dispatchableWorkReportIdsSubquery();

        return Production::Query()
            ->where('service_id', $c['service'])
            ->where('completed', false)
            ->when($user?->usesContractDispatch(), function ($q) use ($user) {
                $companyIds = SicodeRules::visibleCompanyIdsFor($user);

                return count($companyIds)
                    ? $q->whereIn('productions.company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->leftJoin('work_report_flow_productions as wrfp', function ($join) {
                $join->on('wrfp.production_id', '=', 'productions.id')
                    ->where('wrfp.stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                    ->where('wrfp.is_current', true)
                    ->whereRaw("wrfp.id = (
                        SELECT MAX(wrfp_latest.id)
                        FROM work_report_flow_productions wrfp_latest
                        WHERE wrfp_latest.production_id = productions.id
                          AND wrfp_latest.stage = 'fiscalization'
                          AND wrfp_latest.is_current = 1
                    )");
            })
            ->leftJoin('work_reports as wr', function ($join) {
                $join->on('wr.id', '=', 'wrfp.work_report_id')
                    ->where('wr.canceled', false);
            })
            ->leftJoin('adsforms as af', 'wr.id', '=', 'af.work_report_id')
            ->where(function ($query) use ($dispatchableWorkReportIds) {
                $query->whereNull('wrfp.work_report_id')
                    ->orWhereIn('wrfp.work_report_id', $dispatchableWorkReportIds);
            })
            ->when($c['search'] !== '', function ($q) use ($c) {
                $q->where(function ($q) use ($c) {
                    $search = $this->formatWithWildcard($c['search']);
                    $q->where('n.note', $search->type, $search->search)
                        ->orWhere('n.rubrica', $search->type, $search->search)
                        ->orWhere('n.lexp', $search->type, $search->search)
                        ->orWhere('productions.odi', $search->type, $search->search)
                        ->orWhere('productions.odd', $search->type, $search->search)
                        ->orWhere('productions.ods', $search->type, $search->search)
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', $search->type, $search->search);
                        })
                        ->orWhereHas('note.orders', function ($q) use ($search) {
                            $q->where('ordem', $search->type, $search->search);
                        });
                });
            })
            ->when($c['multiSearch'], function ($q) use ($c) {
                $q->where(function ($q) use ($c) {
                    $q->whereHas('note', function ($query) use ($c) {
                        $query->whereIn('note', $c['multiSearch'])
                              ->orWhere('rubrica', $c['multiSearch'])
                              ->orWhere('lexp', $c['multiSearch']);
                    })
                    ->orWhereHas('user', function ($q) use ($c) {
                        $q->whereIn('name', $c['multiSearch']);
                    })
                    ->orWhereHas('note.orders', function ($q) use ($c) {
                        $q->whereIn('ordem', $c['multiSearch']);
                    });
                });
            })
            ->when($c['cityCodes'] !== null, fn ($q) => $q->whereIn('nexp', $c['cityCodes']))
            ->when($withStatus && $c['status'], fn ($q) => $q->whereIn('productions.status', $c['status']))
            ->when($c['note_type'] !== '', fn ($q) => $q->where('n.type_note', $c['note_type']))
            ->when($c['users'], fn ($q) => $q->whereIn('productions.user_id', $c['users']));
    }

    /** Lista completa, com as colunas calculadas, as relações e a ordem da tela (sem paginar). */
    public function list(array $criteria, ?User $user): Builder
    {
        return $this->base($criteria, $user)
            ->addSelect('productions.*')
            ->addSelect(DB::raw("
                CASE
                    WHEN wr.informed_at IS NOT NULL THEN wr.informed_at
                    WHEN EXISTS (
                        SELECT 1 FROM partials p
                        WHERE p.note_id = n.id
                    ) THEN (
                        SELECT p.created_at
                        FROM partials p
                        WHERE p.note_id = n.id
                        ORDER BY p.created_at DESC
                        LIMIT 1
                    )
                    ELSE NULL
                END AS dt_inform
            "))
            ->addSelect(DB::raw('af.created_at AS dt_ads'))
            ->addSelect(DB::raw('af.amount AS ads_amount'))
            ->addSelect(DB::raw('af.tacit AS ads_tacit'))
            ->addSelect(DB::raw('af.tacit_delivered_at AS ads_tacit_delivered_at'))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'company:id,name',
                'user:id,name,company_id,deleted_at',
                'user.Company:id,name',
                'dispatcher:id,name,company_id,deleted_at',
                'dispatcher.Company:id,name',
                'note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
                'note.Adsform:id,work_report_id,note_id,amount,tacit,tacit_delivered_at,created_at',
                'note.workform:id,company_id,note_id,informed_at,rejected',
                'note.workform.adsform:id,work_report_id,amount,tacit,tacit_delivered_at,created_at',
                'WorkReportFlowProductions' => fn ($q) => $q
                    ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                    ->where('is_current', true)
                    ->orderByDesc('id'),
            ])
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('partial', 'desc')
            ->orderByRaw('CASE WHEN dt_ads IS NULL THEN 0 ELSE 1 END DESC')
            ->orderBy('dt_inform', 'asc')
            ->orderBy('dt_ads', 'asc')
            ->orderBy('att_at', 'asc')
            ->orderBy('id', 'asc');
    }
}
