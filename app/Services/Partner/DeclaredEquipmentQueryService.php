<?php

namespace App\Services\Partner;

use App\Models\{Equipment, User};
use App\Services\PartnerAccess\PartnerAccessGate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DeclaredEquipmentQueryService
{
    /**
     * @param array<string,mixed> $params
     */
    public function build(array $params, User $user): Builder
    {
        $query = Equipment::query();

        if (!$user->superadm) {
            $companyIds = PartnerAccessGate::visibleCompanyIdsFor($user);

            $query->whereRelation('WorkReport', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        $dateIn  = $params['date_in'] ?? null;
        $dateOut = $params['date_out'] ?? null;

        if ($dateIn && $dateOut) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateIn)->startOfDay(),
                Carbon::parse($dateOut)->endOfDay(),
            ]);
        } elseif ($dateIn) {
            $query->where('created_at', '>=', Carbon::parse($dateIn)->startOfDay());
        } elseif ($dateOut) {
            $query->where('created_at', '<=', Carbon::parse($dateOut)->endOfDay());
        }

        $search = trim((string) ($params['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('patrimony', 'like', '%' . $search . '%')
                    ->orWhereRelation('WorkReport.Note', function ($q) use ($search) {
                        return $q->where('note', 'like', '%' . $search . '%')
                            ->orWhere('lexp', 'like', '%' . $search . '%');
                    })->orWhereRelation('WorkReport.Orders', function ($q) use ($search) {
                        return $q->where('ordem', 'like', '%' . $search . '%');
                    });
            });
        }

        $filters = is_array($params['filters'] ?? null) ? $params['filters'] : [];

        if (!empty($filters['rubrica'])) {
            $query->whereRelation('WorkReport.Note', function ($q) use ($filters) {
                $q->whereIn('rubrica', $filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        }

        if (!empty($filters['region'])) {
            $query->whereRelation('WorkReport.Note.City', function ($q) use ($filters) {
                $q->whereIn('regiao', $filters['region']);
            });
        }

        if (!empty($filters['city'])) {
            $query->whereRelation('WorkReport.Note', function ($q) use ($filters) {
                $q->whereIn('lexp', $filters['city'])
                    ->orWhereNull('lexp');
            });
        }

        if (!empty($params['equipType'])) {
            $query->where('type', $params['equipType']);
        }

        // Seleção múltipla da tela (filtros do portal da parceira): listas de tipos, movimentos (1 = instalação, 0 = desinstalação) e empresas.
        if (!empty($params['equipTypes'])) {
            $query->whereIn('type', (array) $params['equipTypes']);
        }

        if (($params['moviment'] ?? '') !== '') {
            $query->where('installed', (bool) $params['moviment']);
        }

        if (!empty($params['moviments']) && count((array) $params['moviments']) === 1) {
            $query->where('installed', (bool) ((array) $params['moviments'])[0]);
        }

        if (!empty($params['companySelected'])) {
            $query->whereRelation('WorkReport', 'company_id', $params['companySelected']);
        }

        if (!empty($params['companyIds'])) {
            $query->whereRelation('WorkReport', fn ($q) => $q->whereIn('company_id', (array) $params['companyIds']));
        }

        return $query->orderBy('patrimony');
    }
}
