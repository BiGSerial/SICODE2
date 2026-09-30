<?php

namespace App\Services\Quality;

use App\Models\{QualityProcess, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Quem pode receber a atividade do ciclo corrente: usuário da MESMA empresa do processo e habilitado
 * (`service_users.service = true`) no serviço configurado para o ciclo (`QualityActivities`).
 */
class QualityDesignerResolver
{
    public function __construct(private readonly QualityActivities $activities)
    {
    }

    public function query(QualityProcess $process): Builder
    {
        $service = $this->activities->serviceFor($process->phase);

        return User::query()
            ->where('company_id', $process->company_id)
            ->whereRelation('ToServices', fn ($rel) => $rel->where('service_id', $service?->uuid)->where('service', true))
            ->orderBy('name');
    }

    /** Usuários da empresa habilitados na atividade do ciclo (base do despacho em massa). */
    public function eligibleForCompany(string $companyId, \App\Enum\QualityStageType $phase): Collection
    {
        $service = $this->activities->serviceFor($phase);

        if (!$service) {
            return collect();
        }

        return User::query()->where('company_id', $companyId)
            ->whereRelation('ToServices', fn ($rel) => $rel->where('service_id', $service->uuid)->where('service', true))
            ->orderBy('name')->get();
    }

    public function eligible(QualityProcess $process): Collection
    {
        return $this->activities->serviceFor($process->phase) ? $this->query($process)->get() : collect();
    }

    public function isEligible(QualityProcess $process, ?string $userId): bool
    {
        return $userId !== null && $this->activities->serviceFor($process->phase) !== null && $this->query($process)->whereKey($userId)->exists();
    }
}
