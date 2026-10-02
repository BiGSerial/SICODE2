<?php

namespace App\Services\D5;

use App\Models\FiveNote;
use App\Models\Production;
use Illuminate\Support\Str;

/**
 * Status atual e solução (resultado da última fiscalização) de uma D5, para exibição.
 */
class D5StatusPresenter
{
    /** @return array{key:string,label:string,color:string} */
    public static function status(FiveNote $five): array
    {
        if ($five->is_archived) {
            return ['key' => 'finalizada', 'label' => 'Finalizada', 'color' => 'text-bg-success'];
        }

        if (blank($five->note_d5) && !$five->visible_partner) {
            return ['key' => 'aguardando_geracao', 'label' => 'Aguardando Geração de D5', 'color' => 'text-bg-secondary'];
        }

        if ($five->returned && !$five->is_completed && !$five->is_supervisioned) {
            return ['key' => 'devolvida', 'label' => 'Devolvida à Empreiteira (Não Resolvido)', 'color' => 'text-bg-danger'];
        }

        if ($five->is_supervisioned) {
            return ['key' => 'aguardando_medicao', 'label' => 'Aguardando Medição', 'color' => 'text-bg-primary'];
        }

        if ($five->is_completed) {
            return ['key' => 'aguardando_fiscalizacao', 'label' => 'Aguardando Fiscalização', 'color' => 'text-bg-warning'];
        }

        return ['key' => 'aguardando_empreiteira', 'label' => 'Aguardando Empreiteira', 'color' => 'text-bg-danger'];
    }

    /**
     * Resultado da fiscalização mais recente da D5 (que tenha conclusão). Valores antigos
     * (Fiscalizado Sem/Com Pendências) são traduzidos para Resolvido / Não Resolvido.
     *
     * @return array{label:string,color:string,by:?string,at:?\Carbon\CarbonInterface,info:?string}|null
     */
    public static function solution(FiveNote $five): ?array
    {
        /** @var Production|null $production */
        $production = $five->productions
            ->filter(fn ($p) => Str::contains(Str::lower(Str::ascii((string) ($p->Service->service ?? ''))), 'fiscaliz')
                && filled($p->Analise?->conclusion))
            ->sortByDesc(fn ($p) => $p->completed_at ?? $p->created_at)
            ->first();

        if (!$production) {
            return null;
        }

        $conclusion = strtoupper(trim((string) $production->Analise->conclusion));

        [$label, $color] = match ($conclusion) {
            'RESOLVIDO', 'FISCALIZADO SEM PENDENCIAS' => ['Resolvido', 'text-bg-success'],
            'NAO RESOLVIDO', 'FISCALIZADO COM PENDENCIAS' => ['Não Resolvido', 'text-bg-danger'],
            default => [Str::title(Str::lower($conclusion)), 'text-bg-secondary'],
        };

        return [
            'label' => $label,
            'color' => $color,
            'by'    => $production->User?->name,
            'at'    => $production->completed_at ?? $production->created_at,
            'info'  => $production->Analise->info ?: null,
        ];
    }
}
