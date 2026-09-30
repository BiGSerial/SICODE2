<?php

namespace App\Services\Quality;

/**
 * Regras das ordens de orçamento do 2º ciclo.
 * Espelha as regras do encerramento normal do Desenho (Forms\Analise): 12 dígitos e prefixos 170/190/150/200,
 * exigindo 200 quando a Nota começa com dígito >= 3. Mantido aqui porque as regras originais são métodos
 * privados do componente Livewire (consolidação futura documentada em docs/quality/README.md).
 */
class BudgetOrderRules
{
    public function normalizeNumber(mixed $value): ?float
    {
        $raw = str_replace(' ', '', trim((string) $value));

        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, ',')) {
            $raw = str_replace(['.', ','], ['', '.'], $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    public function orderNumberError(?string $orderNumber, ?string $noteNumber): ?string
    {
        $value = trim((string) $orderNumber);

        if ($value === '') {
            return 'Informe o número da ordem.';
        }

        if (!preg_match('/^\d+$/', $value)) {
            return 'Número da ordem inválido: use apenas números.';
        }

        if (strlen($value) !== 12) {
            return 'Número da ordem inválido: informe exatamente 12 dígitos.';
        }

        $prefix = substr($value, 0, 3);

        if ($this->requiresPrefix200($noteNumber)) {
            return $prefix === '200' ? null : 'Número da ordem inválido: para esta Nota/OV o prefixo deve iniciar com 200.';
        }

        return in_array($prefix, ['170', '190', '150', '200'], true)
            ? null
            : 'Número da ordem inválido: o prefixo deve iniciar com 170, 190, 150 ou 200.';
    }

    /**
     * Normaliza e valida a lista de ordens. Devolve as ordens normalizadas ou lança QualityWorkflowException.
     *
     * @param  array<int, array<string, mixed>>  $orders
     * @return array<int, array<string, mixed>>
     */
    public function validate(array $orders, ?string $noteNumber): array
    {
        if (!$orders) {
            throw new QualityWorkflowException('Adicione pelo menos uma ordem de orçamento.');
        }

        $normalized = [];
        $seen       = [];

        foreach (array_values($orders) as $index => $row) {
            $line   = $index + 1;
            $number = trim((string) ($row['order_number'] ?? ''));
            $error  = $this->orderNumberError($number, $noteNumber);

            if ($error) {
                throw new QualityWorkflowException("Ordem {$line}: {$error}");
            }

            if (in_array($number, $seen, true)) {
                throw new QualityWorkflowException("Ordem {$line}: número de ordem duplicado nesta submissão.");
            }
            $seen[] = $number;

            $costs = [];

            foreach (['total_cost' => 'total', 'company_cost' => 'da empresa', 'client_cost' => 'do cliente'] as $key => $label) {
                $costs[$key] = $this->normalizeNumber($row[$key] ?? null);

                if ($costs[$key] === null || $costs[$key] < 0) {
                    throw new QualityWorkflowException("Ordem {$line}: informe o valor {$label} (número maior ou igual a zero).");
                }
            }

            $normalized[] = ['order_number' => $number] + $costs;
        }

        return $normalized;
    }

    private function requiresPrefix200(?string $noteNumber): bool
    {
        $digits = preg_replace('/\D+/', '', (string) $noteNumber);

        return $digits !== '' && (int) substr($digits, 0, 1) >= 3;
    }
}
