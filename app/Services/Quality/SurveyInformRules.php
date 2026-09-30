<?php

namespace App\Services\Quality;

use App\Helpers\SelectOptions;
use App\Models\{Analise, Note, Production};

/**
 * Informe de encerramento do LEVANTAMENTO (1º ciclo), com os mesmos campos e opções do encerramento normal
 * (Services\Levantamento\Forms\Analise): postes, DOE, interferência em vegetação, conclusão, cadastro e informações.
 */
class SurveyInformRules
{
    public const DOE = ['SIM', 'NAO', 'NAO SEI'];

    public const MA = ['SIM', 'NAO'];

    /** @return array<string, mixed> informe normalizado; lança QualityWorkflowException se inválido. */
    public function validate(array $data): array
    {
        $conclusions = collect(SelectOptions::getSurveyConclusions())->pluck('value')->all();
        $conclusion  = trim((string) ($data['conclusion'] ?? ''));

        if (!in_array($conclusion, $conclusions, true)) {
            throw new QualityWorkflowException('Informe a conclusão do Levantamento.');
        }

        $postes = $data['postes'] ?? null;

        if (!is_numeric($postes) || (int) $postes < 0 || (int) $postes > 500) {
            throw new QualityWorkflowException('Informe a quantidade de postes (0 a 500).');
        }

        if (!in_array($data['doe'] ?? '', self::DOE, true)) {
            throw new QualityWorkflowException('Informe se depende de órgão externo.');
        }

        if (!in_array($data['ma'] ?? '', self::MA, true)) {
            throw new QualityWorkflowException('Informe se haverá interferência em vegetação.');
        }

        $cadastro = filter_var($data['cadastro'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $postesC  = $cadastro ? ($data['postes_c'] ?? null) : 0;

        if ($cadastro && (!is_numeric($postesC) || (int) $postesC < 0 || (int) $postesC > 500)) {
            throw new QualityWorkflowException('Informe a quantidade de postes do cadastro (0 a 500).');
        }

        return [
            'conclusion' => $conclusion, 'postes' => (int) $postes, 'doe' => $data['doe'], 'ma' => $data['ma'],
            'cadastro'   => $cadastro, 'postes_c' => (int) $postesC, 'info' => trim((string) ($data['info'] ?? '')) ?: null,
        ];
    }

    /** Grava o informe na análise e na atividade, como o encerramento normal faz ao salvar/encerrar. */
    public function persist(Production $production, array $inform): void
    {
        $analise = Analise::query()->firstOrCreate(['production_id' => $production->id]);
        $analise->update(['conclusion' => $inform['conclusion'], 'info' => $inform['info'], 'doe' => $inform['doe'], 'postes' => $inform['postes']]);
        $production->update(['postes_u' => $inform['postes'], 'cadastro' => $inform['cadastro'], 'postes_c' => $inform['postes_c'], 'ma' => $inform['ma'] === 'SIM']);
    }

    /** Efeito na Nota no encerramento do Levantamento (normal: doe, ma, postes). */
    public function applyToNote(Note $note, array $inform): void
    {
        $note->update(['doe' => $inform['doe'] === 'SIM', 'ma' => $inform['ma'] === 'SIM', 'postes' => $inform['postes']]);
    }
}
