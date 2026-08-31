<?php

namespace App\Services\Payment;

use App\Models\Edp_depc\City;
use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class NoteFilter
{
    private array $filters = [];

    /**
     * Filtro principal para montar a pilha de notas elegíveis.
     *
     * Regras (OR entre grupos):
     *  (A) WorkForm OK + Orders/Operations coerentes (liberações padrão)
     *  (B) Partials válidas (sem WorkForm)
     *  (C) FiveNote com prioridade (is_completed && is_supervisioned && !is_archived)
     *
     * Filtros adicionais:
     *  - busca simples ($search)
     *  - rubrica / city (vindos de $_SESSION['filter'][$filterGroup])
     *
     * Eager loads úteis para a view/avaliador:
     *  - Productions.User, WorkForm, Partials (ordenadas), FiveNote, Orders.Operations
     */
    public function filter(?string $search, string $filterGroup): Builder
    {
        // Carrega filtros de sessão se existirem
        if (\PHP_SESSION_ACTIVE !== session_status()) {
            if (!session()->isStarted()) { session()->start(); }
        }
        if (isset($_SESSION['filter'][$filterGroup]) && is_array($_SESSION['filter'][$filterGroup])) {
            $this->filters = $_SESSION['filter'][$filterGroup];
        }

        $companyIds = $this->filters['company'] ?? null;
        $rubricas   = $this->filters['rubrica'] ?? null;
        $cityCodes   = $this->municipioFilterValues();

        $query = Note::query();

        // ======== GRUPOS PRINCIPAIS (OR) ========
        $query->where(function (Builder $root) use ($companyIds) {

            // (A) WorkForm válido + Orders/Operations coerentes
            $root->where(function (Builder $q) use ($companyIds) {
                $q->whereHas('WorkForm', function (Builder $wf) use ($companyIds) {
                    // padronizado: campo boolean "rejected"
                    $wf->where('rejected', false)
                       ->when($companyIds, function (Builder $qq) use ($companyIds) {
                           $qq->where(function (Builder $mix) use ($companyIds) {
                               $mix->whereIn('company_id', (array) $companyIds)
                                   ->orWhereNull('company_id');
                           });
                       });
                })
                ->whereHas('Orders', function (Builder $ord) {
                    $ord->where('statusSist', 'LIKE', 'LIB%')
                        ->whereHas('Operations', function (Builder $op) {
                            $op->where('operacao', '0030')->where('status', 'like', 'CONF%');
                        })
                        ->whereHas('Operations', function (Builder $op) {
                            $op->where('operacao', '0040')
                               ->where(function (Builder $qq) {
                                   $qq->where('status', 'like', 'CONF%')
                                      ->orWhere('status', 'like', 'CNPA%');
                               });
                        })
                        ->whereHas('Operations', function (Builder $op) {
                            $op->where('operacao', '0050')
                               ->where(function (Builder $qq) {
                                   $qq->where('status', 'like', 'LIB%')
                                      ->orWhere('status', 'like', 'CNPA%')
                                      ->orWhere('status', 'like', 'JBFI LIB%');
                               });
                        });
                });
            })

            // (B) Partials válidas (sem WorkForm)
            ->orWhere(function (Builder $q) use ($companyIds) {
                $q->whereHas('Partials', function (Builder $p) use ($companyIds) {
                    $p->where('deny', false)
                    ->where('allow', true)
                      ->where('supervision', true)
                      ->where('payment', false)
                      ->when($companyIds, function (Builder $qq) use ($companyIds) {
                          $qq->where(function (Builder $mix) use ($companyIds) {
                              $mix->whereIn('company_id', (array) $companyIds)
                                  ->orWhereNull('company_id');
                          });
                      });
                })
                ->whereDoesntHave('WorkForm'); // prioridade: partials só entram sem WF
            })

            // (C) FiveNote priorizado (is_completed && is_supervisioned && !is_archived)
            ->orWhere(function (Builder $q) {
                $q->whereHas('FiveNote', function (Builder $fn) {
                    $fn->where('is_supervisioned', true)
                       ->where('is_completed', true)
                       ->where('is_archived', false);
                });
            });
        });

        // ======== BUSCA SIMPLES ========
        $query->when($search, function (Builder $q) use ($search) {
            $like = "%{$search}%";
            $q->where(function (Builder $qq) use ($like) {
                $qq->where('note', 'like', $like)
                   ->orWhereRelation('Orders', 'ordem', 'like', $like);
            });
        });

        // ======== FILTROS ADICIONAIS (rubrica / city) ========
        $query->when($rubricas, function (Builder $q) use ($rubricas) {
            $q->where(function (Builder $qq) use ($rubricas) {
                $qq->whereIn('rubrica', (array) $rubricas)
                   ->orWhereNull('rubrica');
            });
        });

        $query->when($cityCodes, function (Builder $q) use ($cityCodes) {
            $q->where(function (Builder $qq) use ($cityCodes) {
                $qq->whereIn('nexp', $cityCodes)
                   ->orWhereIn('lexp', $cityCodes)
                   ->orWhereNull('lexp');
            });
        });

        // ======== EAGER LOADS ÚTEIS (evita N+1 na view/BlockEvaluator) ========
        $query->with([
            // Productions do note (o Main pode filtrar por service_id ao carregar a tela)
            'Productions.User',
            'WorkForm',
            'FiveNote',
            'Partials' => fn ($p) => $p->orderByDesc('created_at'),
            'Orders.Operations',
        ]);

        return $query;
    }

    private function municipioFilterValues(): array
    {
        $cities = $this->filters['city'] ?? [];
        $regions = $this->filters['region'] ?? [];
        $regionals = $this->filters['regional'] ?? [];

        if (empty($cities) && empty($regions) && empty($regionals)) {
            return [];
        }

        $query = City::query();

        if (!empty($cities)) {
            $query->whereIn('rdMunicipio', (array) $cities);
        }

        if (!empty($regions)) {
            $query->whereIn('regiao', (array) $regions);
        }

        if (!empty($regionals)) {
            $query->whereIn('baseConstrucao', (array) $regionals);
        }

        return $query->pluck('rdMunicipio')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
