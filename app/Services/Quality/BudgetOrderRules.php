<?php

namespace App\Services\Quality;

/**
 * Regras das ordens de orçamento do 2º ciclo.
 * Espelha as regras do encerramento normal do Desenho (Forms\Analise): 12 dígitos e prefixos 170/180/190/193/150/200,
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

        return in_array($prefix, ['170', '180', '190', '193', '150', '200'], true)
            ? null
            : 'Número da ordem inválido: o prefixo deve iniciar com 170, 180, 190, 193, 150 ou 200.';
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

            $costs = $this->normalizeCosts($row);

            foreach ($costs as $key => $value) {
                if ($value < 0) {
                    $label = ['total_cost' => 'total', 'company_cost' => 'da empresa', 'client_cost' => 'do cliente'][$key];

                    throw new QualityWorkflowException("Ordem {$line}: informe o valor {$label} (número maior ou igual a zero).");
                }
            }

            $normalized[] = ['order_number' => $number] + $costs;
        }

        return $normalized;
    }

    /**
     * Aplica a mesma composição do encerramento normal: os custos sempre fecham
     * em total = empresa + cliente. Campo monetário vazio significa zero quando
     * não for possível deduzir outro valor; assim o usuário não precisa digitar
     * "0,00" apenas para satisfazer a validação.
     *
     * @param  array<string, mixed>  $order
     * @return array{total_cost: float, company_cost: float, client_cost: float}
     */
    public function normalizeCosts(array $order): array
    {
        $total   = $this->normalizeNumber($order['total_cost'] ?? null);
        $company = $this->normalizeNumber($order['company_cost'] ?? null);
        $client  = $this->normalizeNumber($order['client_cost'] ?? null);

        if ($total === null) {
            $company ??= 0.0;
            $client ??= 0.0;
            $total = round($company + $client, 2);
        } elseif ($company === null && $client === null) {
            // Padrão contratual do encerramento normal: total informado sem
            // rateio corresponde integralmente ao cliente.
            $company = 0.0;
            $client  = $total;
        } elseif ($company === null) {
            $client ??= 0.0;
            $client  = min($client, $total);
            $company = round($total - $client, 2);
        } elseif ($client === null) {
            $company = min($company, $total);
            $client  = round($total - $company, 2);
        } else {
            // Havendo os três valores, o total é a referência e a parcela da
            // empresa preserva a proporcionalidade informada pelo usuário.
            $company = min($company, $total);
            $client  = round($total - $company, 2);
        }

        return [
            'total_cost'   => round($total, 2),
            'company_cost' => round($company, 2),
            'client_cost'  => round($client, 2),
        ];
    }

    private function requiresPrefix200(?string $noteNumber): bool
    {
        $digits = preg_replace('/\D+/', '', (string) $noteNumber);

        return $digits !== '' && (int) substr($digits, 0, 1) >= 3;
    }
}
