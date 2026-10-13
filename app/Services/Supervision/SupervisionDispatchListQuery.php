<?php

namespace App\Services\Supervision;

use App\Models\{Note, User};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta única da lista de despacho de Fiscalização (uma linha por informe, parcial ou D5 passiva).
 * Usada pela tela (Livewire) e pela exportação, para que ambas reflitam exatamente as mesmas linhas.
 */
class SupervisionDispatchListQuery
{
    /**
     * @param array{serviceUuid: string, user?: ?User, search?: ?string, multiSearch?: array, rubrica_s?: array,
     *              typeNote?: mixed, not_assigned?: bool, filterD5?: bool, filter?: array, bulkSearchAnyStatus?: bool} $p
     */
    public function build(array $p): Builder
    {
        $serviceUuid = $p['serviceUuid'];
        $user        = $p['user'] ?? null;
        $search      = (string) ($p['search'] ?? '');
        $multiSearch = (array) ($p['multiSearch'] ?? []);
        $rubricaS    = (array) ($p['rubrica_s'] ?? []);
        $typeNote    = $p['typeNote'] ?? null;
        $notAssigned = (bool) ($p['not_assigned'] ?? false);
        $filterD5    = (bool) ($p['filterD5'] ?? false);
        $filter      = (array) ($p['filter'] ?? []);
        $companyIds  = [];

        $bypassEligibility = !empty($p['bulkSearchAnyStatus']) && count($multiSearch);

        $dispatchRows = app(WorkReportSupervisionCandidateQuery::class)
            ->dispatchRowsSubquery((bool) $bypassEligibility);

        $query = Note::query()
            ->excludeCanceledFullDone()
            ->joinSub($dispatchRows, "dispatch_rows", function ($join) {
                $join->on("dispatch_rows.note_id", "=", "notes.id");
            })
            ->leftJoin("work_reports", "work_reports.id", "=", "dispatch_rows.work_report_id");

        if ($user?->contract) {
            $companyIds = SicodeRules::visibleCompanyIdsFor($user);

            if (empty($companyIds)) {
                $query->whereRaw("0 = 1");
            } else {
                // Cada linha precisa estar ligada à Production da própria empresa
                // parceira. Nunca basta existir outra Production da mesma Nota/OV:
                // um informe não pode herdar a visibilidade de seu irmão.
                // As dispatch_rows já representam somente contextos ainda elegíveis
                // para Fiscalização; por isso uma Production concluída permanece
                // visível somente enquanto o SAP ainda mantiver esse contexto elegível.
                $query->where(function ($visible) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                    $visible->whereExists(function ($production) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                        $production->selectRaw("1")
                            ->from("work_report_flow_productions as wrfp")
                            ->join("productions as p", "p.id", "=", "wrfp.production_id")
                            ->whereColumn("wrfp.work_report_id", "dispatch_rows.work_report_id")
                            ->where("wrfp.stage", "fiscalization")
                            ->where("wrfp.is_current", true)
                            ->where("p.service_id", $serviceUuid)
                            ->whereIn("p.company_id", $companyIds);
                    })->orWhereExists(function ($production) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                        $production->selectRaw("1")
                            ->from("productionables as pa")
                            ->join("productions as p", "p.id", "=", "pa.production_id")
                            ->whereColumn("pa.productionable_id", "dispatch_rows.partial_id")
                            ->where("pa.productionable_type", (new \App\Models\Partial())->getMorphClass())
                            ->where("p.service_id", $serviceUuid)
                            ->whereIn("p.company_id", $companyIds);
                    })->orWhereExists(function ($production) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                        $production->selectRaw("1")
                            ->from("productionables as pa")
                            ->join("productions as p", "p.id", "=", "pa.production_id")
                            ->whereColumn("pa.productionable_id", "dispatch_rows.passive_d5_id")
                            ->where("pa.productionable_type", (new \App\Models\FiveNote())->getMorphClass())
                            ->where("p.service_id", $serviceUuid)
                            ->whereIn("p.company_id", $companyIds);
                    });
                });
            }
        }

        if (strlen($search)) {

            $query->where(function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                return $q->where('note', 'like', '%' . trim($search) . '%')
                    ->orWhere('material', 'like', '%' . trim($search) . '%')
                    ->orWhere('numPedido', 'like', '%' . trim($search) . '%')
                    ->orWhere('group1', 'like', '%' . trim($search) . '%')
                    ->orWhere('group2', 'like', '%' . trim($search) . '%')
                    ->orWhere('group4', 'like', '%' . trim($search) . '%')
                    ->orWhere('group5', 'like', '%' . trim($search) . '%')
                    ->orWhereRelation('Orders', 'ordem', 'like', '%' . trim($search) . '%');
            });
        }

        if (count($multiSearch)) {
            $query->where(function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                return $q->WhereIn('note', $multiSearch)
                    ->orWhereRelation('Orders', function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                        $q->WhereIn('ordem', $multiSearch);
                    });
            });
        }

        if (count($rubricaS)) {
            $query->where(function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                return $q->whereIn('rubrica', $rubricaS)
                    ->orWhereNull('rubrica');
            });
        }

        if ($typeNote) {
            $query->where(function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                return $q->where('type_note', $typeNote)
                    ->orWhereNull('type_note');
            });
        }

        if ($notAssigned) {
            $query->where(function ($unassigned) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                $unassigned->where(function ($report) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                    $report->whereNotNull("dispatch_rows.work_report_id")
                        ->whereNotExists(function ($production) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                            $production->selectRaw("1")
                                ->from("work_report_flow_productions as wrfp")
                                ->join("productions as p", "p.id", "=", "wrfp.production_id")
                                ->whereColumn("wrfp.work_report_id", "dispatch_rows.work_report_id")
                                ->where("wrfp.stage", "fiscalization")
                                ->where("wrfp.is_current", true)
                                ->where("p.service_id", $serviceUuid)
                                ->where("p.completed", false)
                                ->where("p.confirmed", false);
                        });
                })->orWhere(function ($partial) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                    $partial->whereNotNull("dispatch_rows.partial_id")
                        ->whereNotExists(function ($production) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                            $production->selectRaw("1")->from("productionables as pa")
                                ->join("productions as p", "p.id", "=", "pa.production_id")
                                ->whereColumn("pa.productionable_id", "dispatch_rows.partial_id")
                                ->where("pa.productionable_type", (new \App\Models\Partial())->getMorphClass())
                                ->where("p.service_id", $serviceUuid)
                                ->where("p.completed", false)->where("p.confirmed", false);
                        });
                })->orWhere(function ($d5) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                    $d5->whereNotNull("dispatch_rows.passive_d5_id")
                        ->whereNotExists(function ($production) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                            $production->selectRaw("1")
                                ->from("productionables as pa")
                                ->join("productions as p", "p.id", "=", "pa.production_id")
                                ->whereColumn("pa.productionable_id", "dispatch_rows.passive_d5_id")
                                ->where("pa.productionable_type", (new \App\Models\FiveNote())->getMorphClass())
                                ->where("p.service_id", $serviceUuid)
                                ->where("p.completed", false)
                                ->where("p.confirmed", false);
                        });
                });
            });
        }

        if ($filterD5) {
            $query->where(function ($d5) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                $d5->whereExists(function ($linked) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                    $linked->selectRaw("1")
                        ->from("five_notes as fn")
                        ->whereColumn("fn.work_report_id", "dispatch_rows.work_report_id")
                        ->where("fn.is_completed", true)
                        ->where("fn.is_supervisioned", false)
                        ->where("fn.is_archived", false);
                })->orWhereNotNull("dispatch_rows.passive_d5_id");
            });
        }

        if (isset($filter['rubrica'])) {
            $query->whereIn('rubrica', $filter['rubrica']);
        }

        if (isset($filter['city'])) {

            $query->whereRelation('City', function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                $q->whereIn('rdMunicipio', $filter['city']);
            });
        }

        $query->with([
            'orders' => function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                $q->where('statusSist', 'not like', 'ENT%')->where('statusSist', 'not like', 'ENC%');
            },
            'WorkForms' => function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                $q->where('canceled', false)->with([
                    'Orders:id,ordem',
                    'Adsform:id,work_report_id,tacit,created_at',
                    'FiveNote:id,note_id,work_report_id,is_completed,is_supervisioned,is_archived,completed_at',
                ]);
            },
            'Productions' => function ($q) use ($search, $multiSearch, $rubricaS, $typeNote, $serviceUuid, $filter, $companyIds) {
                $q->where('service_id', $serviceUuid)->with([
                    'User',
                    'Company',
                    'WorkReportFlowProductions',
                    'fiveNotes:id,note_d5,work_report_id,note_id,is_completed,is_supervisioned,is_archived,completed_at',
                    'partialInforms:id',
                ]);
            },
            'Wpas',
            'Partials',
            'TempAdsInfos',
            'OldAds',
            'FiveNote',
            'FiveNotes:id,note_id,work_report_id,is_completed,is_supervisioned,is_archived',
        ])
            ->select(
                "notes.*",
                "dispatch_rows.partial_id as row_partial_id",
                "work_reports.id as operational_work_report_id",
                "dispatch_rows.passive_d5_id",
                "dispatch_rows.row_created_at as work_dt_created"
            )
            ->orderBy("work_dt_created", "ASC")
            ->orderBy("notes.id", "ASC")
            ->orderBy("dispatch_rows.work_report_id", "ASC")
            ->orderBy("dispatch_rows.partial_id", "ASC");

        return $query;
    }
}
