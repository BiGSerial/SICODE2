<?php

namespace App\Services\Publication;

use App\Models\{City, Note};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

/** @package  */
class NoteFilter
{
    private $filters;

    private $btzeroform;

    /** Quando a seleção veio explícita (telas no padrão de listas operacionais), `city` são códigos de município (`rdMunicipio`). */
    private bool $explicit = false;

    /**
     * Notas elegíveis para Publicação.
     *
     * Filtros adicionais (company / rubrica / region / regional / city): de `$explicitFilters` quando informado (telas com
     * `WithListFilters`, sem sessão); senão de `$_SESSION['filter'][$filterGroup]`, como sempre foi (Wall e telas antigas).
     */
    public function filter($filterGroup, $btzeroform = true, ?array $explicitFilters = null)
    {

        $this->btzeroform = $btzeroform;
        $this->explicit   = $explicitFilters !== null;
        $this->filters    = $explicitFilters;

        if (!$this->explicit) {
            if (!(session_status() == PHP_SESSION_ACTIVE)) {
                if (!session()->isStarted()) {
                    session()->start();
                }
            }

            if (isset($_SESSION['filter'][$filterGroup])) {
                $this->filters = $_SESSION['filter'][$filterGroup];
            }
        }

        $query = Note::query();

        $query->where(function ($q) {

            $q->where(function ($wq) {
                $wq->whereHas('WorkForm', function ($sq) {
                    $sq->where('rejected', false)
                        ->whereHas('Orders', fn (Builder $order) => $this->publicationEligibleOrder($order));
                });
            });
        });

        if (SicodeRules::workReportSplitsBtzeroEpFinalFlows()) {
            $networkPrefixes = SicodeRules::workReportFinalScopeOrderPrefixes('network');

            if (!empty($networkPrefixes)) {
                $query->where(function (Builder $scopeQuery) use ($networkPrefixes) {
                    $scopeQuery->where('type_note', '<>', 1)
                        ->orWhereNull('type_note')
                        ->orWhereHas('WorkForm.Orders', function (Builder $orderQuery) use ($networkPrefixes) {
                            $orderQuery->where(function (Builder $prefixQuery) use ($networkPrefixes) {
                                foreach ($networkPrefixes as $prefix) {
                                    $prefixQuery->orWhere('ordem', 'like', "{$prefix}%");
                                }
                            });
                        });
                });
            }
        }

        if (!empty($this->filters['rubrica'])) {
            $query->where(function ($query) {
                $query->whereIn('rubrica', $this->filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        }

        if ($this->explicit) {
            $cityCodes = $this->municipioFilterValues();

            if ($cityCodes) {
                $query->where(function ($query) use ($cityCodes) {
                    $query->whereIn('nexp', $cityCodes)
                        ->orWhereIn('lexp', $cityCodes)
                        ->orWhereNull('lexp');
                });
            }
        } elseif (isset($this->filters['city'])) {
            $query->where(function ($query) {
                $query->whereIn('lexp', $this->filters['city'])
                    ->orWhereNull('lexp');
            });
        }

        if (!empty($this->filters['company'])) {
            $query->whereRelation('WorkForm', function ($q) {
                $q->whereIn('company_id', $this->filters['company']);
            });
        }

        return $query;
    }

    private function publicationEligibleOrder(Builder $query): Builder
    {
        return $query
            ->where('statusSist', 'LIKE', 'LIB%')
            ->whereHas('Operations', function (Builder $operation) {
                $operation->where('operacao', '0020')
                    ->where(function (Builder $status) {
                        $status->where('status', 'like', 'LIB%')
                            ->orWhere('status', 'like', 'CNPA%')
                            ->orWhere('status', 'like', 'JBFI LIB%');
                    });
            });
    }

    /** Códigos de município (`rdMunicipio`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    private function municipioFilterValues(): array
    {
        $cities    = $this->filters['city'] ?? [];
        $regions   = $this->filters['region'] ?? [];
        $regionals = $this->filters['regional'] ?? [];

        if (empty($cities) && empty($regions) && empty($regionals)) {
            return [];
        }

        return City::query()
            ->when($cities, fn ($q) => $q->whereIn('rdMunicipio', (array) $cities))
            ->when($regions, fn ($q) => $q->whereIn('regiao', (array) $regions))
            ->when($regionals, fn ($q) => $q->whereIn('baseConstrucao', (array) $regionals))
            ->pluck('rdMunicipio')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
