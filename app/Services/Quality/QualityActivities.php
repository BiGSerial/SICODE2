<?php

namespace App\Services\Quality;

use App\Enum\QualityStageType;
use App\Models\{QualitySetting, Service};

/**
 * Atividade (Service) de cada ciclo. Nada é fixo no código: a Gestão escolhe em Qualidade > Critérios e atividades
 * (hoje: 1º ciclo = Levantamento, 2º ciclo = Desenho). Cada ciclo tem a sua Production.
 */
class QualityActivities
{
    public const KEY = 'activities';

    /** @return array<string, string|null> ciclo => uuid do Service */
    public function map(): array
    {
        $value = QualitySetting::query()->where('key', self::KEY)->value('value') ?? [];

        return [
            QualityStageType::PROJECT->value => $value[QualityStageType::PROJECT->value] ?? null,
            QualityStageType::BUDGET->value  => $value[QualityStageType::BUDGET->value] ?? null,
        ];
    }

    /** @return array<int, string> uuids das atividades configuradas (ambos os ciclos). */
    public function serviceIds(): array
    {
        return array_values(array_filter($this->map()));
    }

    public function serviceFor(QualityStageType $phase): ?Service
    {
        $uuid = $this->map()[$phase->value] ?? null;

        return $uuid ? Service::query()->where('uuid', $uuid)->first() : null;
    }

    public function missing(): array
    {
        return collect(QualityStageType::cases())->filter(fn ($phase) => !$this->serviceFor($phase))->map(fn ($phase) => 'atividade do ' . $phase->short())->values()->all();
    }
}
