<?php

namespace App\Services\Quality;

use App\Custom\RuleBuilder;
use App\Models\{Note, QualityPoolRule};
use Illuminate\Database\Eloquent\Builder;

/**
 * O Pool é sempre uma QUERY de NOTAS (nada é persistido até a Gestão despachar).
 *
 * Regras FIXAS: Nota de tipo 1, não cancelada por completo, sem processo de Qualidade.
 * Regras CONFIGURÁVEIS (`quality_pool_rules`): mesmo modelo dos Serviços (coluna + condição + valor, com exclusão e 2ª condição).
 * Sem nenhuma regra configurada o Pool fica VAZIO de propósito (evita despachar todas as Notas por engano).
 */
class QualityEligibilityService
{
    public const FIXED_RULES = [
        'A Nota é do tipo 1.',
        'A Nota não foi cancelada integralmente.',
        'A Nota ainda não possui (nem possuiu) processo de Qualidade.',
        'A Nota não tem atividade aberta nas atividades escolhidas para a Qualidade.',
    ];

    public function rules()
    {
        return QualityPoolRule::query()->orderBy('id')->get();
    }

    public function query(array $filters = []): Builder
    {
        $rules = $this->rules();

        $query = Note::query()->excludeCanceledFullDone()->where('type_note', 1)->whereDoesntHave('QualityProcesses');

        if ($rules->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        RuleBuilder::applyRules($query, $rules);

        // Nota que já tem atividade aberta em alguma das atividades da Qualidade não entra no Pool.
        if ($serviceIds = app(QualityActivities::class)->serviceIds()) {
            $query->whereDoesntHave('Productions', fn ($production) => $production->whereIn('service_id', $serviceIds)->where('completed', false));
        }

        return $query
            ->when(!empty($filters['notes']), fn (Builder $q) => $q->where(fn ($w) => $w->whereIn('note', $filters['notes'])->orWhereIn('numPedido', $filters['notes'])))
            ->when($filters['note'] ?? null, fn (Builder $q, $term) => $q->where(fn ($w) => $w->where('note', 'like', "%{$term}%")->orWhere('numPedido', 'like', "%{$term}%")))
            ->orderBy('days_left')->orderBy('dt_status');
    }

    public function paginate(array $filters = [], int $perPage = 25)
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function eligible(Note $note): bool
    {
        return $this->query()->whereKey($note->id)->exists();
    }
}
